<?php

namespace Maltyst;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Utils
{
    public function __construct()
    {
        // No initialization required for now
    }

    /**
     * Processes a public token and extracts its algorithm and the remaining hash part.
     *
     * @param string $token The public token to process.
     * @return array|null Returns an array with the remaining hash and the algorithm, or null if invalid.
     */
    public function processPublicToken(string $token): ?array
    {
        if (mb_strlen($token) < 10) {
            return null;
        }

        $algorithms = [
            'sha512' => 6,
            'sha256' => 6,
            'md5'    => 3,
        ];

        foreach ($algorithms as $algo => $prefixLength) {
            if (mb_substr($token, 0, $prefixLength) === $algo) {
                $rest = mb_substr($token, $prefixLength);
                return [$rest, $algo];
            }
        }

        return null;
    }
}
