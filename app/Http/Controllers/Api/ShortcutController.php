<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Unit;
use Illuminate\Http\Request;
use App\Models\UnitLocation;
use Carbon\Carbon;

class ShortcutController extends Controller
{
    public function handleAction(Request $request)
    {
        // 1. Validasi Token Keamanan
        $token = $request->header('X-Shortcut-Token');
        if (!$token || $token !== config('services.shortcut.token')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token tidak valid.'
            ], 401);
        }

        // 2. Validasi Input
        $request->validate([
            'unit_identifier' => 'required|string', // Bisa ID atau Seri Unit
            'action' => 'required|string|in:complete,status,log_location'
        ]);

        // 3. Cari Unit (Cari by ID dulu, kalau gagal cari by Seri)
        $unit = Unit::where('id', $request->unit_identifier)
            ->orWhere('seri', 'LIKE', '%' . $request->unit_identifier . '%')
            ->first();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak ditemukan: ' . $request->unit_identifier
            ], 404);
        }

        // 4. Cari Transaksi Aktif (Status 'paid' dan belum 'completed')
        $rental = Rental::whereHas('units', function ($query) use ($unit) {
                $query->where('units.id', $unit->id);
            })
            ->where('status', 'paid')
            ->whereNull('completed_at')
            ->latest()
            ->first();

        if ($request->action === 'status') {
            if (!$rental) {
                return response()->json([
                    'success' => true,
                    'is_rented' => false,
                    'unit' => $unit->seri,
                    'message' => 'Unit tersedia / tidak ada penyewa aktif.'
                ]);
            }

            return response()->json([
                'success' => true,
                'is_rented' => true,
                'unit' => $unit->seri,
                'tenant' => $rental->nama,
                'booking_code' => $rental->booking_code,
                'waktu_selesai' => $rental->waktu_selesai->format('d M Y, H:i'),
                'message' => "Disewa oleh: {$rental->nama}"
            ]);
        }

        // 5. Eksekusi Aksi 'complete' (Check-out)
        if ($request->action === 'complete') {
            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada transaksi aktif untuk unit ini.'
                ], 400);
            }

            $rental->update([
                'status' => 'completed',
                'completed_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil! Sewa unit {$unit->seri} oleh {$rental->nama} telah diselesaikan.",
                'completed_at' => Carbon::now()->format('H:i:s')
            ]);
        }

        // 6. Eksekusi Aksi 'log_location'
        if ($request->action === 'log_location') {
            $request->validate([
                'lat' => 'required',
                'long' => 'required'
            ]);

            UnitLocation::create([
                'unit_id' => $unit->id,
                'lat' => $request->lat,
                'lng' => $request->long,
                'address' => $request->address,
                'battery_level' => $request->battery_level
            ]);

            return response()->json([
                'success' => true,
                'message' => "Lokasi unit {$unit->seri} berhasil dicatat.",
                'recorded_at' => Carbon::now()->format('H:i:s')
            ]);
        }
    }
}
