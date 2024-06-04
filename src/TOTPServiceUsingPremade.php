<?php
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TOTPService {
    public function generateSecret() {
        $totp = TOTP::create();
        return $totp->getSecret();
    }

    public function verify($secret, $code) {
        $totp = TOTP::create($secret);
        return $totp->verify($code);
    }

    public function getQRCode($label, $secret) {
        try {
            $totp = TOTP::create($secret);
            $totp->setLabel($label);
            $qrCode = new QrCode($totp->getProvisioningUri());
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            return $result->getString(); // This should return the binary PNG data
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
