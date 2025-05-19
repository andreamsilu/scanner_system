@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Import Users</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('import_errors'))
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">Import Errors:</h5>
                            <ul class="mb-0">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5>Instructions:</h5>
                        <ol>
                            <li>Download the template file below</li>
                            <li>Fill in the user details following the template format</li>
                            <li>Upload the completed CSV file</li>
                        </ol>
                        <div class="alert alert-info">
                            <strong>Note:</strong> The CSV file must include the following headers:
                            <code>name, email, password, role</code>
                        </div>
                    </div>

                    <form action="{{ route('users.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File</label>
                            <input type="file" class="form-control @error('csv_file') is-invalid @enderror" 
                                   id="csv_file" name="csv_file" accept=".csv" required>
                            @error('csv_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('users.import.template') }}" class="btn btn-secondary">
                                <i class="bi bi-download"></i> Download Template
                            </a>
                            <div>
                                <a href="{{ route('users') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Import Users
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- CSV Format Example -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">CSV Format Example</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>name</th>
                                    <th>email</th>
                                    <th>password</th>
                                    <th>role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>John Doe</td>
                                    <td>john@example.com</td>
                                    <td>password123</td>
                                    <td>user</td>
                                </tr>
                                <tr>
                                    <td>Admin User</td>
                                    <td>admin@example.com</td>
                                    <td>adminpass123</td>
                                    <td>admin</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 