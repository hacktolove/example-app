<?php

// check_sub.php - Check subscription status for a given number
// Usage: GET /check_sub.php?number=+249XXXXXXXXX

require_once 'db_config.php'; // contains $db_dsn, $db_user, $db_pass

function normalizeNumber($number)
{
    // Remove anything except digits and '+'
    $cleaned = preg_replace('/[^0-9+]/', '', $number);
    if (empty($cleaned)) {
        return null;
    }
    // If no '+' prefix, assume local Sudan number and add '+249'
    if (strpos($cleaned, '+') !== 0) {
        $cleaned = ltrim($cleaned, '0'); // remove leading zeros
        $cleaned = '+249'.$cleaned;
    }

    return $cleaned;
}

// Get number from query string
$number = isset($_GET['number']) ? $_GET['number'] : null;
if (! $number) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing number parameter']);
    exit;
}

$msisdn = normalizeNumber($number);
if (! $msisdn) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid number format']);
    exit;
}

try {
    // Connect using PDO
    $pdo = new PDO($db_dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT msisdn, status FROM profiles WHERE msisdn = :msisdn');
    $stmt->execute([':msisdn' => $msisdn]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $status = (int) $row['status'];
        $response = [
            'msisdn' => $row['msisdn'],
            'status' => $status,
            'subscribed' => ($status === 1),
        ];
    } else {
        $response = [
            'msisdn' => $msisdn,
            'status' => 0,
            'subscribed' => false,
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: '.$e->getMessage()]);
}
