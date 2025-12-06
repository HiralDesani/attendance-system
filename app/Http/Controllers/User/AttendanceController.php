<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the attendance page with check-in/checkout buttons and logs.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // Get today's attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Get today's logs
        $todayLogs = collect();
        $totalTimeWorkedSoFar = 0; // Total time from completed check-in/checkout pairs
        $currentCheckInTime = null;
        
        if ($attendance) {
            $todayLogs = AttendanceLog::where('attendance_id', $attendance->id)
                ->orderBy('time', 'asc')
                ->get();
            
            // Calculate total time worked from completed pairs
            $checkInTime = null;
            foreach ($todayLogs as $log) {
                if ($log->action === 'check_in') {
                    $checkInTime = Carbon::parse($log->time);
                    // If this is the last log and it's check-in, store it for timer
                    if ($log === $todayLogs->last()) {
                        $currentCheckInTime = $checkInTime;
                    }
                } elseif ($log->action === 'checkout' && $checkInTime) {
                    $checkoutTime = Carbon::parse($log->time);
                    $duration = $checkInTime->diffInSeconds($checkoutTime);
                    $totalTimeWorkedSoFar += $duration;
                    $checkInTime = null;
                }
            }
        }

        // Determine button states
        $canCheckIn = true;
        $canCheckout = false;
        
        if ($todayLogs->isNotEmpty()) {
            $lastLog = $todayLogs->last();
            if ($lastLog->action === 'check_in') {
                $canCheckIn = false;
                $canCheckout = true;
            } else {
                $canCheckIn = true;
                $canCheckout = false;
            }
        }

        // Get attendance listing with filters
        $query = Attendance::where('user_id', $user->id)
            ->with('logs')
            ->orderBy('date', 'desc');

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        $attendances = $query->paginate(15);

        // Calculate total time and break time for each attendance (for display only)
        foreach ($attendances as $att) {
            if (!$att->relationLoaded('logs')) {
                $att->load('logs');
            }
            $this->calculateTimesForDisplay($att);
            // Add first check-in and last checkout to attendance object for view
            $att->first_check_in = $this->getFirstCheckIn($att->logs);
            $att->last_checkout = $this->getLastCheckout($att->logs);
        }

        return view('user.attendance.index', compact('attendance', 'todayLogs', 'canCheckIn', 'canCheckout', 'attendances', 'totalTimeWorkedSoFar', 'currentCheckInTime'));
    }

    /**
     * Handle check-in action.
     */
    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        // Get or create today's attendance
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ]
        );

        // Check if last action was checkout (to prevent duplicate check-ins)
        $lastLog = AttendanceLog::where('attendance_id', $attendance->id)
            ->orderBy('time', 'desc')
            ->first();

        if ($lastLog && $lastLog->action === 'check_in') {
            return response()->json([
                'success' => false,
                'message' => 'You are already checked in. Please checkout first.',
            ], 400);
        }

        // Use database transaction for data integrity
        \DB::beginTransaction();
        try {
            // Create check-in log
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'action' => 'check_in',
                'time' => $now,
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful!',
                'canCheckIn' => false,
                'canCheckout' => true,
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during check-in. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle checkout action.
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        // Get today's attendance
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'No attendance found for today. Please check in first.',
            ], 400);
        }

        // Check if last action was check-in
        $lastLog = AttendanceLog::where('attendance_id', $attendance->id)
            ->orderBy('time', 'desc')
            ->first();

        if (!$lastLog || $lastLog->action !== 'check_in') {
            return response()->json([
                'success' => false,
                'message' => 'Please check in first before checkout.',
            ], 400);
        }

        // Use database transaction for data integrity
        \DB::beginTransaction();
        try {
            // Create checkout log
            AttendanceLog::create([
                'attendance_id' => $attendance->id,
                'action' => 'checkout',
                'time' => $now,
            ]);

            // Recalculate times
            $this->calculateTimes($attendance);
            $attendance->refresh();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful!',
                'canCheckIn' => true,
                'canCheckout' => false,
                'totalTime' => $this->formatTime($attendance->total_time),
                'totalBreakTime' => $this->formatTime($attendance->total_break_time),
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during checkout. Please try again.',
            ], 500);
        }
    }

    /**
     * View detailed logs for a specific day.
     */
    public function viewLogs($id)
    {
        $user = Auth::user();
        
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->with('logs')
            ->firstOrFail();

        $logs = AttendanceLog::where('attendance_id', $attendance->id)
            ->orderBy('time', 'asc')
            ->get();

        // Calculate and update times
        $this->calculateTimes($attendance);
        $attendance->refresh();
        
        // Add first check-in and last checkout to attendance object for view
        $attendance->first_check_in = $this->getFirstCheckIn($logs);
        $attendance->last_checkout = $this->getLastCheckout($logs);

        return view('user.attendance.view-logs', compact('attendance', 'logs'));
    }

    /**
     * Get current check-in/checkout status.
     */
    public function getStatus()
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('logs')
            ->first();

        $canCheckIn = true;
        $canCheckout = false;

        if ($attendance && $attendance->logs->isNotEmpty()) {
            $lastLog = $attendance->logs->sortByDesc('time')->first();
            if ($lastLog->action === 'check_in') {
                $canCheckIn = false;
                $canCheckout = true;
            } else {
                $canCheckIn = true;
                $canCheckout = false;
            }
        }

        return response()->json([
            'canCheckIn' => $canCheckIn,
            'canCheckout' => $canCheckout,
        ]);
    }

    /**
     * Calculate total time and break time for an attendance (for display only, doesn't save).
     */
    private function calculateTimesForDisplay(Attendance $attendance)
    {
        $logs = $attendance->logs->sortBy('time');

        $totalTime = 0;
        $totalBreakTime = 0;
        $checkInTime = null;

        foreach ($logs as $log) {
            if ($log->action === 'check_in') {
                $checkInTime = Carbon::parse($log->time);
            } elseif ($log->action === 'checkout' && $checkInTime) {
                $checkoutTime = Carbon::parse($log->time);
                $duration = $checkInTime->diffInSeconds($checkoutTime);
                $totalTime += $duration;
                $checkInTime = null;
            }
        }

        // Calculate break time (time between checkout and next check-in)
        $logsArray = $logs->values()->all();
        for ($i = 0; $i < count($logsArray) - 1; $i++) {
            $current = $logsArray[$i];
            $next = $logsArray[$i + 1];

            if ($current->action === 'checkout' && $next->action === 'check_in') {
                $checkoutTime = Carbon::parse($current->time);
                $nextCheckInTime = Carbon::parse($next->time);
                $breakDuration = $checkoutTime->diffInSeconds($nextCheckInTime);
                $totalBreakTime += $breakDuration;
            }
        }

        $attendance->total_time = $totalTime;
        $attendance->total_break_time = $totalBreakTime;
    }

    /**
     * Calculate total time and break time for an attendance and save.
     */
    private function calculateTimes(Attendance $attendance)
    {
        $logs = AttendanceLog::where('attendance_id', $attendance->id)
            ->orderBy('time', 'asc')
            ->get();

        $totalTime = 0;
        $totalBreakTime = 0;
        $checkInTime = null;

        foreach ($logs as $log) {
            if ($log->action === 'check_in') {
                $checkInTime = Carbon::parse($log->time);
            } elseif ($log->action === 'checkout' && $checkInTime) {
                $checkoutTime = Carbon::parse($log->time);
                $duration = $checkInTime->diffInSeconds($checkoutTime);
                $totalTime += $duration;
                $checkInTime = null;
            }
        }

        // Calculate break time (time between checkout and next check-in)
        for ($i = 0; $i < $logs->count() - 1; $i++) {
            $current = $logs[$i];
            $next = $logs[$i + 1];

            if ($current->action === 'checkout' && $next->action === 'check_in') {
                $checkoutTime = Carbon::parse($current->time);
                $nextCheckInTime = Carbon::parse($next->time);
                $breakDuration = $checkoutTime->diffInSeconds($nextCheckInTime);
                $totalBreakTime += $breakDuration;
            }
        }

        $attendance->total_time = $totalTime;
        $attendance->total_break_time = $totalBreakTime;
        $attendance->save();
    }

    /**
     * Get first check-in time from logs.
     */
    private function getFirstCheckIn($logs)
    {
        if (!$logs || $logs->isEmpty()) {
            return null;
        }
        $sortedLogs = $logs->sortBy('time');
        $firstCheckIn = $sortedLogs->where('action', 'check_in')->first();
        return $firstCheckIn ? Carbon::parse($firstCheckIn->time) : null;
    }

    /**
     * Get last checkout time from logs.
     */
    private function getLastCheckout($logs)
    {
        if (!$logs || $logs->isEmpty()) {
            return null;
        }
        $sortedLogs = $logs->sortBy('time');
        $lastCheckout = $sortedLogs->where('action', 'checkout')->last();
        return $lastCheckout ? Carbon::parse($lastCheckout->time) : null;
    }

    /**
     * Format seconds to readable time format.
     */
    private function formatTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
