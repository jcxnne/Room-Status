<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connection for reservations
    $pdo = new PDO("mysql:host=$host;dbname=reservation", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current date and time
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    $rooms = [];
    $debug_info = [];
    
    // Generate room numbers (adjust based on your actual rooms)
    for ($i = 101; $i <= 120; $i++) {
        $room_number = "Room $i";
        
        // Get the NEXT relevant reservation for this room today
        // Only get reservations that haven't ended + 30 min grace period yet
        $sql = "SELECT 
                    room, 
                    status, 
                    date, 
                    start_time, 
                    end_time, 
                    faculty_name, 
                    subject_code, 
                    checked_in, 
                    checked_out,
                    TIME_TO_SEC(TIMEDIFF(ADDTIME(end_time, '00:30:00'), ?)) as seconds_until_available
                FROM reservations 
                WHERE room = ? 
                AND date = ?
                AND (status = 'approved' OR status = 'pending')
                AND checked_out = 0
                AND ADDTIME(end_time, '00:30:00') >= ?
                ORDER BY start_time
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_time, $i, $current_date, $current_time]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store debug info for room 101
        if ($i == 101) {
            $debug_info['room_101'] = [
                'current_time' => $current_time,
                'current_date' => $current_date,
                'query_params' => [$current_time, $i, $current_date, $current_time],
                'reservation_found' => $reservation ? 'YES' : 'NO',
                'reservation_data' => $reservation
            ];
        }
        
        // Determine room status
        $status = 'available';
        $reservation_info = null;
        
        if ($reservation) {
            $start_time = $reservation['start_time'];
            $end_time = $reservation['end_time'];
            
            // Calculate grace period end (end_time + 30 minutes)
            $grace_period_end = date('H:i:s', strtotime($end_time) + 1800); // 1800 seconds = 30 minutes
            
            if ($i == 101) {
                $debug_info['room_101']['time_details'] = [
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'grace_period_end' => $grace_period_end,
                    'current_time' => $current_time,
                    'is_before_start' => $current_time < $start_time ? 'YES' : 'NO',
                    'is_during_reservation' => ($current_time >= $start_time && $current_time <= $end_time) ? 'YES' : 'NO',
                    'is_in_grace_period' => ($current_time > $end_time && $current_time <= $grace_period_end) ? 'YES' : 'NO',
                    'is_after_grace' => $current_time > $grace_period_end ? 'YES' : 'NO'
                ];
            }
            
            // This reservation should only be here if it's still within grace period
            // Because our SQL query filters out anything past grace period
            
            if ($reservation['status'] === 'approved') {
                // Before start time = upcoming
                if ($current_time < $start_time) {
                    $status = 'reserved';
                    $reservation_info = [
                        'faculty' => $reservation['faculty_name'],
                        'section' => $reservation['subject_code'],
                        'time' => date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time)),
                        'upcoming' => true,
                        'checked_in' => false
                    ];
                }
                // During reservation time
                elseif ($current_time >= $start_time && $current_time <= $end_time) {
                    $status = 'reserved';
                    $reservation_info = [
                        'faculty' => $reservation['faculty_name'],
                        'section' => $reservation['subject_code'],
                        'time' => date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time)),
                        'active' => true,
                        'checked_in' => $reservation['checked_in'] == 1
                    ];
                }
                // In grace period (after end time but within 30 min)
                elseif ($current_time > $end_time && $current_time <= $grace_period_end) {
                    $status = 'reserved';
                    $reservation_info = [
                        'faculty' => $reservation['faculty_name'],
                        'section' => $reservation['subject_code'],
                        'time' => date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time)),
                        'grace_period' => true,
                        'checked_in' => $reservation['checked_in'] == 1
                    ];
                }
                // After grace period - this shouldn't happen due to SQL filter
                else {
                    $status = 'available';
                    $reservation_info = null;
                }
            }
            elseif ($reservation['status'] === 'pending') {
                $status = 'pending';
                $reservation_info = [
                    'faculty' => $reservation['faculty_name'],
                    'section' => $reservation['subject_code'],
                    'time' => date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time))
                ];
            }
        }
        
        if ($i == 101) {
            $debug_info['room_101']['final_status'] = $status;
        }
        
        $rooms[] = [
            'number' => $i,
            'name' => $room_number,
            'status' => $status,
            'building' => $i <= 110 ? 'New Building' : 'Old Building',
            'reservation' => $reservation_info
        ];
    }
    
    // Count rooms by status
    $counts = [
        'total' => count($rooms),
        'available' => count(array_filter($rooms, fn($r) => $r['status'] === 'available')),
        'reserved' => count(array_filter($rooms, fn($r) => $r['status'] === 'reserved')),
        'pending' => count(array_filter($rooms, fn($r) => $r['status'] === 'pending'))
    ];
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms,
        'counts' => $counts,
        'debug' => $debug_info
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>