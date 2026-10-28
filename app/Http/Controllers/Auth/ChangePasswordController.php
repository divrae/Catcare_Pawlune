<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:6|confirmed|different:current_password',
        ], ['new_password.different' => 'Password baru harus berbeda dari password lama.']);

        $user = $request->user();
        $user->update(['password' => Hash::make($data['new_password']), 'must_change_password' => false]);

        return redirect($user->homeRoute())->with('success', 'Password berhasil diganti.');
    }
}
