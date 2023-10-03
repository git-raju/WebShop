<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 
        'customer',
        'product_id',
        'payed',
        'created_at',
        'updated_at'
    ];
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
