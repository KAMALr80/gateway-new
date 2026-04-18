<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_status')->default('approved')->after('erp_contact_id');
            $table->string('approved_by')->nullable()->after('approval_status');
        });

        // Users registered via API after this migration should default to pending.
        // Existing users are already active and trusted — leave them approved.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approved_by']);
        });
    }
};
