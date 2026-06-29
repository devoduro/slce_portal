<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create degree classifications with Ghanaian university standards
        $classifications = [
            [
                'name' => 'First Class Honours',
                'min_cgpa' => 3.60,
                'max_cgpa' => 4.00,
            ],
            [
                'name' => 'Second Class Honours (Upper Division)',
                'min_cgpa' => 3.00,
                'max_cgpa' => 3.59,
            ],
            [
                'name' => 'Second Class Honours (Lower Division)',
                'min_cgpa' => 2.50,
                'max_cgpa' => 2.99,
            ],
            [
                'name' => 'Third Class Honours',
                'min_cgpa' => 2.00,
                'max_cgpa' => 2.49,
            ],
            [
                'name' => 'Pass',
                'min_cgpa' => 1.50,
                'max_cgpa' => 1.99,
            ],
            [
                'name' => 'Fail',
                'min_cgpa' => 0.00,
                'max_cgpa' => 1.49,
            ],
        ];

        foreach ($classifications as $classification) {
            Classification::firstOrCreate(
                ['name' => $classification['name']],
                $classification
            );
        }
    }
}
