<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $rental->booking_code }}</title>
</head>
<body style="margin: 0; padding: 40px 10px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <!-- Invisible Preheader -->
    <div style="display: none; max-height: 0px; overflow: hidden;">
        Pesanan Baru: #{{ $rental->booking_code }} | Total: Rp{{ number_format($rental->grand_total, 0, ',', '.') }} | Pelanggan: {{ $rental->nama }}
    </div>

    <div style="max-width: 500px; margin: 0 auto; background-color: white; color: #09090b; padding: 30px; border-radius: 12px; border: 1px solid #e4e4e7; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <p style="margin: 0; font-size: 12px; color: #71717a; font-weight: 600; text-transform: uppercase;">{{ config('app.name') }} OFFICIAL INVOICE</p>
            <h1 style="font-size: 26px; margin: 5px 0 0 0; font-weight: 800; color: #09090b;">#{{ $rental->booking_code }}</h1>
        </div>

        <div style="background-color: #fafafa; padding: 20px; border-radius: 8px; border: 1px solid #f4f4f5; margin-bottom: 25px;">
            <table style="width: 100%; font-size: 13px; color: #09090b; border-collapse: collapse;">
                <tr><td style="padding: 4px 0; color: #71717a; width: 110px;">Status</td><td style="font-weight: 700; color: #09090b;">: 
                    @if($rental->status == 'pending')
                        {{ (strtolower($rental->metode_pembayaran) == 'cash') ? 'BAYAR DI TEMPAT' : 'MENUNGGU PEMBAYARAN' }}
                    @else
                        {{ strtoupper($rental->status) }}
                    @endif
                </td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Metode Bayar</td><td style="font-weight: 600;">: {{ strtoupper($rental->metode_pembayaran ?: '-') }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Nama Pelanggan</td><td style="font-weight: 600;">: {{ strtoupper($rental->nama) }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">WhatsApp</td><td>: {{ $rental->no_wa }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Alamat Email</td><td>: {{ $rental->email ?: '-' }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">NIK</td><td>: {{ $rental->nik }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Alamat</td><td>: {{ strtoupper($rental->alamat ?: '-') }}</td></tr>
                <tr><td style="padding: 4px 0; color: #71717a;">Masa Sewa</td><td>: {{ \Carbon\Carbon::parse($rental->waktu_mulai)->format('d/m/Y H:i') }} <br> &nbsp; s/d {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d/m/Y H:i') }}</td></tr>
                @if($rental->applied_promo_name)
                <tr><td style="padding: 4px 0; color: #71717a;">Promo Applied</td><td style="color: #2563eb; font-weight: 700;">: {{ strtoupper($rental->applied_promo_name) }}</td></tr>
                @endif
                @if($rental->affiliate_code)
                <tr><td style="padding: 4px 0; color: #71717a;">Referral Code</td><td style="color: #16a34a; font-weight: 700;">: {{ strtoupper($rental->affiliate_code) }}</td></tr>
                @endif
            </table>
        </div>

        <div style="border-top: 1px dashed #e4e4e7; margin: 20px 0;"></div>

        <p style="margin: 0 0 10px 0; font-size: 12px; color: #71717a; font-weight: 700; text-transform: uppercase;">Rincian Item</p>
        <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
            @foreach($rental->items as $item)
            <tr>
                <td style="padding: 10px 0; color: #09090b; font-weight: 500;">{{ $item->unit->seri }}</td>
                <td style="padding: 10px 0; text-align: right; color: #09090b; font-weight: 600;">Rp{{ number_format($item->price_snapshot, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </table>

        <div style="border-top: 1px dashed #e4e4e7; margin: 20px 0;"></div>

        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
            <tr><td style="padding: 4px 0; color: #71717a;">Subtotal</td><td style="text-align: right; color: #09090b;">Rp{{ number_format($rental->subtotal_harga, 0, ',', '.') }}</td></tr>
            @if($rental->potongan_diskon > 0)
            <tr><td style="padding: 4px 0; color: #71717a;">Diskon Promo</td><td style="text-align: right; color: #dc2626;">-Rp{{ number_format($rental->potongan_diskon, 0, ',', '.') }}</td></tr>
            @endif
            @if($rental->kode_unik_pembayaran > 0)
            <tr><td style="padding: 4px 0; color: #71717a;">Kode Unik</td><td style="text-align: right; color: #2563eb;">+{{ $rental->kode_unik_pembayaran }}</td></tr>
            @endif
            <tr><td colspan="2" style="padding-top: 20px;"></td></tr>
            <tr style="font-weight: 800; font-size: 20px; color: #09090b;">
                <td>TOTAL</td>
                <td style="text-align: right;">Rp{{ number_format($rental->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div style="margin-top: 40px; text-align: center;">
            <a href="{{ route('public.payment', $rental->booking_code) }}" style="background-color: #09090b; color: #ffffff; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block;">Lihat Invoice</a>
        </div>

        <div style="text-align: center; margin-top: 50px; color: #a1a1aa; font-size: 11px;">
            <p>© {{ date('Y') }} RENT SPACE. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
