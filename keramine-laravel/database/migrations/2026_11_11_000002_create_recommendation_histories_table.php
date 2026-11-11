<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan_title', 160);
            $table->string('nama_toko', 160)->nullable();
            $table->string('pof_name', 64)->nullable();
            $table->string('basket_type', 32)->default('store_month');
            $table->string('item_level', 32)->default('SERI');
            $table->json('trigger_items');
            $table->json('recommendations_json');
            $table->string('source_mode', 32)->default('fastapi');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['basket_type', 'item_level']);
            $table->index('nama_toko');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_histories');
    }
};

