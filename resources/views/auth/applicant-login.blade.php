<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Transcript System') }} - Applicant Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .login-container { max-width: 450px; margin: 0 auto; padding: 2rem; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .card-header {
            background: linear-gradient(to right, #0369a1, #0ea5e9);
            color: white; text-align: center; padding: 1.5rem; border-bottom: none;
        }
        .btn-primary { background: linear-gradient(to right, #0369a1, #0ea5e9); border: none; }
        .btn-primary:hover { background: linear-gradient(to right, #075985, #0284c7); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 login-container">
                <div class="card">
                    <div class="card-header">
                        <img src="{{ asset('images/slce_logo.png') }}" alt="St. Louis College of Education" class="mb-3" style="height: 80px;">
                        <h3 class="mb-0">Applicant Login</h3>
                        <p class="mb-0">Track your admission and payment status</p>
                    </div>
                    <div class="card-body p-4">
                        @if (session('message'))
                            <div class="alert alert-success mb-3">{{ session('message') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('applicant.login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="applicant_number" class="form-label">Applicant Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control @error('applicant_number') is-invalid @enderror" id="applicant_number" name="applicant_number" value="{{ old('applicant_number') }}" required autofocus>
                                </div>
                                @error('applicant_number')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="text-decoration-none small text-muted">Staff login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
