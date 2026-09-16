@extends('layouts.cdr')
@section('title','Painel · CDR Teste')
@section('content')
<p class="eyebrow">ADMINISTRAÇÃO</p><h1>Painel administrativo</h1><p class="lead">Disponibilidade dos conjuntos e últimas cargas.</p>
<div class="cards">@foreach(['sup'=>'SUP','stfc'=>'CADUP / STFC','smp'=>'CADUP / SMP'] as $key=>$label)
<article class="card"><span class="eyebrow">{{ $label }}</span><h2>{{ number_format($datasets[$key]->record_count ?? 0,0,',','.') }} registros</h2>
<p>{{ isset($datasets[$key]) && $datasets[$key]->published_at ? 'Publicado em '.$datasets[$key]->published_at->format('d/m/Y H:i') : 'Sem importação publicada' }}</p>
<p>Última tentativa: {{ isset($lastImports[$key]) ? $lastImports[$key]->status.' · '.$lastImports[$key]->created_at->format('d/m/Y H:i') : 'Nenhuma' }}</p></article>@endforeach</div>
<div class="actions"><a class="button primary" href="{{ route('admin.imports.index') }}">Enviar arquivo</a><a class="button" href="{{ route('admin.users.index') }}">Gerenciar usuários</a></div>
<h2>Importações recentes</h2><div class="table-wrap"><table><thead><tr><th>Conjunto</th><th>Arquivo</th><th>Responsável</th><th>Status</th><th>Data</th></tr></thead><tbody>@forelse($imports as $item)<tr><td>{{ strtoupper($item->type) }}</td><td><a href="{{ route('admin.imports.show',$item) }}">{{ $item->original_name }}</a></td><td>{{ $item->user->name }}</td><td>{{ $item->status }}</td><td>{{ $item->created_at->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="5">Nenhuma importação.</td></tr>@endforelse</tbody></table></div>
@endsection
