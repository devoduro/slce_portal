@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">CGPA Distribution</h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('gpa-distribution.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="programme_id" class="form-label">Programme</label>
                                <select name="programme_id" id="programme_id" class="form-select">
                                    <option value="">All Programmes</option>
                                    @foreach($programmes as $programme)
                                        <option value="{{ $programme->id }}" {{ request('programme_id') == $programme->id ? 'selected' : '' }}>
                                            {{ $programme->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="min_gpa" class="form-label">Min CGPA</label>
                                <input type="number" step="0.01" min="0" max="4" class="form-control" id="min_gpa" name="min_gpa" value="{{ request('min_gpa') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="max_gpa" class="form-label">Max CGPA</label>
                                <input type="number" step="0.01" min="0" max="4" class="form-control" id="max_gpa" name="max_gpa" value="{{ request('max_gpa') }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </div>
                    </form>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Students</h5>
                                    <p class="card-text h3">{{ $stats['total_students'] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Average CGPA</h5>
                                    <p class="card-text h3">{{ number_format($stats['average_gpa'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Highest CGPA</h5>
                                    <p class="card-text h3">{{ number_format($stats['highest_gpa'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Lowest CGPA</h5>
                                    <p class="card-text h3">{{ number_format($stats['lowest_gpa'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <canvas id="gpaChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Distribution Table</h5>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>CGPA Range</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($distribution as $range => $count)
                                                <tr>
                                                    <td>{{ $range }}</td>
                                                    <td>{{ $count }}</td>
                                                    <td>{{ $stats['total_students'] > 0 ? number_format(($count / $stats['total_students']) * 100, 1) : 0 }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Student List</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Programme</th>
                                            <th>CGPA</th>
                                            <th>Semester GPAs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $student)
                                            <tr>
                                                <td>{{ $student->student_id }}</td>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $student->programme_name }}</td>
                                                <td>{{ number_format($student->calculated_gpa, 2) }}</td>
                                                <td>
                                                    @foreach($student->semester_gpas as $gpa)
                                                        <div class="mb-1">
                                                            <small>{{ $gpa['period'] }}: {{ number_format($gpa['gpa'], 2) }} ({{ $gpa['credits'] }} credits)</small>
                                                        </div>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('gpaChart').getContext('2d');
    const distribution = @json($distribution);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(distribution),
            datasets: [{
                label: 'Number of Students',
                data: Object.values(distribution),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'CGPA Distribution'
                },
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
@endpush
