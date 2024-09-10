<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IntervalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('intervals')->upsert([
            [
                'name' => __('day'),
                'slug' => 'day',
                'date_identifier' => 'day',
                'adverb' => __('daily'),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'name' => __('week'),
                'slug' => 'week',
                'date_identifier' => 'week',
                'adverb' => __('weekly'),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'name' => __('month'),
                'slug' => 'month',
                'date_identifier' => 'month',
                'adverb' => __('monthly'),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
            [
                'name' => __('year'),
                'slug' => 'year',
                'date_identifier' => 'year',
                'adverb' => __('yearly'),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ],
        ], ['slug'], ['name', 'date_identifier']);
    }
}
