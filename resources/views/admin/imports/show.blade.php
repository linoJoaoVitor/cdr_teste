@extends('layouts.cdr') @section('content')
<p class="eyebrow">IMPORTAÇÃO {{ strtoupper($import->type) }}</p><h1>{{ $import->original_name }}</h1>
<div class="cards"><div class="card"><span>Status</span><h2>{{ $import->status }}</h2></div><div class="card"><span>Linhas lidas</span><h2>{{ $import->lines_read }}</h2></div><div class="card"><span>Importadas</span><h2>{{ $import->lines_imported }}</h2></div><div class="card"><span>Rejeitadas</span><h2>{{ $import->lines_rejected }}</h2></div></div>
<p>Início: {{ $import->started_at?->format('d/m/Y H:i') ?? 'Aguardando' }} · Fim: {{ $import->finished_at?->format('d/m/Y H:i') ?? 'Em andamento' }}</p>
@if($import->errors)<div class="error"><strong>Erros:</strong><ul>@foreach($import->errors as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<a class="button" href="{{ route('admin.imports.index') }}">Voltar</a>@if(in_array($import->status,['queued','processing']))<meta http-equiv="refresh" content="5">@endif
@endsection
