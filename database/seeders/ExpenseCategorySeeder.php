<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'EXP-GAJI',
                'name' => 'Gaji Karyawan',
                'description' => 'Biaya operasional untuk pembayaran gaji dan upah karyawan bulanan.',
                'status' => 'active'
            ],
            [
                'code' => 'EXP-IKLAN',
                'name' => 'Biaya Iklan & Marketing',
                'description' => 'Biaya pengeluaran untuk iklan di platform digital (Shopee Ads, Meta, dll).',
                'status' => 'active'
            ],
            [
                'code' => 'EXP-PACKING',
                'name' => 'Bahan Packing & Lakban',
                'description' => 'Pembelian bahan pengemasan seperti bubble wrap, kardus, plastik, dan lakban.',
                'status' => 'active'
            ],
            [
                'code' => 'EXP-UTIL',
                'name' => 'Biaya Listrik, Air & Internet',
                'description' => 'Pembayaran tagihan utilitas bulanan.',
                'status' => 'active'
            ],
            [
                'code' => 'EXP-LAIN',
                'name' => 'Lain-lain',
                'description' => 'Pengeluaran tak terduga atau yang tidak masuk kategori lainnya.',
                'status' => 'active'
            ],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::firstOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
