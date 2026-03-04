<?php
/**
 * Two-Factor Authentication (TOTP) - Google Authenticator compatible
 * Implementation based on RFC 6238 (TOTP) and RFC 4226 (HOTP)
 * Pure PHP - no external dependencies
 */

class TwoFactorAuth
{
    private const SECRET_LENGTH = 20; // 160 bits
    private const CODE_DIGITS = 6;
    private const PERIOD = 30; // seconds
    private const ALGORITHM = 'sha1';
    private const WINDOW = 1; // Allow ±1 period for clock drift

    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random secret key
     */
    public static function generateSecret(): string
    {
        $bytes = random_bytes(self::SECRET_LENGTH);
        return self::base32Encode($bytes);
    }

    /**
     * Generate the otpauth:// URI for QR code scanning
     */
    public static function getOtpAuthUri(string $secret, string $accountName, string $issuer = 'RADIUS Manager'): string
    {
        $accountName = rawurlencode($accountName);
        $issuer = rawurlencode($issuer);
        return "otpauth://totp/{$issuer}:{$accountName}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=" . self::CODE_DIGITS . "&period=" . self::PERIOD;
    }

    /**
     * Verify a TOTP code
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = self::WINDOW): bool
    {
        if (strlen($code) !== self::CODE_DIGITS || !ctype_digit($code)) {
            return false;
        }

        $currentTimestamp = time();

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $timeSlice = floor(($currentTimestamp + ($i * self::PERIOD)) / self::PERIOD);
            $calculatedCode = self::generateCode($secret, $timeSlice);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given time slice
     */
    private static function generateCode(string $secret, int $timeSlice): string
    {
        $secretKey = self::base32Decode($secret);

        // Pack time as 8-byte big-endian
        $time = pack('N*', 0, $timeSlice);

        // HMAC-SHA1
        $hmac = hash_hmac(self::ALGORITHM, $time, $secretKey, true);

        // Dynamic truncation
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $value = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        );

        $code = $value % pow(10, self::CODE_DIGITS);

        return str_pad((string)$code, self::CODE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 encode binary data
     */
    private static function base32Encode(string $data): string
    {
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        $chunks = str_split($binary, 5);
        foreach ($chunks as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $result .= self::$base32Chars[bindec($chunk)];
        }

        return $result;
    }

    /**
     * Base32 decode to binary data
     */
    private static function base32Decode(string $data): string
    {
        $data = strtoupper($data);
        $data = rtrim($data, '=');

        $binary = '';
        foreach (str_split($data) as $char) {
            $pos = strpos(self::$base32Chars, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        $chunks = str_split($binary, 8);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }

        return $result;
    }
}
