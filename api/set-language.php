<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['language']) || !in_array($input['language'], ['tr', 'en'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid language']);
    exit;
}

$language = $input['language'];

try {
    setLanguage($language);
    echo json_encode(['success' => true, 'language' => $language]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save language preference']);
}
?> 