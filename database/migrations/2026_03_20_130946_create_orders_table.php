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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // ERP & order identity
            $table->unsignedBigInteger('erp_order_id')->nullable()->unique();
            $table->string('invoice_no')->nullable();
            $table->string('order_key')->nullable()->unique();
            $table->string('created_via')->nullable();                  // rest-api, checkout, etc.
            $table->string('version')->nullable();                      // API version that created order
            $table->timestamp('transaction_date')->nullable();

            // Relations
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('erp_customer_id')->nullable();  // ERP's customer/contact ID

            // Status
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('due');

            // Currency
            $table->string('currency', 3)->default('USD');

            // Amounts
            $table->decimal('line_total', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            // Billing address
            $table->string('billing_first_name')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_company')->nullable();
            $table->string('billing_address_1')->nullable();
            $table->string('billing_address_2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postcode')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();

            // Shipping address
            $table->string('shipping_first_name')->nullable();
            $table->string('shipping_last_name')->nullable();
            $table->string('shipping_company')->nullable();
            $table->string('shipping_address_1')->nullable();
            $table->string('shipping_address_2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postcode')->nullable();
            $table->string('shipping_country')->nullable();

            // Customer info
            $table->string('customer_ip_address')->nullable();
            $table->string('customer_user_agent')->nullable();
            $table->text('customer_note')->nullable();

            // Meta & extra data
            $table->json('meta_data')->nullable();

            // Sync tracking
            $table->enum('sync_status', [
                'pending',      // not yet sent to ERP
                'syncing',      // job in progress
                'synced',       // ERP confirmed
                'failed',       // all retries exhausted
            ])->default('pending');
            $table->integer('sync_attempts')->default(0);
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('payment_status');
            $table->index('user_id');
            $table->index('sync_status');
            $table->index(['sync_status', 'sync_attempts']);            // queue worker query
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
