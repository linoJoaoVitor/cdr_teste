<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login'); }
    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (!Auth::attempt([...$credentials, 'is_active' => true])) {
            return back()->withErrors(['email' => 'Credenciais inválidas ou conta desativada.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->intended($request->user()->role === 'admin' ? route('admin.dashboard') : route('reports.index'));
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function forgotForm() { return view('auth.forgot'); }
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return back()->with('status', 'Se o endereço estiver cadastrado, você receberá um link de redefinição.');
    }
    public function resetForm(string $token, Request $request)
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }
    public function reset(Request $request)
    {
        $data = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|min:12|confirmed']);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });
        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', 'Senha redefinida.') : back()->withErrors(['email' => __($status)]);
    }
}
