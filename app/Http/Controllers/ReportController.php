<?php
namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->validate([
            'mode' => 'nullable|in:operator,locality,number', 'q' => 'nullable|string|max:120',
            'type' => 'nullable|in:all,sup,stfc,smp',
        ]);
        $mode = $filter['mode'] ?? 'operator';
        $q = trim($filter['q'] ?? '');
        $type = $filter['type'] ?? 'all';
        $datasets = Dataset::all()->keyBy('type');
        $results = [];
        $normal = null;
        if ($q !== '') {
            if ($mode === 'number') {
                $normal = preg_replace('/\D/', '', $q);
                if (str_starts_with($normal, '55') && in_array(strlen($normal), [12, 13], true)) $normal = substr($normal, 2);
                if (!in_array(strlen($normal), [10, 11], true)) throw ValidationException::withMessages(['q' => 'Informe DDD e número com 10 ou 11 dígitos (55 opcional).']);
            }
            foreach (['sup', 'stfc', 'smp'] as $set) {
                if ($type !== 'all' && $type !== $set) continue;
                if ($mode === 'number' && $set === 'sup') continue;
                if ($mode === 'locality' && $set === 'smp') continue;
                $active = $datasets[$set]->active_import_id ?? null;
                if (!$active) continue;
                $query = DB::table($set.'_datas')->where('import_id', $active);
                if ($mode === 'operator') {
                    $fields = $set === 'sup' ? ['prestadora_chamada', 'prestadora_sup', 'nome_sup'] : ['nome_prestadora', 'cnpj_prestadora'];
                    $query->where(function ($builder) use ($fields, $q) {
                        foreach ($fields as $field) $builder->orWhere($field, 'like', '%'.addcslashes($q, '%_\\').'%');
                    });
                } elseif ($mode === 'locality') {
                    $fields = $set === 'sup' ? ['descricao_localidade', 'descricao_municipio', 'cnl_localidade', 'cnl_municipio'] : ['nome_localidade', 'area_local', 'cod_area_local', 'cnl'];
                    $query->where(function ($builder) use ($fields, $q) {
                        foreach ($fields as $field) $builder->orWhere($field, 'like', '%'.addcslashes($q, '%_\\').'%');
                    });
                } else {
                    $code = substr($normal, 0, 2);
                    $prefix = substr($normal, 2, -4);
                    $suffix = substr($normal, -4);
                    $start = $set === 'stfc' ? 'mcdu_i' : 'faixa_inicial';
                    $end = $set === 'stfc' ? 'mcdu_f' : 'faixa_final';
                    $query->where('codigo_nacional', $code)->where('prefixo', $prefix)
                        ->where($start, '<=', $suffix)->where($end, '>=', $suffix);
                }
                $results[$set] = $query->orderBy('id')->paginate(25, ['*'], $set.'_page')->withQueryString();
            }
        }
        return view('reports.index', compact('mode', 'q', 'type', 'datasets', 'results', 'normal'));
    }
}
