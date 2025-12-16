<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'username' => 'admin',
        ]);

        // Create default settings
        Setting::set('commission_rate', 5.0, 'float', 'finance');
        Setting::set('site_name', 'Marketplace', 'string', 'general');
        Setting::set('site_description', 'Digital Marketplace', 'string', 'general');

        // Create sample categories
        $categories = [
            [
                'slug' => 'digital-goods',
                'translations' => [
                    'ru' => ['name' => 'Цифровые товары', 'description' => 'Цифровые продукты и услуги'],
                    'uz' => ['name' => 'Raqamli mahsulotlar', 'description' => 'Raqamli mahsulotlar va xizmatlar'],
                ],
            ],
            [
                'slug' => 'gaming',
                'translations' => [
                    'ru' => ['name' => 'Игры', 'description' => 'Игровые товары и услуги'],
                    'uz' => ['name' => 'O\'yinlar', 'description' => 'O\'yin mahsulotlari va xizmatlari'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::create([
                'slug' => $categoryData['slug'],
                'is_active' => true,
            ]);

            foreach ($categoryData['translations'] as $locale => $translation) {
                $category->translations()->create([
                    'locale' => $locale,
                    ...$translation,
                ]);
            }
        }
    }
}
