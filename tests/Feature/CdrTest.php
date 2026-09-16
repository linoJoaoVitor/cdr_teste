<?php
namespace Tests\Feature;

use App\Jobs\ProcessImport;
use App\Models\Dataset;
use App\Models\Import;
use App\Models\User;
use App\Services\ImportParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CdrTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_open_admin_and_deactivated_user_loses_access(): void
    {
        $client = User::factory()->create(['role' => 'client', 'is_active' => true]);
        $this->actingAs($client)->get('/admin')->assertForbidden();
        $this->actingAs($client)->get('/relatorios')->assertOk();
        $client->update(['is_active' => false]);
        $this->actingAs($client)->get('/relatorios')->assertRedirect('/entrar');
    }

    public function test_failed_stfc_import_keeps_previous_batch_and_other_dataset(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Painel administrativo');
        $this->actingAs($admin)->get('/admin/importacoes')->assertOk()->assertSee('Novo upload');
        $this->actingAs($admin)->get('/admin/usuarios')->assertOk()->assertSee('Criar usuário');
        $good = $this->makeImport($admin, 'stfc', 'stfc.csv', "nome_prestadora;cnpj_prestadora;codigo_nacional;prefixo;mcdu_i;mcdu_f;cnl;nome_localidade;area_local;cod_area_local\nAlfa;1;01;2345;0000;0999;00012;Cidade;Area;001\n");
        (new ProcessImport($good->id))->handle(app(ImportParser::class));
        $this->assertSame('completed', $good->fresh()->status, json_encode($good->fresh()->errors));
        $this->assertSame($good->id, Dataset::find('stfc')->active_import_id);
        $this->assertSame('01', DB::table('stfc_datas')->first()->codigo_nacional);
        Dataset::create(['type' => 'smp', 'record_count' => 10]);
        $bad = $this->makeImport($admin, 'stfc', 'bad.csv', "nome_prestadora;cnpj_prestadora;codigo_nacional;prefixo;mcdu_i;mcdu_f;cnl;nome_localidade;area_local;cod_area_local\nBeta;1;01;2345;0999;0000;00012;Cidade;Area;001\n");
        (new ProcessImport($bad->id))->handle(app(ImportParser::class));
        $this->assertSame('failed', $bad->fresh()->status);
        $this->assertSame($good->id, Dataset::find('stfc')->active_import_id);
        $this->assertSame(10, Dataset::find('smp')->record_count);
        $this->actingAs($admin)->get('/relatorios?mode=number&q=0123450500')->assertOk()->assertSee('00012')->assertSee('Alfa');
        $replacement = $this->makeImport($admin, 'stfc', 'new.csv', "nome_prestadora;cnpj_prestadora;codigo_nacional;prefixo;mcdu_i;mcdu_f;cnl;nome_localidade;area_local;cod_area_local\nGama;1;01;2345;0400;0600;00014;Nova;Area;001\n");
        (new ProcessImport($replacement->id))->handle(app(ImportParser::class));
        $this->assertSame($replacement->id, Dataset::find('stfc')->active_import_id);
        $this->actingAs($admin)->get('/relatorios?mode=number&q=0123450500')->assertOk()->assertSee('Gama')->assertDontSee('Alfa');
    }

    public function test_smp_result_never_invents_cnl(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $import = $this->makeImport($admin, 'smp', 'smp.csv', "nome_prestadora;cnpj_prestadora;codigo_nacional;prefixo;faixa_inicial;faixa_final;status\nMovel;1;11;98765;0000;9999;A\n");
        (new ProcessImport($import->id))->handle(app(ImportParser::class));
        $this->assertSame('completed', $import->fresh()->status, json_encode($import->fresh()->errors));
        $this->actingAs($admin)->get('/relatorios?mode=number&q=11987654321')->assertOk()->assertSee('Não disponível no SMP')->assertSee('Movel');
    }

    private function makeImport(User $user, string $type, string $name, string $contents): Import
    {
        $path = 'imports/'.$name;
        Storage::disk('local')->put($path, $contents);
        return Import::create(['user_id' => $user->id, 'type' => $type, 'original_name' => $name, 'private_path' => $path, 'status' => 'queued']);
    }
}
