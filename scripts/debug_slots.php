<?php
// Script de ayuda para inspeccionar doctor_schedules y slots en database/database.sqlite
// Uso: php scripts/debug_slots.php [doctor_id] [specialty_id] [date]

$cwd = __DIR__ . '/..';
$dbPath = $cwd . '/database/database.sqlite';
if (!file_exists($dbPath)) {
    echo json_encode(['error' => "No se encontró la base de datos en $dbPath"], JSON_PRETTY_PRINT);
    exit(1);
}
app-DG93H_qu.js:4  GET https://consultorio.test/api/appointments/available-slots?doctor_id=5&date=2025-09-02&specialty_id=2 404 (Not Found)
(anonymous) @ app-DG93H_qu.js:4
xhr @ app-DG93H_qu.js:4
ha @ app-DG93H_qu.js:6
_request @ app-DG93H_qu.js:7
request @ app-DG93H_qu.js:6
fr.<computed> @ app-DG93H_qu.js:7
(anonymous) @ app-DG93H_qu.js:2
ee @ Index-CqeBVFd4.js:53
E @ Index-CqeBVFd4.js:53
Sn @ app-DG93H_qu.js:87
St @ app-DG93H_qu.js:87
r @ app-DG93H_qu.js:92
handleMouseUp_ @ unknownUnderstand this error
Index-CqeBVFd4.js:53 Error loading available slots: ee {message: 'Request failed with status code 404', name: 'AxiosError', code: 'ERR_BAD_REQUEST', config: {…}, request: XMLHttpRequest, …}
$doctorId = $argv[1] ?? null;
$specialtyId = $argv[2] ?? null;
$dateArg = $argv[3] ?? null;

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$doctorId || !$specialtyId || !$dateArg) {
        // Mostrar algunas filas de doctor_schedules para inspección
        $stmt = $pdo->query("SELECT id, doctor_id, specialty_id, day_of_week, start_time, end_time, appointment_duration, is_active FROM doctor_schedules LIMIT 20");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['sample_schedules' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit(0);
    }

    // Compute day_of_week string from dateArg
    $dt = new DateTime($dateArg);
    $dayNames = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    $dayOfWeek = $dayNames[(int)$dt->format('w')];

    // Fetch matching schedules
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? AND specialty_id = ? AND day_of_week = ? AND is_active = 1");
    $stmt->execute([$doctorId, $specialtyId, $dayOfWeek]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allSlots = [];
    $schedulesOut = [];
    foreach ($schedules as $sch) {
        $slots = [];
        $start = strtotime($sch['start_time']);
        $end = strtotime($sch['end_time']);
        $durationSec = ((int)$sch['appointment_duration']) * 60;
        if ($start && $end && $durationSec > 0) {
            while ($start < $end) {
                $slots[] = date('H:i', $start);
                $start += $durationSec;
            }
        }
        $allSlots = array_merge($allSlots, $slots);
        $schedulesOut[] = array_merge($sch, ['slots' => $slots]);
    }

    $allSlots = array_values(array_unique($allSlots));
    sort($allSlots);

    // Get booked appointments
    $stmt2 = $pdo->prepare("SELECT appointment_date, status FROM appointments WHERE doctor_id = ? AND specialty_id = ? AND date(appointment_date) = date(?) AND status != 'cancelada'");
    $stmt2->execute([$doctorId, $specialtyId, $dateArg]);
    $appts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $booked = [];
    foreach ($appts as $a) {
        $dt2 = new DateTime($a['appointment_date']);
        $booked[] = $dt2->format('H:i');
    }

    $available = array_values(array_diff($allSlots, $booked));

    // If date is today, filter out past times
    $today = new DateTime();
    $isToday = $today->format('Y-m-d') === (new DateTime($dateArg))->format('Y-m-d');
    if ($isToday) {
        $nowTime = $today->format('H:i');
        $available = array_values(array_filter($available, function($s) use ($nowTime) {
            return $s > $nowTime;
        }));
    }

    echo json_encode([
        'date' => $dateArg,
        'day_of_week' => $dayOfWeek,
        'schedules' => $schedulesOut,
        'all_slots' => $allSlots,
        'booked_slots' => $booked,
        'available_slots' => $available,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
    exit(1);
}
