<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peringatan Keterlambatan #{{ $rental->booking_code }}</title>
</head>
<body style="margin: 0; padding: 40px 10px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #fff1f2;">
    <div style="max-width: 500px; margin: 0 auto; background-color: white; color: #09090b; padding: 30px; border-radius: 12px; border: 1px solid #fecdd3; box-shadow: 0 10px 15px -3px rgba(159, 18, 57, 0.1);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: inline-block; background-color: #fff1f2; color: #e11d48; padding: 6px 14px; border-radius: 99px; font-size: 11px; font-weight: 800; margin-bottom: 15px; border: 1px solid #ffe4e6; text-transform: uppercase;">WAKTU HABIS / OVERDUE</div>
            <h1 style="font-size: 24px; margin: 0; font-weight: 800; color: #e11d48;">Anda Melewati Batas Waktu</h1>
            <p style="color: #64748b; font-size: 14px; margin: 10px 0 0 0;">Halo {{ $rental->nama }}, sistem mendeteksi bahwa waktu sewa unit Anda telah berakhir namun unit belum dikembalikan.</p>
        </div>

        <div style="background-color: #fef2f2; padding: 25px; border-radius: 12px; border: 2px solid #fee2e2; margin-bottom: 25px; text-align: center;">
            <p style="margin: 0; font-size: 12px; color: #991b1b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">Konsekuensi Keterlambatan</p>
            <h2 style="margin: 10px 0; font-size: 28px; color: #991b1b; font-weight: 900;">DENDA BERJALAN</h2>
            <p style="margin: 0; font-size: 13px; color: #b91c1c;">Harap segera mengembalikan unit ke lokasi kami sekarang juga untuk menghentikan akumulasi denda.</p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 13px;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">Booking Code</td>
                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 700;">#{{ $rental->booking_code }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">Jadwal Kembali</td>
                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; text-align: right; color: #e11d48; font-weight: 700;">{{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('H:i') }} WIB</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">Unit Belum Kembali</td>
                <td style="padding: 10px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">
                    @foreach($rental->units as $unit)
                        <div>{{ $unit->seri }} 
                            @if($unit->imei)
                                <span style="font-size: 11px; color: #64748b; font-family: monospace; font-weight: 400;">(...{{ substr($unit->imei, -4) }})</span>
                            @endif
                        </div>
                    @endforeach
                </td>
            </tr>
        </table>

        <div style="margin-top: 30px; text-align: center; space-x-10">
            <a href="https://wa.me/{{ $rental->admin_wa_setting }}" style="background-color: #e11d48; color: #ffffff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 800; font-size: 14px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.3);">Konfirmasi ke Admin</a>
        </div>

        <div style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 11px;">
            <p>Abaikan email ini jika Anda sudah berada di lokasi pengembalian.</p>
            <p>&copy; {{ date('Y') }} RENT SPACE. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
