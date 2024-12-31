<?php

namespace Maltyst;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PublicUtils
{
    const TOKEN_ALGO = "sha3-512";

    public function __construct()
    {
        // No initialization required for now
    }

    public function createToken(): array
    {
        $emailConfirmationTokenClear  = bin2hex(random_bytes(32));
        $emailConfirmationTokenHash   = hash(self::TOKEN_ALGO, $emailConfirmationTokenClear);
        $emailConfirmationTokenPublic = self::TOKEN_ALGO . "__" . $emailConfirmationTokenClear;

        return [$emailConfirmationTokenHash, $emailConfirmationTokenPublic];
    }


    /**
     * Processes a public token and extracts its algorithm and the remaining hash part.
     *
     * @param string $token The public token to process.
     * @return array|null Returns an array with the remaining hash and the algorithm, or null if invalid.
     */
    public function processPublicToken(string $token): array
    {
        $tokenParts = explode('__', $token);
        if (count($tokenParts) !== 2) {
            return [null, null];
        }
        if ( !is_string($tokenParts[0]) || empty($tokenParts[0]) || !is_string($tokenParts[1]) || empty($tokenParts[1])) {
            return [null, null];
        }

        [$tokenAlgo, $emailConfirmationTokenClear] = $tokenParts;

        // Validate this is a supported algo
        $allAlgos = hash_algos();
        if (!in_array($tokenAlgo, $allAlgos)) {
            return [null, null];
        }

        return $tokenParts;
    }
}
