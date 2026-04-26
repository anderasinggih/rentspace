<x-mail::message>
# Pesanan Baru Masuk!

Halo Admin, ada pesanan baru yang baru saja dibuat oleh pelanggan. Berikut adalah ringkasan detail pesanannya:

## Detail Pelanggan
- **Nama:** {{ $rental->nama }}
- **WhatsApp:** {{ $rental->no_wa }}
- **NIK:** {{ $rental->nik }}
- **Alamat:** {{ $rental->alamat }}

## Detail Sewa
- **Kode Booking:** #{{ $rental->booking_code }}
- **Waktu Mulai:** {{ \Carbon\Carbon::parse($rental->waktu_mulai)->format('d M Y H:i') }}
- **Waktu Selesai:** {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d M Y H:i') }}
@if($rental->applied_promo_name)
- **Promo:** {{ $rental->applied_promo_name }}
@endif
@if($rental->affiliate_code)
- **Referral:** {{ $rental->affiliate_code }}
@endif

## Unit yang Disewa
<x-mail::table>
| Unit | Harga (Snapshot) |
| :--- | :--- |
@foreach($rental->items as $item)
| {{ $item->unit->seri }} | Rp{{ number_format($item->price_snapshot, 0, ',', '.') }} |
@endforeach
</x-mail::table>

## Ringkasan Pembayaran
- **Subtotal:** Rp{{ number_format($rental->subtotal_harga, 0, ',', '.') }}
- **Diskon:** Rp{{ number_format($rental->potongan_diskon, 0, ',', '.') }}
- **Kode Unik:** {{ $rental->kode_unik_pembayaran }}
- **Total Bayar:** **Rp{{ number_format($rental->grand_total, 0, ',', '.') }}**

<x-mail::button :url="route('public.payment', $rental->booking_code)">
Buka Struk Pesanan
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
