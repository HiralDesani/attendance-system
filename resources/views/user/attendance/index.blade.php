@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-clock-history"></i> Attendance Management</h4>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Check In / Checkout Buttons -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title mb-4">Today's Attendance</h5>
                                    <div class="d-flex justify-content-center gap-3">
                                        <button id="checkInBtn" class="btn btn-success btn-lg px-5"
                                                {{ !$canCheckIn ? 'disabled' : '' }}>
                                            <i class="bi bi-box-arrow-in-right"></i> Check In
                                        </button>
                                        <button id="checkoutBtn" class="btn btn-danger btn-lg px-5"
                                                {{ !$canCheckout ? 'disabled' : '' }}>
                                            <i class="bi bi-box-arrow-right"></i> Check Out
                                        </button>
                                    </div>
                                    <div id="message" class="mt-3"></div>
                                    <div class="mt-4">
                                        @if($attendance && $todayLogs->isNotEmpty())
                                            @php
                                                $firstCheckIn = $todayLogs->where('action', 'check_in')->first();
                                                $lastCheckout = $todayLogs->where('action', 'checkout')->last();
                                                $lastLog = $todayLogs->last();
                                            @endphp
                                            @if($firstCheckIn)
                                                <div class="mb-2">
                                                    <strong>Check In:</strong>
                                                    <span id="firstCheckInTime">{{ \Carbon\Carbon::parse($firstCheckIn->time)->format('h:i:s A') }}</span>
                                                </div>
                                            @endif
                                            @if($lastCheckout)
                                                <div class="mb-2">
                                                    <strong>Check Out:</strong>
                                                    <span id="lastCheckoutTime">{{ \Carbon\Carbon::parse($lastCheckout->time)->format('h:i:s A') }}</span>
                                                </div>
                                            @endif
                                        @endif
                                        <div class="mb-2">
                                            <strong>Total Time:</strong>
                                            <span id="timerDisplay" class="h4 text-primary">
                                                @php
                                                    $hours = floor(($totalTimeWorkedSoFar ?? 0) / 3600);
                                                    $minutes = floor((($totalTimeWorkedSoFar ?? 0) % 3600) / 60);
                                                    $seconds = ($totalTimeWorkedSoFar ?? 0) % 60;
                                                    echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                                @endphp
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Listing with Filters -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Attendance History</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Date Range Filter -->
                                    <form method="GET" action="{{ route('user.attendance.index') }}" class="mb-4">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date"
                                                       value="{{ request('start_date') }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date"
                                                       value="{{ request('end_date') }}">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="bi bi-funnel"></i> Filter
                                                </button>
                                                <a href="{{ route('user.attendance.index') }}" class="btn btn-secondary">
                                                    <i class="bi bi-x-circle"></i> Clear
                                                </a>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Attendance Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Date</th>
                                                    <th>Check In Time</th>
                                                    <th>Check Out Time</th>
                                                    <th>Total Time</th>
                                                    <th>Total Break Time</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($attendances as $att)
                                                    <tr>
                                                        <td>{{ ($attendances->currentPage() - 1) * $attendances->perPage() + $loop->iteration }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($att->date)->format('d M Y') }}</td>
                                                        <td>
                                                            @if(isset($att->first_check_in) && $att->first_check_in)
                                                                {{ $att->first_check_in->format('h:i:s A') }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($att->last_checkout) && $att->last_checkout)
                                                                {{ $att->last_checkout->format('h:i:s A') }}
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $hours = floor($att->total_time / 3600);
                                                                $minutes = floor(($att->total_time % 3600) / 60);
                                                                $seconds = $att->total_time % 60;
                                                                echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                                            @endphp
                                                        </td>
                                                        <td>
                                                            @php
                                                                $hours = floor($att->total_break_time / 3600);
                                                                $minutes = floor(($att->total_break_time % 3600) / 60);
                                                                $seconds = $att->total_break_time % 60;
                                                                echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                                            @endphp
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('user.attendance.view-logs', $att->id) }}"
                                                               class="btn btn-sm btn-info">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">No attendance records found.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="mt-3">
                                        {{ $attendances->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkInBtn = document.getElementById('checkInBtn');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const messageDiv = document.getElementById('message');
    const todayLogsTable = document.getElementById('todayLogsTable');
    const timerDisplay = document.getElementById('timerDisplay');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let timerInterval = null;
    let timerStartTime = null;
    let totalTimeWorkedSoFar = {{ $totalTimeWorkedSoFar ?? 0 }}; // Total time from completed sessions in seconds

    // Start timer if user is checked in
    @if($attendance && $todayLogs->isNotEmpty())
        @php
            $lastLog = $todayLogs->last();
        @endphp
        @if($lastLog && $lastLog->action === 'check_in' && isset($currentCheckInTime))
            timerStartTime = new Date('{{ $currentCheckInTime->toIso8601String() }}');
            startTimer();
        @endif
    @endif

    function startTimer() {
        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(function() {
            if (timerStartTime) {
                const now = new Date();
                const currentSessionTime = Math.floor((now - timerStartTime) / 1000); // current session in seconds
                const totalTime = totalTimeWorkedSoFar + currentSessionTime; // cumulative total

                const hours = Math.floor(totalTime / 3600);
                const minutes = Math.floor((totalTime % 3600) / 60);
                const seconds = totalTime % 60;

                if (timerDisplay) {
                    timerDisplay.textContent =
                        String(hours).padStart(2, '0') + ':' +
                        String(minutes).padStart(2, '0') + ':' +
                        String(seconds).padStart(2, '0');
                }
            }
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        // Don't reset timer display, keep showing total time
    }

    // Check In Button Click
    checkInBtn.addEventListener('click', function() {
        if (this.disabled) return;

        fetch('{{ route("user.attendance.check-in") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                checkInBtn.disabled = true;
                checkoutBtn.disabled = false;

                // Start timer with current total time
                timerStartTime = new Date();
                // totalTimeWorkedSoFar is already set from server, timer will add current session
                startTimer();

                // Reload page to show updated logs
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showMessage(data.message || 'Error occurred', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred. Please try again.', 'danger');
        });
    });

    // Checkout Button Click
    checkoutBtn.addEventListener('click', function() {
        if (this.disabled) return;

        fetch('{{ route("user.attendance.checkout") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                checkInBtn.disabled = false;
                checkoutBtn.disabled = true;

                // Stop timer updates but keep showing total time
                stopTimer();

                // Update total time worked (add current session to total)
                if (timerStartTime) {
                    const now = new Date();
                    const currentSessionTime = Math.floor((now - timerStartTime) / 1000);
                    totalTimeWorkedSoFar += currentSessionTime;

                    // Update timer display with final total
                    if (timerDisplay) {
                        const hours = Math.floor(totalTimeWorkedSoFar / 3600);
                        const minutes = Math.floor((totalTimeWorkedSoFar % 3600) / 60);
                        const seconds = totalTimeWorkedSoFar % 60;
                        timerDisplay.textContent =
                            String(hours).padStart(2, '0') + ':' +
                            String(minutes).padStart(2, '0') + ':' +
                            String(seconds).padStart(2, '0');
                    }
                }

                // Reload page to show updated logs and final total time
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showMessage(data.message || 'Error occurred', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = (error && error.message) ? error.message : 'An error occurred. Please try again.';
            showMessage(errorMessage, 'danger');
        });
    });

    function showMessage(message, type) {
        messageDiv.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;

        setTimeout(() => {
            messageDiv.innerHTML = '';
        }, 5000);
    }
});
</script>
@endsection

