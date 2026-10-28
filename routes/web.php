<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardingController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\GroomingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

// ---------- Publik ----------
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.post');
});

// ---------- Wajib login ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/ganti-password', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/ganti-password', [ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('/struk/{invoice}', [ReceiptController::class, 'show'])->name('receipt.show');

    // ===== Owner =====
    Route::middleware('role:owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/', [OwnerController::class, 'dashboard'])->name('dashboard');
        Route::get('/poll', [OwnerController::class, 'poll'])->name('poll');
        Route::post('/notices/read', [OwnerController::class, 'readNotices'])->name('notices.read');

        Route::post('/pets', [OwnerController::class, 'storePet'])->name('pets.store');
        Route::put('/pets/{pet}', [OwnerController::class, 'updatePet'])->name('pets.update');
        Route::delete('/pets/{pet}', [OwnerController::class, 'destroyPet'])->name('pets.destroy');
        Route::get('/pets/{pet}/pass', [OwnerController::class, 'pass'])->name('pets.pass');

        Route::post('/antrean', [OwnerController::class, 'storeQueue'])->name('queue.store');
        Route::post('/antrean/{visit}/batal', [OwnerController::class, 'cancelQueue'])->name('queue.cancel');

        Route::get('/kunjungan/{visit}', [OwnerController::class, 'showVisit'])->name('visits.show');
        Route::get('/boarding/{boarding}', [OwnerController::class, 'showBoarding'])->name('boarding.show');
        Route::post('/testimoni', [OwnerController::class, 'storeTestimonial'])->name('testimonial.store');
    });

    // ===== Dokter =====
    Route::middleware('role:doctor')->prefix('dokter')->name('doctor.')->group(function () {
        Route::get('/', [DoctorController::class, 'dashboard'])->name('dashboard');
        Route::get('/antrean', [DoctorController::class, 'queueFragment'])->name('queue');
        Route::post('/walk-in', [DoctorController::class, 'walkIn'])->name('walkin');
        Route::post('/kunjungan/{visit}/panggil', [DoctorController::class, 'call'])->name('call');
        Route::post('/kunjungan/{visit}/mulai', [DoctorController::class, 'start'])->name('start');
        Route::post('/kunjungan/{visit}/tidak-hadir', [DoctorController::class, 'noShow'])->name('noshow');
        Route::get('/kunjungan/{visit}/periksa', [DoctorController::class, 'exam'])->name('exam');
        Route::put('/kunjungan/{visit}/periksa', [DoctorController::class, 'saveExam'])->name('exam.save');
        Route::get('/rekam-medis', [DoctorController::class, 'history'])->name('history');
    });

    // ===== Boarding (staff) =====
    Route::middleware('role:staff')->prefix('boarding')->name('boarding.')->group(function () {
        Route::get('/', [BoardingController::class, 'index'])->name('index');
        Route::get('/rekap', [BoardingController::class, 'recap'])->name('recap');
        Route::post('/walk-in', [BoardingController::class, 'walkIn'])->name('walkin');
        Route::post('/kunjungan/{visit}/panggil', [BoardingController::class, 'call'])->name('call');
        Route::post('/{boarding}/check-in', [BoardingController::class, 'checkIn'])->name('checkin');
        Route::post('/{boarding}/check-out', [BoardingController::class, 'checkOut'])->name('checkout');
        Route::post('/{boarding}/grooming', [BoardingController::class, 'orderGrooming'])->name('grooming');
        Route::post('/{boarding}/catatan', [BoardingController::class, 'storeLog'])->name('logs.store');
        Route::get('/{boarding}', [BoardingController::class, 'show'])->name('show');
        Route::get('/{boarding}/cetak', [BoardingController::class, 'print'])->name('print');
    });

    // ===== Grooming (groomer) =====
    Route::middleware('role:groomer')->prefix('grooming')->name('grooming.')->group(function () {
        Route::get('/', [GroomingController::class, 'index'])->name('index');
        Route::get('/antrean', [GroomingController::class, 'queueFragment'])->name('queue');
        Route::get('/riwayat', [GroomingController::class, 'history'])->name('history');
        Route::post('/walk-in', [GroomingController::class, 'walkIn'])->name('walkin');
        Route::get('/{order}', [GroomingController::class, 'work'])->name('work');
        Route::post('/{order}/panggil', [GroomingController::class, 'call'])->name('call');
        Route::post('/{order}/mulai', [GroomingController::class, 'start'])->name('start');
        Route::post('/{order}/selesai', [GroomingController::class, 'finish'])->name('finish');
        Route::post('/{order}/batal', [GroomingController::class, 'cancel'])->name('cancel');
    });

    // ===== Kasir / POS =====
    Route::middleware('role:cashier')->prefix('kasir')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/riwayat', [PosController::class, 'history'])->name('history');
        Route::post('/{visit}/item', [PosController::class, 'addItem'])->name('item.add');
        Route::delete('/{visit}/item/{service}', [PosController::class, 'removeItem'])->name('item.remove');
        Route::post('/{visit}/bayar', [PosController::class, 'pay'])->name('pay');
    });

    // ===== Admin =====
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::put('/staff/{user}', [Admin\StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{user}/reset', [Admin\StaffController::class, 'resetPassword'])->name('staff.reset');
        Route::post('/staff/{user}/toggle', [Admin\StaffController::class, 'toggle'])->name('staff.toggle');

        Route::get('/shift', [Admin\ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shift', [Admin\ShiftController::class, 'save'])->name('shifts.save');
        Route::post('/shift/salin', [Admin\ShiftController::class, 'copyPrevious'])->name('shifts.copy');

        Route::get('/tarif', [Admin\TariffController::class, 'index'])->name('tariffs.index');
        Route::post('/tarif', [Admin\TariffController::class, 'store'])->name('tariffs.store');
        Route::put('/tarif/{tariff}', [Admin\TariffController::class, 'update'])->name('tariffs.update');
        Route::delete('/tarif/{tariff}', [Admin\TariffController::class, 'destroy'])->name('tariffs.destroy');

        Route::get('/obat', [Admin\MedicineController::class, 'index'])->name('medicines.index');
        Route::post('/obat', [Admin\MedicineController::class, 'store'])->name('medicines.store');
        Route::put('/obat/{medicine}', [Admin\MedicineController::class, 'update'])->name('medicines.update');
        Route::post('/obat/{medicine}/stok', [Admin\MedicineController::class, 'stock'])->name('medicines.stock');
        Route::delete('/obat/{medicine}', [Admin\MedicineController::class, 'destroy'])->name('medicines.destroy');

        Route::get('/kamar', [Admin\RoomController::class, 'index'])->name('rooms.index');
        Route::post('/kamar', [Admin\RoomController::class, 'store'])->name('rooms.store');
        Route::put('/kamar/{room}', [Admin\RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/kamar/{room}', [Admin\RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::get('/pelanggan', [Admin\CustomerController::class, 'index'])->name('customers.index');
        Route::get('/testimoni', [Admin\TestimonialController::class, 'index'])->name('testimonials.index');
        Route::post('/testimoni/{testimonial}/toggle', [Admin\TestimonialController::class, 'toggle'])->name('testimonials.toggle');
        Route::delete('/testimoni/{testimonial}', [Admin\TestimonialController::class, 'destroy'])->name('testimonials.destroy');

        Route::get('/rekap', [Admin\RecapController::class, 'index'])->name('recap.index');
        Route::get('/rekap/cetak', [Admin\RecapController::class, 'print'])->name('recap.print');
        Route::get('/rekap/pdf', [Admin\RecapController::class, 'pdf'])->name('recap.pdf');
    });
});
