<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran #{{ $rental->booking_code }}</title>
</head>
<body style="margin: 0; padding: 40px 10px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="max-width: 500px; margin: 0 auto; background-color: white; color: #09090b; padding: 30px; border-radius: 12px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: inline-block; background-color: #f1f5f9; color: #0f172a; padding: 6px 14px; border-radius: 99px; font-size: 11px; font-weight: 700; margin-bottom: 15px; border: 1px solid #e2e8f0;">PAID / LUNAS</div>
            <h1 style="font-size: 26px; margin: 0; font-weight: 800; color: #09090b;">Pembayaran Diterima</h1>
            <p style="color: #71717a; font-size: 13px; margin: 5px 0 0 0;">Transaction ID: #{{ $rental->booking_code }}</p>
        </div>

        <div style="background-color: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #f4f4f5; margin-bottom: 25px;">
            <table style="width: 100%; font-size: 13px; color: #09090b; border-collapse: collapse;">
                <tr><td style="padding: 4px 0; color: #71717a; width: 110px;">Status Akuntansi</td><td style="font-weight: 700; color: #16a34a;">: LUNAS</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Metode Pembayaran</td><td style="font-weight: 600;">: {{ strtoupper($rental->metode_pembayaran ?: '-') }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Pemesan</td><td style="font-weight: 600;">: {{ strtoupper($rental->nama) }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">WhatsApp</td><td>: {{ $rental->no_wa }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Alamat Email</td><td>: {{ $rental->email ?: '-' }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">NIK</td><td>: {{ $rental->nik }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Waktu Rental</td><td>: {{ \Carbon\Carbon::parse($rental->waktu_mulai)->format('d/m/Y H:i') }} <br> &nbsp; s/d {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d/m/Y H:i') }}</td></tr>
                @if($rental->affiliate_code)
                <tr><td style="padding: 4px 0; color: #71717a;">Referral</td><td style="font-weight: 600;">: {{ strtoupper($rental->affiliate_code) }}</td></tr>
                @endif
            </table>
        </div>

        <div style="border-top: 1px dashed #e4e4e7; margin: 20px 0;"></div>

        <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
            @foreach($rental->items as $item)
            <tr>
                <td style="padding: 8px 0; color: #09090b; font-weight: 500;">{{ $item->unit->seri }}</td>
                <td style="padding: 8px 0; text-align: right; color: #09090b; font-weight: 600;">Rp{{ number_format($item->price_snapshot, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr><td colspan="2" style="padding-top: 20px;"></td></tr>
            <tr style="font-weight: 800; font-size: 20px; color: #16a34a;">
                <td>TOTAL LUNAS</td>
                <td style="text-align: right;">Rp{{ number_format($rental->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="border-top: 1px dashed #e4e4e7; margin: 20px 0;"></div>

        <p style="font-size: 13px; color: #71717a; line-height: 1.6; text-align: center; margin: 20px 0;">
            Invoice ini adalah bukti pembayaran yang sah. Silakan tunjukkan saat pengambilan unit.
        </p>

        <div style="margin-top: 30px; text-align: center;">
            <a href="{{ route('public.payment', $rental->booking_code) }}" style="background-color: #09090b; color: #ffffff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block;">Lihat Invoice</a>
        </div>

        <div style="text-align: center; margin-top: 40px; color: #a1a1aa; font-size: 11px;">
            <p>&copy; {{ date('Y') }} RENT SPACE. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
