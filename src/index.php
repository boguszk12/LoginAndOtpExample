<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/database.php';
require __DIR__ . '/User.php';
require __DIR__ . '/TOTPService.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = (new Database())->getPdo();
$user = new User($pdo);
$totpService = new TOTPService();

header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestMethod == 'POST' && $requestUri == '/totp-api/src/index.php/register') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['username']) && isset($data['password'])) {
        $totpSecret = $totpService->generateSecret();
        $response = $user->register($data['username'], $data['password'], $totpSecret);

        if ($response !== true){
            echo json_encode(['message' => $response]);
        }
        else{
            $qrCode = $totpService->getQRCode($data['username'], $totpSecret);
            if ($qrCode) {
                echo json_encode(['message' => 'User registered successfully', 'totp_secret' => $totpSecret, 'qr_code' => base64_encode($qrCode)]);
            } else {
                echo json_encode(['message' => 'User registered, but QR code generation failed', 'totp_secret' => $totpSecret]);
            }
        }
        
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }

} elseif ($requestMethod == 'POST' && $requestUri == '/totp-api/src/index.php/verify') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['username']) && isset($data['password']) && isset($data['totp_code'])) {
        $userData = $user->getUser($data['username']);
        if ($userData == false){
            echo json_encode(['message' => 'Username was not found.']);
        }
        else{

        $passwordValidity = password_verify($data['password'], $userData['hashed_password']);
        echo "password".$passwordValidity;
        $totpValidity = $totpService->verify($userData['totp_secret'], $data['totp_code']);

            if ($passwordValidity && $totpValidity) {
                echo json_encode(['message' => 'User verified successfully']);
            } else {
                echo json_encode(['error' => 'Password or OTP code is invalid.']);
            }

        }   
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
} else {
    echo json_encode(['error' => 'Invalid endpoint']);
}
