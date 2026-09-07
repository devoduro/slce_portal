<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .owing {
            color: #dc2626;
        }
        .credit {
            color: #059669;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 18px;
        }
        .header p {
            color: #666;
            margin: 4px 0 0;
            font-size: 11px;
        }
        .summary {
            margin-top: 10px;
            font-size: 10px;
            color: #444;
        }
        .summary span {
            margin-right: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'College AIMS') }}</h1>
        <p>{{ $title }}</p>
        <p>Generated {{ now()->format('d M Y, H:i') }}</p>
    </div>

    <div class="summary">
        <span><strong>Graduates:</strong> {{ number_format($summary['total']) }}</span>
        <span><strong>Owing:</strong> {{ number_format($summary['owing_count']) }} ({{ number_format($summary['owing_total'], 2) }})</span>
        <span><strong>Settled:</strong> {{ number_format($summary['settled_count']) }}</span>
        <span><strong>In credit:</strong> {{ number_format($summary['credit_count']) }} ({{ number_format($summary['credit_total'], 2) }})</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Index Number</th>
                <th>Full Name</th>
                <th>Programme</th>
                <th>Graduated</th>
                <th>Level</th>
                <th>Phone</th>
                <th class="text-right">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php $student = $row['student']; @endphp
                <tr>
                    <td>{{ $student->index_number }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->programme->name ?? 'N/A' }}</td>
                    <td>{{ $student->graduatedAcademicYear->name ?? 'N/A' }}</td>
                    <td>{{ $student->level ?? '-' }}</td>
                    <td>{{ $student->phone ?: '-' }}</td>
                    <td class="text-right {{ $row['status'] }}">{{ number_format($row['balance'], 2) }}</td>
                    <td class="{{ $row['status'] }}">{{ ucfirst($row['status']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
