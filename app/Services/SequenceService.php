<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SequenceService
{
    public function next(string $name): int
    {
        DB::table('sequences')->upsert(
            ['name' => $name, 'value' => 1],
            ['name'],
            ['value' => DB::raw('value + 1')]
        );

        return DB::table('sequences')
            ->where('name', $name)
            ->value('value');
    }
}