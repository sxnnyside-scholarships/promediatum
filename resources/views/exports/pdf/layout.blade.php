<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
        }

        .page-header {
            text-align: center;
            border-bottom: 2px solid #78350f;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .page-header h1 {
            font-size: 18px;
            color: #78350f;
            margin-bottom: 4px;
        }

        .page-header .subtitle {
            font-size: 12px;
            color: #555;
        }

        .page-header .custom-header {
            font-size: 13px;
            color: #333;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .meta-bar {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            font-size: 10px;
            color: #555;
        }

        .meta-bar .left {
            display: table-cell;
            text-align: left;
        }

        .meta-bar .right {
            display: table-cell;
            text-align: right;
        }

        .context-box {
            background: #fef3c7;
            border: 1px solid #d97706;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 20px;
        }

        .context-box h3 {
            font-size: 12px;
            color: #78350f;
            margin-bottom: 6px;
        }

        .context-grid {
            display: table;
            width: 100%;
        }

        .context-grid .row {
            display: table-row;
        }

        .context-grid .label {
            display: table-cell;
            font-weight: bold;
            padding: 2px 12px 2px 0;
            width: 140px;
            color: #555;
        }

        .context-grid .value {
            display: table-cell;
            padding: 2px 0;
        }

        .section {
            margin-bottom: 20px;
        }

        .section h2 {
            font-size: 14px;
            color: #78350f;
            border-bottom: 1px solid #d6d3d1;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 10px;
        }

        table.data-table thead th {
            background: #78350f;
            color: #fff;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 9px;
            text-transform: uppercase;
        }

        table.data-table tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e7e5e4;
        }

        table.data-table tbody tr:nth-child(even) {
            background: #fafaf9;
        }

        table.data-table tbody tr.at-risk {
            background: #fef2f2;
        }

        table.data-table .number {
            text-align: right;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }

        .badge-danger {
            background: #fecaca;
            color: #991b1b;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }

        .summary-card {
            display: table-cell;
            text-align: center;
            padding: 8px;
            border: 1px solid #d6d3d1;
            background: #fafaf9;
        }

        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #78350f;
        }

        .summary-card .label {
            font-size: 9px;
            color: #78716c;
            text-transform: uppercase;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #78716c;
            border-top: 1px solid #d6d3d1;
            padding-top: 8px;
            padding-bottom: 12px;
        }

        .page-footer .custom-footer {
            margin-bottom: 4px;
        }

        .signature-section {
            margin-top: 60px;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            width: 250px;
            border-top: 1px solid #333;
            padding-top: 4px;
            font-size: 10px;
            color: #555;
        }

        table.compact-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.compact-table th,
        table.compact-table td {
            padding: 4px 8px;
            border: 1px solid #d6d3d1;
        }

        table.compact-table th {
            background: #f5f5f4;
            text-align: left;
            font-weight: 600;
        }
    </style>
</head>
<body>
    @yield('content')

    <div class="page-footer">
        @if($config['include_footer_text'])
            <div class="custom-footer">{{ $config['include_footer_text'] }}</div>
        @endif
        <span>Generado: {{ $meta['generated_at'] }} · Café Pedagógico</span>
    </div>
</body>
</html>
