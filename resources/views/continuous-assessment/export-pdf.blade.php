<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Continuous Assessment Courses</title>
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
        <h1>Continuous Assessment Courses</h1>
        <p>Generated on {{ now()->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Level</th>
                <th>Programme</th>
                <th>Semester</th>
                <th>Lecturer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($courses as $course)
                <tr>
                    <td>{{ $course->code }}</td>
                    <td>{{ $course->title }}</td>
                    <td>{{ $course->level ?? 'N/A' }}</td>
                    <td>{{ $course->programmes->pluck('code')->join(', ') ?: 'None' }}</td>
                    <td>{{ $course->semester->name ?? 'N/A' }}</td>
                    <td>{{ $course->lecturers->pluck('name')->join(', ') ?: 'Not assigned' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
