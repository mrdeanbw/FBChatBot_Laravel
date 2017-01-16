<?php namespace App\Services;

/**
 * Simple Base 62 ID to string, string to ID encryption class.
 * Class URLShortener
 * @package App\Services
 */
class SimpleEncryptionService
{

    /**
     * Shuffled Alphabet
     */
    CONST ALPHABET = "15IVxtzCpyG9mKS7qA8NvhWRLTleJM0wi2ZEa4DQb6nXfYjgusc3OoPHUBrdkF";

    /**
     * Length of the alphabet.
     */
    CONST BASE = 62;

    CONST MAX_ALLOWED_ID = 2000000000;

    /**
     * Encode the ID integer to a string.
     * @param int $id
     * @return bool|string
     */
    public static function encode($id)
    {
        if ($id > self::MAX_ALLOWED_ID) {
            return false;
        }

        $ret = "";
        while ($id > 0) {
            $ret .= self::ALPHABET[$id % self::BASE];
            $id = (int)($id / self::BASE);
        }

        return strrev($ret);
    }

    /**
     * Decode the string to integer.
     * @param $string
     * @return bool|int
     */
    public static function decode($string)
    {
        $id = 0;
        for ($i = 0; $i < strlen($string); $i++) {
            $id = $id * self::BASE + strpos(self::ALPHABET, $string[$i]);
            if ($id > self::MAX_ALLOWED_ID) {
                return false;
            }
        }

        return $id;
    }

}