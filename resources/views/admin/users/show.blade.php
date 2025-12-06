@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-circle"></i> User Details</h2>
    <div>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4"><i class="bi bi-info-circle"></i> User Information</h5>
                
                <table class="table table-borderless">
                    <tr>
                        <th width="200"><i class="bi bi-hash"></i> ID:</th>
                        <td>{{ $user->id }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-person"></i> Name:</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-envelope"></i> Email:</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-shield-check"></i> Role:</th>
                        <td>
                            <span class="badge bg-primary">{{ ucfirst($user->role) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-calendar-plus"></i> Created At:</th>
                        <td>{{ $user->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <th><i class="bi bi-calendar-check"></i> Updated At:</th>
                        <td>{{ $user->updated_at->format('d M Y, h:i A') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
