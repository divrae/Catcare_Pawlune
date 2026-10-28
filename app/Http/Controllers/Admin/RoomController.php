<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Room;
use App\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index()
    {
        return view('admin.rooms', [
            'rooms' => Room::with('tariff', 'currentStay.pet')->orderBy('room_number')->get(),
            'boardingTariffs' => Tariff::where('category', 'boarding')->orderBy('price')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_number' => 'required|string|max:20|unique:rooms,room_number',
            'name' => 'nullable|string|max:80',
            'room_type' => 'required|in:standard,deluxe,vip',
            'tariff_id' => ['required', Rule::exists('tariffs', 'id')->where('category', 'boarding')],
        ]);
        $room = Room::create($data);
        ActivityLog::record('room', "Kamar baru {$room->room_number}");

        return back()->with('success', "Kamar {$room->room_number} ditambahkan.");
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:20', Rule::unique('rooms', 'room_number')->ignore($room->id)],
            'name' => 'nullable|string|max:80',
            'room_type' => 'required|in:standard,deluxe,vip',
            'tariff_id' => ['required', Rule::exists('tariffs', 'id')->where('category', 'boarding')],
            'is_maintenance' => 'nullable|boolean',
        ]);
        $data['is_maintenance'] = $request->boolean('is_maintenance');

        if ($data['is_maintenance'] && $room->currentStay()->exists()) {
            return back()->withErrors(['room' => "Kamar {$room->room_number} sedang terisi, tidak bisa maintenance."]);
        }
        $room->update($data);

        return back()->with('success', "Kamar {$room->room_number} diperbarui.");
    }

    public function destroy(Room $room)
    {
        if ($room->boardings()->exists()) {
            return back()->withErrors(['room' => "Kamar {$room->room_number} punya riwayat boarding. Gunakan mode maintenance saja."]);
        }
        $room->delete();

        return back()->with('success', 'Kamar dihapus.');
    }
}
