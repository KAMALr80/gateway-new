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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('erp_product_id', 64)->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('simple');                 // simple, variable, digital etc.
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->decimal('regular_price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->boolean('manage_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();                    // size, color, etc.
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('in_stock');
            $table->index('category_id');
            $table->index('sub_category_id');
            $table->index('brand_id');
            $table->index('type');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
