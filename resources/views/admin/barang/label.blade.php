@php
    $title = $title ?? 'Cetak Label Barcode';
    $appName = config('app.name', 'Kopsis POS');
    $items = $items ?? [];
    $mode = $mode ?? 'single';
    $sourceBarang = $sourceBarang ?? null;
    $qty = $qty ?? count($items);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ $appName }}</title>

    <link href="{{ app_asset_versioned('assets/vendor/tabler-icons.min.css') }}" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #0f172a; }
        .label-toolbar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 24px; background: linear-gradient(135deg, #128048, #0e6a3a); color: #ffffff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); }
        .label-toolbar-left { display: flex; align-items: center; gap: 12px; }
        .label-toolbar-left h1 { margin: 0; font-size: 18px; font-weight: 700; }
        .label-toolbar-left p { margin: 0; font-size: 12px; opacity: 0.85; }
        .label-toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .label-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: transform 0.1s ease, opacity 0.2s ease; }
        .label-btn:hover { opacity: 0.92; }
        .label-btn:active { transform: scale(0.97); }
        .label-btn-primary { background: #ffffff; color: #128048; }
        .label-btn-soft { background: rgba(255, 255, 255, 0.18); color: #ffffff; }
        .label-btn-soft:hover { background: rgba(255, 255, 255, 0.28); }
        .label-qty-control { display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.15); padding: 6px 10px; border-radius: 8px; }
        .label-qty-control label { font-size: 12px; font-weight: 600; }
        .label-qty-control input { width: 64px; padding: 5px 8px; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; text-align: center; color: #0f172a; }
        .label-empty { text-align: center; padding: 80px 24px; color: #475569; }
        .label-empty i { font-size: 48px; margin-bottom: 12px; color: #94a3b8; }
        .label-sheet-wrap { max-width: 210mm; margin: 24px auto; padding: 0; background: #ffffff; box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08); border-radius: 4px; }
        .label-sheet { width: 210mm; min-height: 297mm; padding: 8mm 6mm; display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: minmax(30mm, auto); gap: 2mm; page-break-after: always; }
        .label-item { border: 1px dashed #94a3b8; border-radius: 4px; padding: 2mm 2mm; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; background: #ffffff; page-break-inside: avoid; break-inside: avoid; gap: 1mm; }
        .label-item-store { font-size: 7.5pt; font-weight: 700; color: #128048; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.1; margin: 0; }
        .label-item-name { font-size: 8pt; font-weight: 700; color: #0f172a; line-height: 1.15; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 18pt; word-break: break-word; }
        .label-item-barcode { margin: 0 auto; max-width: 100%; height: auto; }
        .label-item-code { font-family: 'Courier New', monospace; font-size: 7pt; font-weight: 600; color: #1e293b; letter-spacing: 0.5px; line-height: 1; margin: 0; }
        .label-item-price { font-size: 10pt; font-weight: 800; color: #128048; margin: 0; line-height: 1; }
        @media print {
            body { background: #ffffff; }
            .label-toolbar { display: none !important; }
            .label-sheet-wrap { max-width: none; margin: 0; box-shadow: none; border-radius: 0; }
            .label-sheet { margin: 0; padding: 8mm 6mm; gap: 3mm; }
            .label-item { border: 1px dashed #cbd5e1; }
            .label-item-store, .label-item-price { color: #0f172a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4 portrait; margin: 0; }
        }
        @media (max-width: 900px) {
            .label-sheet-wrap { margin: 12px; width: auto; max-width: none; overflow-x: auto; }
        }
    </style>
</head>
<body>

<div class="label-toolbar" data-label-toolbar>
    <div class="label-toolbar-left">
        <i class="ti ti-barcode" style="font-size: 26px;"></i>
        <div>
            <h1>Cetak Label Barcode</h1>
            <p>
                {{ count($items) }} label siap cetak
                @if ($mode === 'single' && $sourceBarang)
                    · {{ $sourceBarang['nama'] ?? '-' }}
                @else
                    · Mode bulk ({{ count($items) }} item)
                @endif
            </p>
        </div>
    </div>

    <div class="label-toolbar-right">
        @if ($mode === 'single' && $sourceBarang)
            <form action="{{ url('/admin/barang/label/'.($sourceBarang['id'] ?? '')) }}" method="GET" class="label-qty-control">
                <label for="qty">Jumlah:</label>
                <input type="number" id="qty" name="qty" value="{{ $qty }}" min="1" max="96" onchange="this.form.submit()">
            </form>
        @endif

        <button type="button" class="label-btn label-btn-primary" onclick="window.print()">
            <i class="ti ti-printer"></i>
            Cetak (Ctrl+P)
        </button>

        <a href="{{ url('/admin/barang') }}" class="label-btn label-btn-soft">
            <i class="ti ti-arrow-left"></i>
            Kembali
        </a>
    </div>
</div>

@if (empty($items))
    <div class="label-empty">
        <i class="ti ti-printer-off"></i>
        <h2>Tidak ada label untuk dicetak</h2>
        <p>Pilih barang dengan barcode valid dari halaman daftar barang.</p>
    </div>
@else
    @php
        $perSheet = 24;
        $sheets = array_chunk($items, $perSheet);
    @endphp

    @foreach ($sheets as $sheetItems)
        <div class="label-sheet-wrap">
            <div class="label-sheet">
                @foreach ($sheetItems as $item)
                    @php
                        $bc = trim((string) ($item['barcode'] ?? ''));
                    @endphp
                    @if ($bc !== '')
                        <div class="label-item">
                            <div class="label-item-store">{{ $appName }}</div>
                            <h3 class="label-item-name">{{ $item['nama'] ?? '-' }}</h3>
                            <svg class="label-item-barcode" data-label-barcode="{{ $bc }}"></svg>
                            <p class="label-item-code">{{ $bc }}</p>
                            <p class="label-item-price">{{ app_rupiah($item['harga_jual'] ?? 0) }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
@endif

<script src="{{ app_asset_versioned('assets/vendor/JsBarcode.all.min.js') }}"></script>
<script>
    (function () {
        'use strict';
        function renderAll() {
            if (typeof window.JsBarcode === 'undefined') return;
            var elements = document.querySelectorAll('[data-label-barcode]');
            elements.forEach(function (el) {
                var value = el.getAttribute('data-label-barcode') || '';
                if (!value) return;
                try {
                    window.JsBarcode(el, value, {
                        format: 'CODE128', width: 1.4, height: 38,
                        displayValue: false, margin: 2,
                        background: '#ffffff', lineColor: '#0f172a'
                    });
                } catch (e) {
                    el.outerHTML = '<span style="font-family:monospace;font-size:8pt;">' + value + '</span>';
                }
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderAll);
        } else {
            renderAll();
        }
        var params = new URLSearchParams(window.location.search);
        if (params.get('print') === '1') {
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 600);
            });
        }
    })();
</script>

</body>
</html>
