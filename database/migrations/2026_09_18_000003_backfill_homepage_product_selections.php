<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('homepage_sections')->where('type', 'product_carousel')->orderBy('id')->each(function ($section) {
            $settings = json_decode($section->settings ?: '{}', true) ?: [];
            if (array_key_exists('product_ids', $settings)) {
                return;
            }

            $limit = min(max((int) ($settings['limit'] ?? 14), 1), 50);
            $offset = min(max((int) ($settings['offset'] ?? 0), 0), 500);
            $query = DB::table('products')->where('is_active', true)->where('in_stock', true)->whereNull('parent_id');
            match ($settings['filter'] ?? 'new') {
                'sale' => $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'regular_price')->orderByDesc('updated_at'),
                'featured' => $query->orderByDesc('updated_at'),
                default => $query->orderByDesc('created_at'),
            };
            $settings['product_ids'] = $query->skip($offset)->limit($limit)->pluck('id')->map(fn ($id) => (int) $id)->all();
            unset($settings['filter'], $settings['offset']);

            DB::table('homepage_sections')->where('id', $section->id)->update(['settings' => json_encode($settings)]);
        });
    }

    public function down(): void
    {
        DB::table('homepage_sections')->where('type', 'product_carousel')->orderBy('id')->each(function ($section) {
            $settings = json_decode($section->settings ?: '{}', true) ?: [];
            unset($settings['product_ids']);
            DB::table('homepage_sections')->where('id', $section->id)->update(['settings' => json_encode($settings)]);
        });
    }
};
