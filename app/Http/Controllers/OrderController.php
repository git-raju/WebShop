<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ordersData =  Order::all();
        return response()->json(['success' => true, 'data' => $ordersData]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validation
        $request->validate([
            "customer_id" => "required",
            "products" => "required|array",
            "quantity" => "required|array"
        ]);

        $quantity = $request->quantity;
        // Get price of products
        $productDetails = Product::whereIn('id', $request->products)->get(['id','price'])->toArray();
        
        $tempOrderItems = [];
        $orderAmount = 0;
        foreach($productDetails as $key => $value){            
            $itemQuantity = $quantity[$key] ?? 1;
            $orderAmount = $orderAmount + ($itemQuantity * $value['price']);
            $tempOrderItems[] = [
                'product_id'    => $value['id'],
                'quantity'      => $itemQuantity,
                'item_price'    => $value['price']
            ];
        } 
        $orderData = [
            "customer"      =>  $request->customer_id,  // THIS CAN be taken from authented user details(from JWT)
            "payed"         =>  $orderAmount,
            "order_status"  =>  'pending',
        ]; 
        // Create Order
        $orderID = Order::create($orderData)->id;
        // Create Order items
        if(count($tempOrderItems) > 0){
            // Add order id with order items
            $orderItems = array_map(function ($item) use ($orderID) {
                $item['order_id'] = $orderID;
                return $item;
            }, $tempOrderItems);
            \DB::table('order_items')->insert($orderItems);
        }
        return response()->json([
            'success' => true, 
            'message' => 'Order placed successfully.',
            'orderID' => $orderID
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $orderData = Order::find($id);
        return response()->json(['success' => true, 'data' => $orderData]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderID
     * @return \Illuminate\Http\Response
     */    
    public function update(Request $request, $orderID)
    {
        // validation
        $request->validate([
            "product_id"   => "required"
        ]);
           
        // Get exsting order & order items 
        $orderObj = Order::find($orderID);
        $orderDetails = $orderObj->toArray();
        if(empty($orderDetails)){
            return response()->json(['success' => false, 'message' => 'Invalid order']);
        }
        else if(!empty($orderDetails) && $orderDetails['order_status'] != 'pending'){
            return response()->json(['success' => false, 'message' => 'Order is already placed, you can\'t add more products.']);
        }    
        
        $orderItemsDetails = $orderObj->orderItems;         
        $productIDs = array_unique(array_merge([$request->product_id], array_column($orderItemsDetails->toArray(),'product_id')));
        
        // Get price of products
        $productDetails = Product::whereIn('id', $productIDs)->get(['id','price'])->toArray();
         
        $existingItemsQuantity = [];
        foreach($orderItemsDetails as $key => $value){
            $existingItemsQuantity[$value['product_id']] = $value['quantity'];
        } 
        $oldOrderItems = [];
        $newOrderItems = [];
        $orderAmount = 0;
        foreach($productDetails as $key => $value){
            // check if product already exists in order items            
            if(array_key_exists($value['id'], $existingItemsQuantity)){
                $itemQuantity = $existingItemsQuantity[$value['id']];
                if($value['id'] == $request->product_id){
                    $itemQuantity = $itemQuantity + 1;      
                }                                                        
                $orderAmount = $orderAmount + ($itemQuantity * $value['price']);
                $oldOrderItems[] = [
                    'order_id'      => $orderID,
                    'product_id'    => $value['id'],
                    'quantity'      => $itemQuantity,
                    'item_price'    => $value['price']
                ];
            }  
            else{
                $itemQuantity = 1;
                $orderAmount = $orderAmount + ($itemQuantity * $value['price']);
                $newOrderItems[] = [
                    'order_id'      => $orderID,
                    'product_id'    => $value['id'],
                    'quantity'      => $itemQuantity,
                    'item_price'    => $value['price']
                ];
            }               
        }  
        $updateOrderData = [
            "customer"      =>  1, // static for now // THIS CAN be taken from authented user details(from JWT)
            "payed"         =>  $orderAmount,
            "order_status"  =>  'pending',
        ]; 

        # TODO : BEGIN DB TRANSACTION STATEMENT HERE
            // update Order
            Order::where('id', $orderID)->update($updateOrderData);

            // add new Order items
            if(count($newOrderItems) > 0){
                \DB::table('order_items')->insert($newOrderItems);
            }
            // update old Order items
            if(count($oldOrderItems) > 0){
                foreach($oldOrderItems as $oldItem){
                    OrderItem::where([
                        'order_id' => $orderID,
                        'product_id' => $oldItem['product_id'],
                        ])->update($oldItem);
                }            
            }
        # END DB TRANSACTION STATEMENT HERE
        return response()->json([
            'success' => true, 
            'message' => 'Order updated successfully.',
            'orderID' => $orderID
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $orderID
     * @return \Illuminate\Http\Response
     */    
    public function pay(Request $request, $orderID)
    {
        // validation
        $request->validate([
            "order_id"          => "required", // NOT REQUIRED HERE, $orderID is already coming from URL
            "customer_email"    => "required|email",
            "value"             => "required"  // NOT REQUIRED HERE, IT CAN BE FETCHED FROM DB
        ]);
           
        // Get exsting order & order items 
        $orderObj = Order::find($orderID);
        $orderDetails = $orderObj->toArray();
        if(empty($orderDetails)){
            return response()->json(['success' => false, 'message' => 'Invalid order']);
        }
        else if(!empty($orderDetails) && $orderDetails['order_status'] != 'pending'){
            return response()->json(['success' => false, 'message' => 'Payment is already done.']);
        }    
        $orderAmount = $request->value;
        // Get orderAmount from DB
        //$orderAmount = $orderDetails['payed'];

        $url = env('PAYMENT_URL', 'https://superpay.view.agentur-loop.com/pay');  
        
        $data = array(
            'order_id'          => $orderID,
            'customer_email'    => $request->customer_email,
            'value'             => $orderAmount
        );  
        $payData = json_encode($data);
        $success = false;
        try{
            $ch = curl_init();
            //options for curl
            $arrayOptions = array(            
            CURLOPT_URL             =>  $url,
            CURLOPT_POST            =>  true,
            CURLOPT_POSTFIELDS      =>  $payData,
            CURLOPT_RETURNTRANSFER  =>  true,
            CURLOPT_HTTPHEADER      =>  array('Content-Type:application/json')
            );
            
            //setting multiple options using curl_setopt_array
            curl_setopt_array($ch,$arrayOptions);
            
            // using curl_exec() is used to execute the POST request
            $response = curl_exec($ch);
            
            //decode the response
            $response = json_decode($response, true);     
            curl_close($ch);
            
            if(isset($response['message']) && $response['message'] == 'Payment Successful'){
                $success = true;
                $updateOrderData = [                           
                    "order_status"  =>  'success', //'payed',
                ];         
                // update Order
                Order::where('id', $orderID)->update($updateOrderData);
            } 
        }
        catch(\Exception $e){
            $response['message'] = $e->getMessage();
        }                   
        
        return response()->json([
            'success' => $success, 
            'message' => $response['message'] ?? 'Something went wrong, please try again later!',            
        ]);
    }
}
