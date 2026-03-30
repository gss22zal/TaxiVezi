<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Driver;
use App\Models\User;

$user = User::where('role', 'driver')->first();
if ($user) {
    $driver = Driver::where('user_id', $user->id)->first();
    if ($driver) {
        echo "Driver ID: " . $driver->id . "\n";
        echo "is_online: " . $driver->is_online . "\n";
        echo "can_accept_orders: " . $driver->can_accept_orders . "\n";
    } else {
        echo "Driver record not found for user_id: " . $user->id . "\n";
    }
} else {
    echo "No driver users found\n";
}
