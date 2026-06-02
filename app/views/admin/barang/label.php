
<?php
$title = $title ?? 'Cetak Label Barcode';
$activeMenu = $activeMenu ?? 'barang';

$items = $items ?? [];
$mode = $mode ?? 'single';
$sourceBarang = $sourceBarang ?? null;
$qty = $qty ?? count($items);

$appName = defined('APP_NAME') ? APP_NAME : 'Kopsis POS';

if (!function_exists('label_e')) {
    function label_e(mixed $value): string
    {
        if (function_exists('app_e')) {
            return app_e($value);
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('label_money')) {
    function label_money(mixed $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('label_app_base_url')) {
    function label_app_base_url(): string
    {
        if (defined('BASE_URL') && BASE_URL !== '') {
            return rtrim((string) BASE_URL, '/');
        }

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (str_contains($scriptName, '/public/index.php')) {
            return rtrim(str_replace('/public/index.php', '', $scriptName), '/');
        }

        if (str_contains($scriptName, '/index.php')) {
            return rtrim(str_replace('/index.php', '', $scriptName), '/');
        }

        return '';
    }
}

if (!function_exists('label_app_url')) {
    function label_app_url(string $path = ''): string
    {
        if (function_exists('app_url')) {
            return app_url($path);
        }

        $base = label_app_base_url();
        $path = '/' . ltrim($path, '/');

        return $base . $path;
    }
}

if (!function_exists('label_app_asset')) {
    function label_app_asset(string $path): string
    {
        if (function_exists('app_asset_versioned')) {
            return app_asset_versioned($path);
        }

        if (function_exists('app_asset')) {
            return app_asset($path);
        }

        $cleanPath = strtok($path, '?');
        $cleanPath = ltrim((string) $cleanPath, '/');

        $url = label_app_url($cleanPath);

        if (defined('PUBLIC_PATH')) {
            $diskPath = PUBLIC_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);

            if (is_file($diskPath)) {
                $mtime = filemtime($diskPath);
                if ($mtime !== false) {
                    $url .= '?v=' . $mtime;
                }
            }
        }

        return $url;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= label_e($title) ?> - <?= label_e($appName) ?></title>

    <link href="<?= label_app_asset('assets/vendor/tabler-icons.min.css') ?>" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Toolbar (hidden saat print) */
        .label-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 24px;
            background: linear-gradient(135deg, #128048, #0e6a3a);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .label-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .label-toolbar-left h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .label-toolbar-left p {
            margin: 0;
            font-size: 12px;
            opacity: 0.85;
        }

        .label-toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .label-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.1s ease, opacity 0.2s ease;
        }

        .label-btn:hover {
            opacity: 0.92;
        }

        .label-btn:active {
            transform: scale(0.97);
        }

        .label-btn-primary {
            background: #ffffff;
            color: #128048;
        }

        .label-btn-soft {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }

        .label-btn-soft:hover {
            background: rgba(255, 255, 255, 0.28);
        }

        .label-qty-control {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 10px;
            border-radius: 8px;
        }

        .label-qty-control label {
            font-size: 12px;
            font-weight: 600;
        }

        .label-qty-control input {
            width: 64px;
            padding: 5px 8px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            color: #0f172a;
        }

        .label-empty {
            text-align: center;
            padding: 80px 24px;
            color: #475569;
        }

        .label-empty i {
            font-size: 48px;
            margin-bottom: 12px;
            color: #94a3b8;
        }

        /* A4 sheet container */
        .label-sheet-wrap {
            max-width: 210mm;
            margin: 24px auto;
            padding: 0;
            background: #ffffff;
            box-shadow: 0 8px 32px rgba(15, 23, 42, 0.08);
            border-radius: 4px;
        }

        .label-sheet {
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 6mm;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-auto-rows: minmax(30mm, auto);
            gap: 2mm;
            page-break-after: always;
        }

        /* Single label item */
        .label-item {
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            padding: 2mm 2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #ffffff;
            page-break-inside: avoid;
            break-inside: avoid;
            gap: 1mm;
        }

        .label-item-store {
            font-size: 7.5pt;
            font-weight: 700;
            color: #128048;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
            margin: 0;
        }

        .label-item-name {
            font-size: 8pt;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.15;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 18pt;
            word-break: break-word;
        }

        .label-item-barcode {
            margin: 0 auto;
            max-width: 100%;
            height: auto;
        }

        .label-item-code {
            font-family: 'Courier New', monospace;
            font-size: 7pt;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: 0.5px;
            line-height: 1;
            margin: 0;
        }

        .label-item-price {
            font-size: 10pt;
            font-weight: 800;
            color: #128048;
            margin: 0;
            line-height: 1;
        }

        /* Print-only styles */
        @media print {
            body {
                background: #ffffff;
            }

            .label-toolbar {
                display: none !important;
            }

            .label-sheet-wrap {
                max-width: none;
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .label-sheet {
                margin: 0;
                padding: 8mm 6mm;
                gap: 3mm;
            }

            .label-item {
                border: 1px dashed #cbd5e1;
            }

            .label-item-store,
            .label-item-price {
                color: #0f172a !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }

        /* Mobile / Tablet preview */
        @media (max-width: 900px) {
            .label-sheet-wrap {
                margin: 12px;
                width: auto;
                max-width: none;
                overflow-x: auto;
            }
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
                <?= label_e((string) count($items)) ?> label siap cetak
                <?php if ($mode === 'single' && $sourceBarang): ?>
                    · <?= label_e($sourceBarang['nama'] ?? '-') ?>
                <?php else: ?>
                    · Mode bulk (<?= label_e((string) count($items)) ?> item)
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="label-toolbar-right">
        <?php if ($mode === 'single' && $sourceBarang): ?>
            <form action="<?= label_e(label_app_url('/admin/barang/label/' . ($sourceBarang['id'] ?? ''))) ?>" method="GET" class="label-qty-control">
                <label for="qty">Jumlah:</label>
                <input
                    type="number"
                    id="qty"
                    name="qty"
                    value="<?= label_e((string) $qty) ?>"
                    min="1"
                    max="96"
                    onchange="this.form.submit()"
                >
            </form>
        <?php endif; ?>

        <button type="button" class="label-btn label-btn-primary" onclick="window.print()">
            <i class="ti ti-printer"></i>
            Cetak (Ctrl+P)
        </button>

        <a
            href="<?= label_e(label_app_url('/admin/barang')) ?>"
            class="label-btn label-btn-soft"
        >
            <i class="ti ti-arrow-left"></i>
            Kembali
        </a>
    </div>
</div>

<?php if (empty($items)): ?>
    <div class="label-empty">
        <i class="ti ti-printer-off"></i>
        <h2>Tidak ada label untuk dicetak</h2>
        <p>Pilih barang dengan barcode valid dari halaman daftar barang.</p>
    </div>
<?php else: ?>
    <?php
    // 24 label per sheet (4 kolom x 6 baris)
    $perSheet = 24;
    $sheets = array_chunk($items, $perSheet);
    ?>

    <?php foreach ($sheets as $sheetItems): ?>
        <div class="label-sheet-wrap">
            <div class="label-sheet">
                <?php foreach ($sheetItems as $item): ?>
                    <?php
                    $barcode = trim((string) ($item['barcode'] ?? ''));
                    if ($barcode === '') {
                        continue;
                    }
                    $namaBarang = (string) ($item['nama'] ?? '-');
                    $hargaJual = (float) ($item['harga_jual'] ?? 0);
                    ?>

                    <div class="label-item">
                        <div class="label-item-store"><?= label_e($appName) ?></div>

                        <h3 class="label-item-name"><?= label_e($namaBarang) ?></h3>

                        <svg
                            class="label-item-barcode"
                            data-label-barcode="<?= label_e($barcode) ?>"
                        ></svg>

                        <p class="label-item-code"><?= label_e($barcode) ?></p>

                        <p class="label-item-price"><?= label_e(label_money($hargaJual)) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script src="<?= label_app_asset('assets/vendor/JsBarcode.all.min.js') ?>"></script>
<script>
    (function () {
        'use strict';

        function renderAll() {
            if (typeof window.JsBarcode === 'undefined') {
                return;
            }

            var elements = document.querySelectorAll('[data-label-barcode]');
            elements.forEach(function (el) {
                var value = el.getAttribute('data-label-barcode') || '';
                if (!value) return;

                try {
                    window.JsBarcode(el, value, {
                        format: 'CODE128',
                        width: 1.4,
                        height: 38,
                        displayValue: false,
                        margin: 2,
                        background: '#ffffff',
                        lineColor: '#0f172a'
                    });
                } catch (e) {
                    // Kalau format gagal (misal value invalid), tampilin teks fallback
                    el.outerHTML = '<span style="font-family:monospace;font-size:8pt;">' + value + '</span>';
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderAll);
        } else {
            renderAll();
        }

        // Auto print kalau URL ada ?print=1
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
