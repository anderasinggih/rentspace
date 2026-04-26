<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Pengembalian #{{ $rental->booking_code }}</title>
</head>
<body style="margin: 0; padding: 40px 10px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc;">
    <div style="max-width: 500px; margin: 0 auto; background-color: white; color: #09090b; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: inline-block; background-color: #fffbeb; color: #b45309; padding: 6px 14px; border-radius: 99px; font-size: 11px; font-weight: 700; margin-bottom: 15px; border: 1px solid #fef3c7; text-transform: uppercase; letter-spacing: 0.05em;">PENGINGAT PENGEMBALIAN</div>
            <h1 style="font-size: 24px; margin: 0; font-weight: 800; color: #09090b;">Waktu Anda Hampir Habis</h1>
            <p style="color: #64748b; font-size: 14px; margin: 10px 0 0 0;">Halo {{ $rental->nama }}, harap segera mempersiapkan pengembalian unit agar terhindar dari denda keterlambatan.</p>
        </div>

        <div style="background-color: #fffcf0; padding: 20px; border-radius: 8px; border: 1px solid #ffedd5; margin-bottom: 25px;">
            <table style="width: 100%; font-size: 13px; color: #09090b; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px 0; color: #9a3412; font-weight: 700;">Jadwal Selesai</td>
                    <td style="padding: 6px 0; font-weight: 800; text-align: right; color: #c2410c;">: {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('H:i') }} WIB</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #71717a;">Tanggal</td>
                    <td style="padding: 6px 0; text-align: right;">: {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #71717a;">Status Booking</td>
                    <td style="padding: 6px 0; text-align: right; font-weight: 600;">: AKTIF / DALAM SEWA</td>
                </tr>
            </table>
        </div>

        <div style="border-top: 1px dashed #e2e8f0; margin: 20px 0;"></div>

        <h4 style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.025em;">Unit yang Disewa:</h4>
        <div style="space-y-2">
            @foreach($rental->units as $unit)
            <div style="padding: 10px; background-color: #f8fafc; border-radius: 6px; border: 1px solid #f1f5f9; margin-bottom: 8px;">
                <span style="font-size: 13px; font-weight: 600; color: #0f172a;">{{ $unit->seri }}</span>
            </div>
            @endforeach
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="{{ route('public.payment', $rental->booking_code) }}" style="background-color: #b45309; color: #ffffff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 14px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(180, 83, 9, 0.2);">Lihat Detail Sewa</a>
        </div>

        <div style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 11px;">
            <p>Abaikan email ini jika Anda sudah berada di lokasi pengembalian.</p>
            <p>&copy; {{ date('Y') }} RENT SPACE. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
