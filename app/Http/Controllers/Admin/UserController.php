<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index() { return view('admin.users.index', ['users' => User::orderBy('name')->paginate(25)]); }
    public function create() { return view('admin.users.form', ['user' => new User]); }
    public function edit(User $user) { return view('admin.users.form', compact('user')); }
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users', 'role' => 'required|in:admin,client', 'password' => 'required|string|min:12|confirmed']);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        User::create($data);
        return redirect()->route('admin.users.index')->with('status', 'Usuário criado.');
    }
    public function update(Request $request, User $user)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)], 'role' => 'required|in:admin,client', 'password' => 'nullable|string|min:12|confirmed']);
        if ($user->id === $request->user()->id && ($data['role'] !== 'admin' || !$request->boolean('is_active'))) {
            return back()->withErrors(['role' => 'Você não pode remover seu próprio acesso administrativo.']);
        }
        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        $user->update($data);
        return redirect()->route('admin.users.index')->with('status', 'Usuário atualizado.');
    }
}
