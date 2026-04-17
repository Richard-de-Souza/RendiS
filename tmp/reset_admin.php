<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$user = User::where('email', 'admin@rendis.com')->first();
if ($user) {
    $user->password = Hash::make('password');
    $user->save();
    echo "Password reset for admin@rendis.com successful.\n";
} else {
    echo "User admin@rendis.com not found.\n";
}
