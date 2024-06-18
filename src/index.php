<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/database.php';
require __DIR__ . '/User.php';
require __DIR__ . '/TOTPService.php';


use Firebase\JWT\JWT;
use Firebase\JWT\Key;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = (new Database())->getPdo();
$user = new User($pdo);
$totpService = new TOTPService();


$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function validateJWT($jwt){
    $secretKey  = '06d3567ee8c898698848bca1e6bee1f81ef01072b351d5f781dc0c61c8e421de';
    $token = JWT::decode($jwt, new Key($secretKey, 'HS512'));
    $now = new DateTimeImmutable();
    $serverName = "localhost";

    if ($token->iss !== $serverName || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp())
    {
        return false;
    }
    return true;

}



function getJWT($username) {
    $secretKey  = '06d3567ee8c898698848bca1e6bee1f81ef01072b351d5f781dc0c61c8e421de';

    $issuedAt   = new DateTimeImmutable();
    $expire     = $issuedAt->modify('+1 minute')->getTimestamp(); 
    $serverName = "localhost";                      

    $data = [
        'iat'  => $issuedAt->getTimestamp(),   
        'iss'  => $serverName,              
        'nbf'  => $issuedAt->getTimestamp(),     
        'exp'  => $expire,                     
        'userName' => $username,          
    ];
    return JWT::encode(
        $data,
        $secretKey,
        'HS512'
    );
}





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
        
        $passwordValidity = password_verify($data['password'].$userData['salt'], $userData['hashed_password']);

        $totpValidity = $totpService->verify($userData['totp_secret'], $data['totp_code']);

            if ($passwordValidity && $totpValidity) {
                $jwt = getJWT($data['username']);
                echo json_encode(['token' => $jwt, 'message' => 'User verified successfully']);
            } else {
                echo json_encode(['error' => 'Password or OTP code is invalid.']);
            }

        }   
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
} 

elseif ($requestMethod == 'POST' && $requestUri == '/totp-api/src/index.php/jwt-validate') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['token'])) {
        //echo $data['token'];
        try{
            $response = validateJWT($data['token']);
        }
        catch (Exception $exc){
            $response = false;
        }

        if ($response == false){
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['message' => 'JWT is invalid','valid' => false]);
        }
        else{
            header('HTTP/1.1 200 Authorized');
            echo json_encode(['message' => 'JWT is valid','valid' => true]);

        }   
    } else {
        header('HTTP/1.1 302 Unauthorized');
        echo json_encode(['message' => 'No JWT was sent','valid' => false]);
    }
} 

elseif ($requestMethod == 'POST' && $requestUri == '/totp-api/src/index.php/login') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['username']) && isset($data['password'])) {
        $userData = $user->getUser($data['username']);
        if ($userData == false){
            echo json_encode(['message' => 'Username was not found.']);
        }
        else{
        $passwordValidity = password_verify($data['password'].$userData['salt'], $userData['hashed_password']);
        echo "password".$passwordValidity;
            if ($passwordValidity) {
                echo json_encode(['message' => 'User cridentials are valid.']);
            } else {
                echo json_encode(['error' => 'Password is invalid.']);
            }

        }   
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
}
else {
    echo json_encode(['error' => 'Invalid endpoint']);
}