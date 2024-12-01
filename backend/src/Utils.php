<?php if ( ! defined( 'ABSPATH' ) ) exit;

class Utils
{
    public function __construct()
    {

    }

    public function processPublicToken($token)
    {
        if (!is_string($token) || (mb_strlen($token) < 10)) {
            return null;
        } else if (mb_substr($token, 0, 6) === 'sha512') {
            $algo = 'sha512';
            $rest = mb_substr($token, 6);
        } else if (mb_substr($token, 0, 6) === 'sha256') {
            $algo = 'sha256';
            $rest = mb_substr($token, 6);
        } else if (mb_substr($token, 0, 3) === 'md5') {
            $algo = 'md5';
            $rest = mb_substr($token, 3);
        } else {
            return null;
        }
        
        return array($rest, $algo);
    }
}




