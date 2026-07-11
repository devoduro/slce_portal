<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Fees</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .creditor {
            color: #059669;
        }
        .debtor {
            color: #dc2626;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            padding: 0;
            font-size: 18px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Fees</h1>
        <p>Academic Year: {{ $academicYear->name ?? 'N/A' }} &bull; Generated on {{ now()->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Index Number</th>
                <th>Full Name</th>
                <th>Programme</th>
                <th>Level</th>
                <th class="text-right">Fee Amount</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
                <th>Status</th>
                <th class="text-right">% Paid</th>
                <th class="text-right">Arrears</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php $student = $row['student']; @endphp
                <tr>
                    <td>{{ $student->index_number }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->programme->name ?? 'N/A' }}</td>
                    <td>{{ $student->level ?? '-' }}</td>
                    <td class="text-right">{{ $row['fee_amount'] !== null ? number_format($row['fee_amount'], 2) : 'Not set' }}</td>
                    <td class="text-right">{{ number_format($row['paid'], 2) }}</td>
                    <td class="text-right {{ $row['status'] }}">{{ number_format($row['balance'], 2) }}</td>
                    <td class="{{ $row['status'] }}">{{ ucfirst($row['status']) }}</td>
                    <td class="text-right">{{ $row['percentage'] }}%</td>
                    <td class="text-right">{{ number_format($row['arrears'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
