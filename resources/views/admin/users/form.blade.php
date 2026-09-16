@extends('layouts.cdr') @section('content')
<p class="eyebrow">ADMINISTRAÇÃO</p><h1>{{ $user->exists ? 'Editar usuário' : 'Novo usuário' }}</h1>
<div class="card form-card"><form method="post" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">@csrf @if($user->exists)@method('PUT')@endif
<label>Nome<input name="name" value="{{ old('name',$user->name) }}" required></label><label>E-mail<input type="email" name="email" value="{{ old('email',$user->email) }}" required></label>
<label>Perfil<select name="role"><option value="client" @selected(old('role',$user->role)==='client')>Usuário/cliente</option><option value="admin" @selected(old('role',$user->role)==='admin')>Administrador</option></select></label>
<label>Senha {{ $user->exists ? '(deixe em branco para manter)' : '(mínimo 12 caracteres)' }}<input type="password" name="password" {{ $user->exists ? '' : 'required' }}></label><label>Confirme a senha<input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }}></label>
<label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$user->exists ? $user->is_active : true))> Conta ativa</label><button class="primary">Salvar</button></form></div>
@endsection
