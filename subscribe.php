<?php

// subscribe.php - Subscribe a number (insert or update status=1)
// Usage: POST /subscribe.php with JSON body: {"number":"+249XXXXXXXXX"}

require_once 'db_config.php';

function normalizeNumber($number)
{
    $cleaned = preg_replace('/[^0-9+]/', '', $number);
    if (empty($cleaned)) {
        return null;
    }
    if (strpos($cleaned, '+') !== 0) {
        $cleaned = ltrim($cleaned, '0');
        $cleaned = '+249'.$cleaned;
    }

    return $cleaned;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (! $input || ! isset($input['number'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing number in JSON body']);
    exit;
}

$number = $input['number'];
$msisdn = normalizeNumber($number);
if (! $msisdn) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid number format']);
    exit;
}

try {
    $pdo = new PDO($db_dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if exists
    $stmt = $pdo->prepare('SELECT msisdn FROM profiles WHERE msisdn = :msisdn');
    $stmt->execute([':msisdn' => $msisdn]);
    $exists = $stmt->fetch() ? true : false;

    if ($exists) {
        // Update
        $stmt = $pdo->prepare('
            UPDATE profiles
            SET status = 1,
                last_update_date = CURRENT_DATE,
                last_update_time = CURRENT_TIME
            WHERE msisdn = :msisdn
        ');
        $stmt->execute([':msisdn' => $msisdn]);
        $action = 'update';
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO profiles (msisdn, status, channel, subs_date, subs_time, last_update_date, last_update_time)
            VALUES (:msisdn, 1, 'api', CURRENT_DATE, CURRENT_TIME, CURRENT_DATE, CURRENT_TIME)
        ");
        $stmt->execute([':msisdn' => $msisdn]);
        $action = 'insert';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'msisdn' => $msisdn,
        'status' => 1,
        'action' => $action,
        'message' => "Subscription {$action}ed successfully",
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}
