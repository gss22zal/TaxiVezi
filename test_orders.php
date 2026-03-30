<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Driver;
use App\Models\User;

$orders = Order::whereIn('status', ['new', 'accepted'])
    ->select('id', 'order_number', 'status', 'driver_id', 'created_at')
    ->take(10)
    ->get();

echo "Total orders with status 'new' or 'accepted': " . $orders->count() . "\n\n";

foreach ($orders as $o) {
    echo "ID: " . $o->id . " | Number: " . $o->order_number . " | Status: " . $o->status . " | driver_id: " . ($o->driver_id ?? 'NULL') . " | Created: " . $o->created_at . "\n";
}

echo "\n\n--- Driver info ---\n";
$user = User::where('email', 'driver1@taxivezi.ru')->first();
if ($user) {
    echo "User: " . $user->email . " (id: " . $user->id . ")\n";
    $driver = Driver::where('user_id', $user->id)->first();
    if ($driver) {
        echo "Driver ID: " . $driver->id . "\n";
        echo "is_online: " . $driver->is_online . "\n";
        echo "can_accept_orders: " . ($driver->can_accept_orders ?? 'NULL') . "\n";
    } else {
        echo "No driver record found\n";
    }
} else {
    echo "User not found\n";
}
