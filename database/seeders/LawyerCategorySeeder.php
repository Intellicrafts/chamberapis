<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LawyerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Criminal Law', 'Family Law', 'Corporate Law', 'Intellectual Property', 
            'Real Estate Law', 'Tax Law', 'Immigration Law', 'Labor Law', 
            'Environmental Law', 'Constitutional Law', 'Civil Rights Law',
            'Personal Injury', 'Medical Malpractice', 'Bankruptcy', 'Estate Planning'
        ];
        
        foreach ($categories as $category) {
            // Check if category already exists
            if (!\App\Models\LawyerCategory::where('category_name', $category)->exists()) {
                \App\Models\LawyerCategory::create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'category_name' => $category,
                    'lawyer_id' => null
                ]);
            }
        }
    }
}
