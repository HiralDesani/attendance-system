@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-clock-history"></i> Attendance Logs Details</h4>
                    <a href="{{ route('user.attendance.index') }}" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>

                <div class="card-body">
                    <!-- Attendance Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-1">Date</small>
                                <strong>{{ \Carbon\Carbon::parse($attendance->date)->format('d M Y, l') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-1">Attendance ID</small>
                                <strong>#{{ $attendance->id }}</strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-1">Check In</small>
                                <strong>
                                    @if(isset($attendance->first_check_in) && $attendance->first_check_in)
                                        {{ $attendance->first_check_in->format('h:i:s A') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-1">Check Out</small>
                                <strong>
                                    @if(isset($attendance->last_checkout) && $attendance->last_checkout)
                                        {{ $attendance->last_checkout->format('h:i:s A') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-primary text-white">
                                <small class="d-block mb-1 opacity-75">Total Time</small>
                                <strong>
                                    @php
                                        $hours = floor($attendance->total_time / 3600);
                                        $minutes = floor(($attendance->total_time % 3600) / 60);
                                        $seconds = $attendance->total_time % 60;
                                        echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    @endphp
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-warning text-white">
                                <small class="d-block mb-1 opacity-75">Total Break Time</small>
                                <strong>
                                    @php
                                        $hours = floor($attendance->total_break_time / 3600);
                                        $minutes = floor(($attendance->total_break_time % 3600) / 60);
                                        $seconds = $attendance->total_break_time % 60;
                                        echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    @endphp
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="border rounded p-3 bg-info text-white">
                                <small class="d-block mb-1 opacity-75">Total Actions</small>
                                <strong>{{ $logs->count() }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Logs -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Check In/Check Out Logs</h5>
                        </div>
                        <div class="card-body">
                            @if($logs->isEmpty())
                                <p class="text-muted text-center mb-0">No logs found for this attendance.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Action</th>
                                                <th>Time</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $prevTime = null;
                                            @endphp
                                            @foreach($logs as $index => $log)
                                                @php
                                                    $currentTime = \Carbon\Carbon::parse($log->time);
                                                    $duration = '';
                                                    if ($prevTime) {
                                                        $prev = \Carbon\Carbon::parse($prevTime);
                                                        $diffSeconds = $prev->diffInSeconds($currentTime);
                                                        $hours = floor($diffSeconds / 3600);
                                                        $minutes = floor(($diffSeconds % 3600) / 60);
                                                        $seconds = $diffSeconds % 60;
                                                        $duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                                    }
                                                    $prevTime = $log->time;
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        @if($log->action === 'check_in')
                                                            <span class="badge bg-success">Check In</span>
                                                        @else
                                                            <span class="badge bg-danger">Check Out</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $currentTime->format('d M Y, h:i:s A') }}</td>
                                                    <td>
                                                        @if($duration)
                                                            <span class="text-muted">{{ $duration }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

