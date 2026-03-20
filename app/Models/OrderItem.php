<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'erp_product_id',
        'name',
        'sku',
        'quantity',
        'unit_price',
        'unit_tax',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'unit_tax'   => 'decimal:2',
    ];

    // Dynamically calculated — never stored
    public function getTotalAttribute(): float
    {
        return ($this->unit_price + $this->unit_tax) * $this->quantity;
    }

    public function getLineTaxAttribute(): float
    {
        return $this->unit_tax * $this->quantity;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Soft reference — product may change after order placed
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}