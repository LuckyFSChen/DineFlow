<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.table_print_page_title', ['store' => $store->name]) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #0f172a;
            background: #f8fafc;
        }

        .toolbar {
            position: sticky;
            top: 0;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 20;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
        }

        .btn {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 0 16px 20px;
        }

        .grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            page-break-inside: avoid;
        }

        .table-no {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }

        .store-name {
            margin: 2px 0 6px;
            font-size: 11px;
            color: #475569;
        }

        .qr-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #ffffff;
            width: 130px;
            height: 130px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hint {
            margin-top: 6px;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            word-break: break-all;
        }

        .table-no-under-qr {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            color: #0f172a;
        }

        .empty {
            border: 1px dashed #94a3b8;
            border-radius: 12px;
            background: #ffffff;
            padding: 30px;
            text-align: center;
            color: #475569;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .container {
                margin: 0;
                max-width: none;
                padding: 0;
            }

            .grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 4mm;
            }

            .card {
                border-color: #cbd5e1;
            }
        }
    </style>
</head>
<body>
    @php
        $printCount = $tables->count() + ($takeout ? 1 : 0);
    @endphp
    <div class="toolbar">
        <div class="title">{{ __('admin.table_print_title', ['store' => $store->name, 'count' => $printCount]) }}</div>
        <button class="btn" type="button" onclick="window.print()">{{ __('admin.print') }}</button>
    </div>

    <div class="container">
        @if($tables->isEmpty() && ! $takeout)
            <div class="empty">{{ __('admin.table_print_empty') }}</div>
        @else
            <div class="grid">
                @if($takeout)
                    <div class="card">
                        <p class="table-no">{{ $takeout['table_no'] }}</p>
                        <p class="store-name">{{ $store->name }}</p>
                        <div class="qr-wrap">{!! $takeout['qr_svg'] !!}</div>
                        <p class="table-no-under-qr">{{ $takeout['table_no'] }}</p>
                        <p class="hint">{{ $takeout['menu_url'] }}</p>
                    </div>
                @endif
                @foreach($tables as $table)
                    <div class="card">
                        <p class="table-no">{{ $table->table_no }}</p>
                        <p class="store-name">{{ $store->name }}</p>
                        <div class="qr-wrap">{!! $table->qr_svg !!}</div>
                        <p class="table-no-under-qr">{{ $table->table_no }}</p>
                        <p class="hint">{{ $table->menu_url }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
