<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');                  // no FK — snapshot record
            $table->unsignedBigInteger('erp_product_id')->nullable();
            $table->string('name');                                    // product name at time of order
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('unit_tax', 10, 2)->default(0);
            // total is calculated: (unit_price + unit_tax) * quantity
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('erp_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
