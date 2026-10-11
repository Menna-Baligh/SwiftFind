<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $categories = [
            'Electronics', 'Laptops & Computers', 'Smartphones',
            'Home Appliances', 'Fashion & Clothing', 'Sports & Fitness',
            'Books & Stationery', 'Gaming Consoles', 'Beauty & Care', 'Automotive'
        ];

        foreach ($categories as $categoryName) {
            Category::firstOrCreate(['name' => $categoryName]);
        }

        $categoryIds = Category::pluck('id')->toArray();

        $prefixes = ['Pro', 'Ultra', 'Gaming', 'Smart', 'Wireless', 'Portable', 'Compact', 'Heavy Duty'];
        $items    = ['Laptop', 'Phone', 'Monitor', 'Headphones', 'Camera', 'Stand', 'Desk', 'Chair', 'Watch', 'Charger'];
        $words    = ['powerful', 'durable', 'sleek', 'fast', 'high quality', 'ergonomic', 'aluminum', 'premium'];

        $totalRecords = 1000000;
        $chunkSize    = 5000;
        $chunks       = $totalRecords / $chunkSize;

        for ($c = 0; $c < $chunks; $c++) {
            $data = [];

            for ($i = 0; $i < $chunkSize; $i++) {
                $name = $prefixes[array_rand($prefixes)] . ' ' . $items[array_rand($items)] . ' ' . Str::random(5);
                $desc = 'A ' . $words[array_rand($words)] . ' device designed for gaming, office work, and daily efficiency.';

                $data[] = [
                    'category_id'    => $categoryIds[array_rand($categoryIds)],
                    'name'           => $name,
                    'description'    => $desc,
                    'price'          => rand(100, 50000) / 10,
                    'stock_quantity' => rand(0, 100) > 20 ? rand(1, 500) : 0,
                    'created_at'     => now()->subDays(rand(1, 365))->toDateTimeString(),
                    'updated_at'     => now()->toDateTimeString(),
                ];
            }

            DB::table('products')->insert($data);
        }
    }
}
