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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // validation
        $request->validate([
            "customer_id"   => "required",
            "order_id"      => "required",
            "products"      => "required|array",
            "quantity"      => "required|array"
        ]);

        $quantity = $request->quantity;        
        // Get exsting order & order items 
        $orderObj = Order::find($request->order_id);
        $orderDetails = $orderObj->toArray();
        if(empty($orderDetails)){
            return response()->json(['success' => false, 'message' => 'Invalid order']);
        }
        else if(!empty($orderDetails) && $orderDetails['order_status'] != 'pending'){
            return response()->json(['success' => false, 'message' => 'Order is already placed, you can\'t add more products.']);
        }    
        
        $orderItemsDetails = $orderObj->orderItems;         
        $productIDs = array_unique(array_merge($request->products, array_column($orderItemsDetails->toArray(),'product_id')));
        
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
                // check if product exists in input
                $index = array_search($value['id'], $request->products);                
                if($index > -1){
                    $itemQuantity = $itemQuantity + ($quantity[$index] ?? 1);                    
                }                                
                $orderAmount = $orderAmount + ($itemQuantity * $value['price']);
                $oldOrderItems[] = [
                    'order_id'      => $request->order_id,
                    'product_id'    => $value['id'],
                    'quantity'      => $itemQuantity,
                    'item_price'    => $value['price']
                ];
            }  
            else{
                $itemQuantity = $quantity[$key] ?? 1;
                $orderAmount = $orderAmount + ($itemQuantity * $value['price']);
                $newOrderItems[] = [
                    'order_id'      => $request->order_id,
                    'product_id'    => $value['id'],
                    'quantity'      => $itemQuantity,
                    'item_price'    => $value['price']
                ];
            }               
        }  
        $updateOrderData = [
            "customer"      =>  $request->customer_id,  // THIS CAN be taken from authented user details(from JWT)
            "payed"         =>  $orderAmount,
            "order_status"  =>  'pending',
        ]; 

        # TODO : BEGIN DB TRANSACTION STATEMENT HERE
            // update Order
            Order::where('id', $request->order_id)->update($updateOrderData);

            // add new Order items
            if(count($newOrderItems) > 0){
                \DB::table('order_items')->insert($newOrderItems);
            }
            // update old Order items
            if(count($oldOrderItems) > 0){
                foreach($oldOrderItems as $oldItem){
                    OrderItem::where([
                        'order_id' => $request->order_id,
                        'product_id' => $oldItem['product_id'],
                        ])->update($oldItem);
                }            
            }
        # END DB TRANSACTION STATEMENT HERE
        return response()->json([
            'success' => true, 
            'message' => 'Order updated successfully.',
            'orderID' => $request->order_id
        ]);
    }
}
