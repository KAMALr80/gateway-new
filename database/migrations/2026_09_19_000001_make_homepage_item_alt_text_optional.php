<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_section_items', function (Blueprint $table) {
            $table->string('alt_text')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('homepage_section_items', function (Blueprint $table) {
            $table->string('alt_text')->nullable(false)->change();
        });
    }
};
