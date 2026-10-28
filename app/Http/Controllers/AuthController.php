<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string|max:120',
            'password' => 'required|string',
        ]);

        $id = trim($data['identifier']);
        $user = str_contains($id, '@')
            ? User::where('email', mb_strtolower($id))->first()
            : User::where('phone', Fmt::phone($id))->first();

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['identifier' => __('auth.failed')]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['identifier' => 'Akun kamu dinonaktifkan. Hubungi admin.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($user->homeRoute());
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => Fmt::phone($request->input('phone'))]);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'regex:/^08[0-9]{8,12}$/'],
            'password' => 'required|string|min:6|confirmed',
            'address' => 'nullable|string|max:255',
        ], ['phone.regex' => 'Nomor HP tidak valid. Contoh: 081234567890']);

        $existing = User::where('phone', $data['phone'])->first();

        // Owner walk-in (didaftarkan dokter/staff, belum punya password) bisa "mengklaim" akunnya.
        if ($existing && ($existing->password !== null || $existing->role !== 'owner')) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP ini sudah terdaftar. Silakan masuk.']);
        }

        if ($existing) {
            $existing->update([
                'name' => $data['name'], 'password' => Hash::make($data['password']),
                'address' => $data['address'] ?? $existing->address, 'is_walk_in' => false,
            ]);
            $user = $existing;
        } else {
            $user = User::create([
                'name' => $data['name'], 'phone' => $data['phone'], 'role' => 'owner',
                'password' => Hash::make($data['password']), 'address' => $data['address'] ?? null,
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('owner.dashboard')->with('success', 'Selamat datang di Pawlune! Daftarkan anabulmu untuk mendapat Soulmate Pass.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
