<?php

namespace Database\Seeders;

use App\Enums\FinanceFlowType;
use App\Models\FinanceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Gaji',
                'flow_type' => FinanceFlowType::Income,
                'icon' => 'briefcase-business',
                'color' => '#16A34A',
            ],
            [
                'name' => 'Uang Saku',
                'flow_type' => FinanceFlowType::Income,
                'icon' => 'wallet',
                'color' => '#22C55E',
            ],
            [
                'name' => 'Bonus',
                'flow_type' => FinanceFlowType::Income,
                'icon' => 'sparkles',
                'color' => '#84CC16',
            ],
            [
                'name' => 'Penjualan',
                'flow_type' => FinanceFlowType::Income,
                'icon' => 'badge-dollar-sign',
                'color' => '#14B8A6',
            ],
            [
                'name' => 'Pengembalian Dana',
                'flow_type' => FinanceFlowType::Income,
                'icon' => 'rotate-ccw',
                'color' => '#06B6D4',
            ],
            [
                'name' => 'Pemasukan Lainnya',
                'flow_type' => FinanceFlowType::Income,
                'icon' => 'circle-plus',
                'color' => '#0EA5E9',
            ],

            [
                'name' => 'Makanan & Minuman',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'utensils',
                'color' => '#F97316',
            ],
            [
                'name' => 'Transportasi',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'car',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Belanja',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'shopping-bag',
                'color' => '#EC4899',
            ],
            [
                'name' => 'Tagihan',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'receipt-text',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Pendidikan',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'graduation-cap',
                'color' => '#6366F1',
            ],
            [
                'name' => 'Kesehatan',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'heart-pulse',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Hiburan',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'gamepad-2',
                'color' => '#A855F7',
            ],
            [
                'name' => 'Biaya Admin',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'landmark',
                'color' => '#64748B',
            ],
            [
                'name' => 'Pengeluaran Lainnya',
                'flow_type' => FinanceFlowType::Expense,
                'icon' => 'circle-minus',
                'color' => '#F43F5E',
            ],
        ];

        User::query()
            ->where('is_active', true)
            ->each(function (User $user) use ($categories): void {
                foreach ($categories as $index => $data) {
                    $category = FinanceCategory::withTrashed()
                        ->firstOrNew([
                            'user_id' => $user->id,
                            'name' => $data['name'],
                            'flow_type' => $data['flow_type']->value,
                        ]);

                    $category->fill([
                        'icon' => $data['icon'],
                        'color' => $data['color'],
                        'is_system' => true,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]);

                    if ($category->exists && $category->trashed()) {
                        $category->restore();
                    }

                    $category->save();
                }
            });
    }
}
