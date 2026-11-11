<?php

namespace Database\Seeders;

use App\Models\RecommendationHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@keramine.id'],
            [
                'name' => 'Admin KeraMine',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'pof_name' => 'ALL',
            ]
        );

        $dian = User::updateOrCreate(
            ['email' => 'dian@keramine.id'],
            [
                'name' => 'Dian Pratama (Sales POF)',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'pof_name' => 'DIAN',
            ]
        );

        User::updateOrCreate(
            ['email' => 'robby@keramine.id'],
            [
                'name' => 'Robby Wijaya (Sales POF)',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'pof_name' => 'ROBBY',
            ]
        );

        if (RecommendationHistory::query()->count() === 0) {
            RecommendationHistory::create([
                'user_id' => $dian->id,
                'plan_title' => 'Restock Paket Keramik Lantai Toko Abad Bangunan (Seri DIEGO)',
                'nama_toko' => 'ABAD BANGUNAN, TOKO',
                'pof_name' => 'DIAN',
                'basket_type' => 'store_month',
                'item_level' => 'SERI',
                'trigger_items' => ['DIEGO'],
                'recommendations_json' => [
                    [
                        'recommended_item' => 'GRANIT',
                        'source_type' => 'arm_rule',
                        'antecedents_str' => 'DIEGO',
                        'confidence' => 0.1667,
                        'lift' => 3.9649,
                        'zhang_metric' => 0.7605,
                        'utility_score' => 0.2564,
                        'p_value_fisher' => 0.0001,
                    ],
                    [
                        'recommended_item' => 'DOT',
                        'source_type' => 'arm_rule',
                        'antecedents_str' => 'DIEGO',
                        'confidence' => 0.1061,
                        'lift' => 2.1479,
                        'zhang_metric' => 0.5435,
                        'utility_score' => 0.1610,
                        'p_value_fisher' => 0.0023,
                    ],
                    [
                        'recommended_item' => 'DAKOTA',
                        'source_type' => 'arm_rule',
                        'antecedents_str' => 'DIEGO',
                        'confidence' => 0.1212,
                        'lift' => 1.7928,
                        'zhang_metric' => 0.4497,
                        'utility_score' => 0.1420,
                        'p_value_fisher' => 0.0110,
                    ],
                ],
                'source_mode' => 'seed_verified',
                'notes' => 'Rencana cross-selling B2B bulanan berbasis rule SOTA tervalidasi Fisher & Benjamini-Hochberg.',
            ]);

            RecommendationHistory::create([
                'user_id' => $admin->id,
                'plan_title' => 'Bundling Seri Premium CUBIC → MIRANDA (Lift Tertinggi 4.44x)',
                'nama_toko' => 'GUNUNG MAS JAYA, TOKO',
                'pof_name' => 'BARI',
                'basket_type' => 'store_month',
                'item_level' => 'SERI',
                'trigger_items' => ['CUBIC'],
                'recommendations_json' => [
                    [
                        'recommended_item' => 'MIRANDA',
                        'source_type' => 'arm_rule',
                        'antecedents_str' => 'CUBIC',
                        'confidence' => 0.1935,
                        'lift' => 4.4437,
                        'zhang_metric' => 0.7811,
                        'utility_score' => 0.3102,
                        'p_value_fisher' => 0.00001,
                    ],
                ],
                'source_mode' => 'seed_verified',
                'notes' => 'Top-1 utility rule pada level SERI (Zhang Metric +0.781, FDR-significant).',
            ]);
        }
    }
}
