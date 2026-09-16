<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'cdr:create-admin {email} {name}';
    protected $description = 'Cria o primeiro administrador sem cadastro público';

    public function handle(): int
    {
        $password = $this->secret('Senha inicial (mínimo 12 caracteres)');
        if (strlen((string) $password) < 12) { $this->error('Senha muito curta.'); return self::FAILURE; }
        if (User::where('email', $this->argument('email'))->exists()) { $this->error('E-mail já cadastrado.'); return self::FAILURE; }
        User::create(['name' => $this->argument('name'), 'email' => $this->argument('email'), 'password' => Hash::make($password), 'role' => 'admin', 'is_active' => true]);
        $this->info('Administrador criado.');
        return self::SUCCESS;
    }
}
