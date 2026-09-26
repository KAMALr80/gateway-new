<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_section_items', function (Blueprint $table) {
            $table->string('desktop_image_external_url', 2048)->nullable()->after('desktop_image_path');
            $table->string('mobile_image_external_url', 2048)->nullable()->after('mobile_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_section_items', function (Blueprint $table) {
            $table->dropColumn(['desktop_image_external_url', 'mobile_image_external_url']);
        });
    }
};
