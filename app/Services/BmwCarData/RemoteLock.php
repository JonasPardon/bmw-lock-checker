<?php

namespace App\Services\BmwCarData;

/**
 * Deliberately not implemented: documents the feasibility finding.
 */
final class RemoteLock
{
    public const REASON = 'BMW CarData is a read-only data API (EU Data Act). Its swagger defines only GET '
        .'endpoints plus container create/delete; there is no remote-service/command endpoint. Remote lock '
        .'lives in the MyBMW backend, which BMW closed to third parties on 2025-09-29, and the My BMW app\'s '
        .'Siri Shortcuts lock action requires Digital Key Plus (iDrive 8+), unavailable on iDrive 7.5. See README.md.';

    public static function result(string $vin): array
    {
        return ['vin' => $vin, 'supported' => false, 'reason' => self::REASON];
    }
}
