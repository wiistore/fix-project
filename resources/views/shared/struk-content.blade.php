@php
    $transaksi = $transaksi ?? [];
    $items = $items ?? ($detailTransaksi ?? []);
    $authUser = $user ?? $currentUser ?? [];

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
    foreach ($items as $row) {
        $totalQty += (int) ($row['qty'] ?? 0);
    }
@endphp

<div class="struk-receipt-paper struk-print-area">
    <div class="struk-receipt-brand">
        <div class="struk-receipt-logo">
            <i class="ti ti-school"></i>
        </div>
        <h3>LAB KEWIRAUSAHAAN</h3>
        <strong>MTSN 8 BANYUWANGI</strong>
        <p>Jl. Pendidikan • Banyuwangi</p>
    </div>

    <div class="struk-divider"></div>

    <div class="struk-meta">
        <div>
            <span>No</span>
            <strong>{{ $kodeTransaksi }}</strong>
        </div>
        <div>
            <span>Tanggal</span>
            <strong>{{ $tanggal }}</strong>
        </div>
        <div>
            <span>Kasir</span>
            <strong>{{ ucwords((string) $kasir) }}</strong>
        </div>
        <div>
            <span>Metode</span>
            <strong>{{ $metodeBayar }}</strong>
        </div>
    </div>

    <div class="struk-divider"></div>

    <table class="struk-table">
        <thead>
            <tr>
                <th>Barang</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if (empty($items))
                <tr>
                    <td colspan="3" class="text-center">Tidak ada item.</td>
                </tr>
            @else
                @foreach ($items as $item)
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
                        <td class="text-center">{{ $qty }}</td>
                        <td class="text-end">{{ app_rupiah($sub) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="struk-divider"></div>

    <div class="struk-total">
        <div>
            <span>Total Item</span>
            <strong>{{ $totalQty }}</strong>
        </div>
        <div>
            <span>Total</span>
            <strong>{{ app_rupiah($totalJual) }}</strong>
        </div>
        <div>
            <span>Bayar</span>
            <strong>{{ app_rupiah($nominalBayar) }}</strong>
        </div>
        <div>
            <span>Kembalian</span>
            <strong>{{ app_rupiah($kembalian) }}</strong>
        </div>
    </div>

    <div class="struk-divider"></div>

    <div class="struk-receipt-footer">
        <strong>Terima kasih</strong>
        <p>Barang yang sudah dibeli tidak dapat dikembalikan tanpa persetujuan admin.</p>
    </div>
</div>
