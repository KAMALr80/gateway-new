<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (! Schema::hasColumn('media', 'disk')) {
                $table->string('disk')->nullable()->after('url');
            }
            if (! Schema::hasColumn('media', 'path')) {
                $table->string('path')->nullable()->after('disk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $columns = array_values(array_filter(['disk', 'path'], fn (string $column) => Schema::hasColumn('media', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
