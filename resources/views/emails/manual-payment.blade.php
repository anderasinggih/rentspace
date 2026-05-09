<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran #{{ $rental->booking_code }}</title>
</head>
<body style="margin: 0; padding: 40px 10px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="max-width: 500px; margin: 0 auto; background-color: white; color: #09090b; padding: 30px; border-radius: 12px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <p style="margin: 0; font-size: 12px; color: #f59e0b; font-weight: 700; text-transform: uppercase;">⚠️ PERLU VERIFIKASI PEMBAYARAN</p>
            <h1 style="font-size: 26px; margin: 5px 0 0 0; font-weight: 800; color: #09090b;">#{{ $rental->booking_code }}</h1>
        </div>

        <div style="background-color: #fffbeb; padding: 20px; border-radius: 8px; border: 1px solid #fef3c7; margin-bottom: 25px;">
            <p style="margin: 0; font-size: 14px; color: #92400e; line-height: 1.6;">
                Pelanggan telah melaporkan pembayaran via <strong>QRIS MANUAL</strong>. Silakan cek mutasi masuk dan lakukan validasi di dashboard admin.
            </p>
        </div>

        <div style="background-color: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #f4f4f5; margin-bottom: 25px;">
            <table style="width: 100%; font-size: 13px; color: #09090b; border-collapse: collapse;">
                <tr><td style="padding: 4px 0; color: #71717a; width: 110px;">Nama Pelanggan</td><td style="font-weight: 600;">: {{ strtoupper($rental->nama) }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">WhatsApp</td><td>: {{ $rental->no_wa }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Total Tagihan</td><td style="font-weight: 700; color: #2563eb;">: Rp{{ number_format($rental->grand_total, 0, ',', '.') }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Unit Sewa</td><td>: {{ $rental->units->pluck('seri')->implode(', ') }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Masa Sewa</td><td>: {{ \Carbon\Carbon::parse($rental->waktu_mulai)->format('d/m/Y H:i') }} s/d {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d/m/Y H:i') }}</td></tr>
            </table>
        </div>

        <div style="margin-top: 40px; text-align: center;">
            <a href="{{ route('admin.monitoring') }}" style="background-color: #09090b; color: #ffffff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block;">Cek Monitoring</a>
        </div>

        <div style="text-align: center; margin-top: 50px; color: #a1a1aa; font-size: 11px;">
            <p>© {{ date('Y') }} RENT SPACE. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
