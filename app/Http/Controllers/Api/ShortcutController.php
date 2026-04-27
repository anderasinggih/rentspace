<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Unit;
use Illuminate\Http\Request;
use App\Models\UnitLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class ShortcutController extends Controller
{
    public function handleAction(Request $request)
    {
        \Log::info('Shortcut API Hit', [
            'raw_input' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'ip' => $request->ip()
        ]);
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
            'action' => 'required|string|in:complete,status,log_location,handover'
        ]);

        // 3. Cari Unit (Cari by ID dulu, prioritize exact match)
        $identifier = $request->unit_identifier;
        $unit = Unit::where('id', $identifier)
            ->orWhere('seri', $identifier)
            ->orWhere('seri', 'LIKE', '%' . $identifier . '%')
            ->first();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak ditemukan: ' . $identifier
            ], 404);
        }

        // 4. Cari Transaksi Aktif (Status 'paid' atau 'renting')
        $rental = Rental::whereHas('units', function ($query) use ($unit) {
                $query->where('units.id', $unit->id);
            })
            ->whereIn('status', ['paid', 'renting'])
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
                'message' => "Berhasil! Penyerahan unit {$unit->seri} dari penyewa {$rental->nama} telah SELESAI.",
                'completed_at' => Carbon::now()->format('H:i:s')
            ]);
        }

        // 5.b Eksekusi Aksi 'handover' (Validasi Ambil)
        if ($request->action === 'handover') {
            if (!$rental || $rental->status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak dalam status "Siap Ambil" atau tidak ada transaksi.'
                ], 400);
            }

            $rental->update([
                'status' => 'renting',
                'handed_over_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil! Unit {$unit->seri} telah divalidasi ambil oleh {$rental->nama}. Status sekarang: Sedang Disewa.",
                'handed_over_at' => Carbon::now()->format('H:i:s')
            ]);
        }

        // 6. Eksekusi Aksi 'log_location'
        if ($request->action === 'log_location') {
            // Flexible parameters: accept long or lng
            $lat = $request->lat;
            $lng = $request->long ?? $request->lng;

            if (!$lat || !$lng) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi tidak lengkap (lat/long required).'
                ], 422);
            }

            $address = $request->address;

            // Jika alamat kosong dari iPhone, coba cari di Web (Reverse Geocoding)
            if (!$address) {
                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'RentSpace-App-v1'
                    ])->get("https://nominatim.openstreetmap.org/reverse", [
                        'format' => 'json',
                        'lat' => $lat,
                        'lon' => $lng,
                        'zoom' => 18,
                        'addressdetails' => 1
                    ]);

                    if ($response->successful()) {
                        $address = $response->json('display_name');
                    }
                } catch (\Exception $e) {
                }
            }

            UnitLocation::create([
                'unit_id' => $unit->id,
                'lat' => $lat,
                'lng' => $lng,
                'address' => $address,
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
