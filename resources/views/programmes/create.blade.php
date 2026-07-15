@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Create New Programme</span>
                    <a href="{{ route('programmes.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Programmes
                    </a>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Error!</strong> Please check the form below for errors.<br>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('programmes.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Programme Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Programme Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                                    @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department">Department <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department') }}" required>
                                    @error('department')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="faculty">Faculty <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('faculty') is-invalid @enderror" id="faculty" name="faculty" value="{{ old('faculty') }}" required>
                                    @error('faculty')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duration">Duration (Years) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" value="{{ old('duration') }}" min="1" max="10" required>
                                    @error('duration')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sts_category">STS / Internship Category</label>
                                    <select class="form-control @error('sts_category') is-invalid @enderror" id="sts_category" name="sts_category">
                                        <option value="">Not applicable</option>
                                        @foreach(\App\Models\Programme::STS_CATEGORY_LABELS as $value => $label)
                                            <option value="{{ $value }}" {{ old('sts_category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Determines which partner schools and STS terms students on this programme are eligible for. Leave as "Not applicable" if this programme has no STS/Internship placement.</small>
                                    @error('sts_category')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Programme
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
