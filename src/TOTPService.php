<?php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TOTPService {
    private $secretLength = 16;
    private $digits = 6;
    private $period = 30;

    public function generateSecret() {
        $randomBytes = random_bytes($this->secretLength);
        return $this->base32Encode($randomBytes);
    }

    public function generateCode($secret, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $time = floor($timestamp / $this->period);
        $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $time), $this->base32Decode($secret), true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $code = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
        $code %= pow(10, $this->digits);
        return str_pad($code, $this->digits, '0', STR_PAD_LEFT);
    }

    public function verify($secret, $code, $timestamp = null) {
        return $this->generateCode($secret, $timestamp) === $code;
    }

    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';

        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $fiveBitBinaryArray = str_split($binaryString, 5);
        $base32 = '';
        foreach ($fiveBitBinaryArray as $fiveBitBinary) {
            $base32 .= $alphabet[bindec(str_pad($fiveBitBinary, 5, '0', STR_PAD_RIGHT))];
        }

        return $base32;
    }

    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';

        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
        }

        $eightBitBinaryArray = str_split($binaryString, 8);
        $decoded = '';
        foreach ($eightBitBinaryArray as $eightBitBinary) {
            if (strlen($eightBitBinary) == 8) {
                $decoded .= chr(bindec($eightBitBinary));
            }
        }

        return $decoded;
    }


    public function getQRCode($label, $secret) {
        try {

            $qrCode = new QrCode("otpauth://totp/{$label}?secret={$secret}");
            //otpauth://totp/testuser5?secret=J2P5GX7YT6AIADKSQOVQ3YEM7LPQNQTDNHHATQS4DBUNBWCA7GVTN6RHWBCACO74BIBRTMJAHDLAWNRBVZ26XQPL33VVUIO5TBN66JY
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            return $result->getString(); // This should return the binary PNG data
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
