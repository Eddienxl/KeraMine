<?php

namespace Tests\Feature;

use App\Models\RecommendationHistory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KeraMinePortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_all_core_pages_render_cleanly_with_local_clean_fallback(): void
    {
        Http::fake(['*' => Http::response(null, 503)]);

        $this->get('/')->assertStatus(200)->assertSee('KeraMine B2B');
        $this->get('/stores')->assertStatus(200)->assertSee('237');
        $this->get('/stores?tab=catalog&level=SERI')->assertStatus(200)->assertSee('DIEGO');
        $this->get('/recommender?trigger_items=DIEGO&item_level=SERI&run=1')
            ->assertStatus(200)
            ->assertSee('GRANIT')
            ->assertSee('LOCAL_FALLBACK');
        $this->get('/rules?basket_type=store_month&item_level=SERI&fdr_only=1')
            ->assertStatus(200)
            ->assertSee('CUBIC')
            ->assertSee('MIRANDA');
        $this->get('/history')->assertStatus(200)->assertSee('ABAD BANGUNAN');
        $this->get('/pipeline')->assertStatus(200)->assertSee('FastAPI ARM Engine');
        $this->get('/login')->assertStatus(200)->assertSee('admin@keramine.id');
    }

    public function test_locale_switcher_toggles_between_indonesian_and_english(): void
    {
        Http::fake(['*' => Http::response(null, 503)]);

        $this->get('/?lang=en')
            ->assertStatus(200)
            ->assertSee('Launch Recommender')
            ->assertSee('Browse 237 Stores');

        $this->get('/?lang=id')
            ->assertStatus(200)
            ->assertSee('Buka Mesin Rekomendasi')
            ->assertSee('Profil 237 Toko');
    }

    public function test_quick_login_works_and_prioritizes_sales_pof_stores(): void
    {
        Http::fake(['*' => Http::response(null, 503)]);

        $this->post('/login', ['quick_role' => 'dian'])
            ->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertSame('DIAN', auth()->user()->pof_name);

        $this->get('/stores')->assertStatus(200)->assertSee('ABAD BANGUNAN, TOKO');

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_recommender_saves_plan_exports_csv_and_respects_delete_ownership(): void
    {
        Http::fake(['*' => Http::response(null, 503)]);

        $dian = User::query()->where('email', 'dian@keramine.id')->firstOrFail();
        $robby = User::query()->where('email', 'robby@keramine.id')->firstOrFail();
        $admin = User::query()->where('email', 'admin@keramine.id')->firstOrFail();

        $this->actingAs($dian)->post('/recommender', [
            'save_plan' => 1,
            'nama_toko' => 'ABAD BANGUNAN, TOKO',
            'basket_type' => 'store_month',
            'item_level' => 'SERI',
            'trigger_items' => 'DIEGO',
            'top_k' => 5,
            'plan_title' => 'Paket Uji Test Feature',
            'notes' => 'Verifikasi SQLite CRUD & CSV',
        ])->assertRedirect('/history');

        $plan = RecommendationHistory::query()->where('plan_title', 'Paket Uji Test Feature')->firstOrFail();
        $this->assertSame($dian->id, $plan->user_id);

        // CSV Export succeeds cleanly
        $csvResp = $this->get("/history/{$plan->id}/export-csv");
        $csvResp->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $csvResp->headers->get('content-type'));
        $content = $csvResp->streamedContent();
        $this->assertStringContainsString('GRANIT', $content);

        // Another sales rep cannot delete Dian's plan
        $this->actingAs($robby)->delete("/history/{$plan->id}")
            ->assertRedirect('/history')
            ->assertSessionHas('error');
        $this->assertDatabaseHas('recommendation_histories', ['id' => $plan->id]);

        // Admin can delete cleanly
        $this->actingAs($admin)->delete("/history/{$plan->id}")
            ->assertRedirect('/history')
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('recommendation_histories', ['id' => $plan->id]);
    }

    public function test_pipeline_simulation_and_reset_forward_payloads_to_fastapi_or_n8n(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/api/simulate-transactions' => Http::response([
                'status' => 'success',
                'batch_tag' => 'n8n_sim_test',
                'inserted_transactions' => 2,
            ], 200),
            'http://127.0.0.1:5678/webhook/keramine-ingest' => Http::response([
                'status' => 'success',
                'batch_tag' => 'n8n_hook_test',
                'inserted' => 2,
            ], 200),
            'http://127.0.0.1:8001/api/reset-demo' => Http::response([
                'status' => 'ok',
                'restored_rules' => 784,
            ], 200),
            '*' => Http::response(null, 503),
        ]);

        $this->post('/pipeline/simulate', [
            'channel' => 'fastapi',
            'target_toko' => 'ABAD BANGUNAN, TOKO',
            'target_pof' => 'DIAN',
            'bundle_seris' => 'DIEGO, GRANIT',
            'qty_per_item' => 60,
            'repeat_baskets' => 1,
        ])->assertRedirect('/pipeline')->assertSessionHas('status');

        $this->post('/pipeline/simulate', [
            'channel' => 'n8n',
            'target_toko' => 'ABAD BANGUNAN, TOKO',
            'target_pof' => 'DIAN',
            'bundle_seris' => 'CUBIC, MIRANDA',
            'qty_per_item' => 50,
            'repeat_baskets' => 1,
        ])->assertRedirect('/pipeline')->assertSessionHas('status');

        $this->post('/pipeline/reset')
            ->assertRedirect('/pipeline')
            ->assertSessionHas('status');
    }
}

