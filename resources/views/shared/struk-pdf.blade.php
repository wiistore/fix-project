@php
    $transaksi = $transaksi ?? [];
    $detailTransaksi = $detailTransaksi ?? ($items ?? []);
    $authUser = $user ?? [];

    $kodeTransaksi = (string) ($transaksi['kode_transaksi'] ?? $transaksi['kode'] ?? '-');
    $tanggalRaw = $transaksi['tanggal'] ?? $transaksi['created_at'] ?? date('Y-m-d H:i:s');
    $tanggal = app_date($tanggalRaw, 'd/m/Y H:i');

    $kasir = $transaksi['nama_kasir']
        ?? $transaksi['kasir']
        ?? $transaksi['nama_user']
        ?? app_user_name($authUser);

    $metodeBayar = strtoupper((string) ($transaksi['metode_bayar'] ?? $transaksi['metode_pembayaran'] ?? '-'));
    $totalJual = (float) ($transaksi['total_jual'] ?? $transaksi['total'] ?? 0);
    $nominalBayar = (float) ($transaksi['nominal_bayar'] ?? $totalJual);
    $kembalian = (float) ($transaksi['kembalian'] ?? max(0, $nominalBayar - $totalJual));

    $totalQty = 0;
    foreach ($detailTransaksi as $row) {
        $totalQty += (int) ($row['qty'] ?? 0);
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $kodeTransaksi }}</title>
    <style>
        @page { margin: 8px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: Arial, sans-serif; font-size: 10px; }
        .receipt { width: 100%; }
        .brand { text-align: center; }
        .brand h1 { margin: 0; font-size: 13px; font-weight: 800; letter-spacing: .3px; }
        .brand strong { display: block; margin-top: 2px; font-size: 10px; }
        .brand p { margin: 3px 0 0; color: #555; font-size: 9px; }
        .divider { margin: 8px 0; border-top: 1px dashed #999; }
        .meta div, .summary div { display: table; width: 100%; margin-bottom: 3px; }
        .meta span, .summary span { display: table-cell; color: #555; }
        .meta strong, .summary strong { display: table-cell; text-align: right; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { padding-bottom: 4px; border-bottom: 1px dashed #999; text-align: left; font-size: 9px; }
        td { padding: 5px 0; border-bottom: 1px dotted #ddd; vertical-align: top; }
        td strong { display: block; font-size: 10px; }
        td small { display: block; color: #555; font-size: 8px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .footer { text-align: center; }
        .footer strong { display: block; margin-bottom: 3px; font-size: 10px; }
        .footer p { margin: 0; color: #555; font-size: 8px; }
    </style>
</head>
<body>
<div class="receipt">
    <div class="brand">
        <h1>LAB KEWIRAUSAHAAN</h1>
        <strong>MTSN 8 BANYUWANGI</strong>
        <p>Jl. Pendidikan • Banyuwangi</p>
    </div>

    <div class="divider"></div>

    <div class="meta">
        <div><span>No</span><strong>{{ $kodeTransaksi }}</strong></div>
        <div><span>Tanggal</span><strong>{{ $tanggal }}</strong></div>
        <div><span>Kasir</span><strong>{{ ucwords((string) $kasir) }}</strong></div>
        <div><span>Metode</span><strong>{{ $metodeBayar }}</strong></div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Barang</th>
                <th class="center">Qty</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($detailTransaksi))
                <tr><td colspan="3" class="center">Tidak ada item.</td></tr>
            @else
                @foreach ($detailTransaksi as $item)
                    @php
                        $namaB = (string) ($item['nama_barang'] ?? $item['nama'] ?? '-');
                        $kodeB = (string) ($item['kode_barang'] ?? $item['kode'] ?? '');
                        $qty = (int) ($item['qty'] ?? 0);
                        $hj = (float) ($item['harga_jual'] ?? 0);
                        $sub = (float) ($item['subtotal_jual'] ?? ($qty * $hj));
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $namaB }}</strong>
                            @if ($kodeB !== '')
                                <small>{{ $kodeB }}</small>
                            @endif
                            <small>{{ $qty }} x {{ app_rupiah($hj) }}</small>
                        </td>
                        <td class="center">{{ $qty }}</td>
                        <td class="right">{{ app_rupiah($sub) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="summary">
        <div><span>Total Item</span><strong>{{ $totalQty }}</strong></div>
        <div><span>Total</span><strong>{{ app_rupiah($totalJual) }}</strong></div>
        <div><span>Bayar</span><strong>{{ app_rupiah($nominalBayar) }}</strong></div>
        <div><span>Kembalian</span><strong>{{ app_rupiah($kembalian) }}</strong></div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <strong>Terima kasih</strong>
        <p>Barang yang sudah dibeli tidak dapat dikembalikan tanpa persetujuan admin.</p>
    </div>
</div>
</body>
</html>
