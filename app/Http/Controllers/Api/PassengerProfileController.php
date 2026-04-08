<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PassengerProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        /** @var \App\Models\User $user */

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        // Проверка текущего пароля если меняем пароль
        if ($request->filled('new_password')) {
            if (!$request->filled('current_password')) {
                return response()->json(['error' => 'Введите текущий пароль'], 422);
            }
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Неверный текущий пароль'], 422);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'] ?? '';
        $user->phone = $validated['phone'] ?? '';
        $user->email = $validated['email'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Данные обновлены',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
            ]
        ]);
    }
}
