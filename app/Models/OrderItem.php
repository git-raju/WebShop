<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 
        // 'customer_id',
        'product_id',
        'quantity',
        'item_price',
        'created_at',
        'updated_at'
    ];
}
