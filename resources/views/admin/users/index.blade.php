@extends('layouts.cdr') @section('content')
<p class="eyebrow">ADMINISTRAÇÃO</p><h1>Usuários</h1><a class="button primary" href="{{ route('admin.users.create') }}">Criar usuário</a>
<div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Situação</th><th></th></tr></thead><tbody>@foreach($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role === 'admin' ? 'Administrador' : 'Cliente' }}</td><td>{{ $user->is_active ? 'Ativo' : 'Desativado' }}</td><td><a href="{{ route('admin.users.edit',$user) }}">Editar</a></td></tr>@endforeach</tbody></table></div>{{ $users->links() }}
@endsection
