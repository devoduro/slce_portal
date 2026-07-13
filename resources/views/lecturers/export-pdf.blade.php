<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lecturers</title>
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
        <h1>Lecturers</h1>
        <p>Generated on {{ now()->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Staff ID</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Department</th>
                <th class="text-right">Courses</th>
                <th class="text-right">Classes (Current Semester)</th>
                <th class="text-right">Workload (Credit Hours)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lecturers as $lecturer)
                <tr>
                    <td>{{ $lecturer->name }}</td>
                    <td>{{ $lecturer->staff_id ?? 'N/A' }}</td>
                    <td>{{ $lecturer->email ?? 'N/A' }}</td>
                    <td>{{ $lecturer->phone ?? 'N/A' }}</td>
                    <td>{{ $lecturer->department->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ $lecturer->courses_count }}</td>
                    <td class="text-right">{{ $lecturer->workload_classes }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format($lecturer->workload_credit, 2), '0'), '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
