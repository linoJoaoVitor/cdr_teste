<?php
namespace App\Jobs;

use App\Models\Dataset;
use App\Models\Import;
use App\Services\ImportParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessImport implements ShouldQueue
{
    use Queueable;
    public int $timeout = 3600;
    public int $tries = 1;
    public function __construct(public int $importId) {}
    public function handle(ImportParser $parser): void
    {
        $import = Import::findOrFail($this->importId);
        $lock = Cache::lock('cdr-import-'.$import->type, $this->timeout + 60);
        if (!$lock->get()) {
            $import->update(['status' => 'failed', 'errors' => ['Outro upload do mesmo conjunto está em processamento.'], 'finished_at' => now()]);
            return;
        }
        $table = $import->type.'_datas';
        try {
            $import->update(['status' => 'processing', 'started_at' => now()]);
            $batch = [];
            $read = 0;
            $written = 0;
            foreach ($parser->rows(Storage::disk('local')->path($import->private_path), $import->type) as [$lineNumber, $row]) {
                $read = $lineNumber;
                $batch[] = ['import_id' => $import->id, ...$row, 'created_at' => now(), 'updated_at' => now()];
                if (count($batch) === 500) {
                    DB::table($table)->insert($batch);
                    $written += count($batch);
                    $batch = [];
                    $import->update(['lines_read' => $read, 'lines_imported' => $written]);
                }
            }
            if ($batch) { DB::table($table)->insert($batch); $written += count($batch); }
            if ($written === 0) throw new \RuntimeException('Nenhum registro válido encontrado.');
            DB::transaction(function () use ($import, $written, $read) {
                Dataset::firstOrCreate(['type' => $import->type]);
                $dataset = Dataset::where('type', $import->type)->lockForUpdate()->firstOrFail();
                $dataset->update(['active_import_id' => $import->id, 'record_count' => $written, 'published_at' => now()]);
                $import->update(['status' => 'completed', 'lines_read' => $read, 'lines_imported' => $written, 'finished_at' => now()]);
            });
        } catch (Throwable $e) {
            preg_match('/^Linha (\d+):/', $e->getMessage(), $match);
            $import->update(['status' => 'failed', 'lines_read' => max($read, isset($match[1]) ? (int) $match[1] : 0), 'lines_imported' => 0, 'lines_rejected' => isset($match[1]) ? 1 : 0, 'errors' => [$e->getMessage()], 'finished_at' => now()]);
            DB::table($table)->where('import_id', $import->id)->delete();
        } finally { $lock->release(); }
    }
    public function failed(Throwable $e): void
    {
        $import = Import::find($this->importId);
        if (!$import || $import->status === 'completed') return;
        $import->update(['status' => 'failed', 'errors' => [$e->getMessage()], 'finished_at' => now()]);
        DB::table($import->type.'_datas')->where('import_id', $import->id)->delete();
    }
}
