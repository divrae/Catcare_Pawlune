<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::whereIn('role', User::STAFF_ROLES)->with('shifts')->orderBy('role')->orderBy('name')->get();

        return view('admin.staff', [
            'staff' => $staff,
            'roles' => collect(config('catcare.roles'))->only(User::STAFF_ROLES),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['phone' => Fmt::phone($request->input('phone')), 'email' => mb_strtolower((string) $request->input('email'))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:120|unique:users,email',
            'phone' => ['nullable', 'regex:/^08[0-9]{8,12}$/', 'unique:users,phone'],
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
        ], ['phone.regex' => 'Nomor HP tidak valid. Contoh: 081234567890']);

        $temp = Str::upper(Str::random(3)) . random_int(1000, 9999);
        $user = User::create($data + ['password' => Hash::make($temp), 'must_change_password' => true]);
        ActivityLog::record('staff_add', "Admin menambah {$user->roleLabel()} {$user->name}");

        return back()->with('success', "Akun {$user->name} dibuat.")->with('temp_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temp]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->isStaffMember(), 404);
        $request->merge(['phone' => Fmt::phone($request->input('phone')), 'email' => mb_strtolower((string) $request->input('email'))]);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^08[0-9]{8,12}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::in(User::STAFF_ROLES)],
        ], ['phone.regex' => 'Nomor HP tidak valid. Contoh: 081234567890']);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Kamu tidak bisa menurunkan role akunmu sendiri.']);
        }
        $user->update($data);
        ActivityLog::record('staff_edit', "Admin memperbarui akun {$user->name}");

        return back()->with('success', "Data {$user->name} diperbarui.");
    }

    public function resetPassword(User $user)
    {
        abort_unless($user->isStaffMember(), 404);
        $temp = Str::upper(Str::random(3)) . random_int(1000, 9999);
        $user->update(['password' => Hash::make($temp), 'must_change_password' => true]);
        ActivityLog::record('staff_reset', "Admin mereset password {$user->name}");

        return back()->with('success', "Password {$user->name} direset.")->with('temp_password', ['name' => $user->name, 'email' => $user->email, 'password' => $temp]);
    }

    public function toggle(Request $request, User $user)
    {
        abort_unless($user->isStaffMember(), 404);
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['staff' => 'Kamu tidak bisa menonaktifkan akunmu sendiri.']);
        }
        $user->update(['is_active' => ! $user->is_active]);
        ActivityLog::record('staff_toggle', ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan') . " akun {$user->name}");

        return back()->with('success', $user->is_active ? "{$user->name} diaktifkan." : "{$user->name} dinonaktifkan.");
    }
}
