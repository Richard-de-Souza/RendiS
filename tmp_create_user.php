<?php
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$role = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
User::updateOrCreate(
    ['email' => 'testbrowser@example.com'],
    [
        'name' => 'Test Browser',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'salary' => 5000,
        'cpf' => '12345678901' // Adding a fake CPF just in case
    ]
);
echo "User created successfully\n";
