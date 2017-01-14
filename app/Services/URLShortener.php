<?php
namespace App\Services;

class URLShortener
{

    CONST ALPHABET = "15IVxtzCpyG9mKS7qA8NvhWRLTleJM0wi2ZEa4DQb6nXfYjgusc3OoPHUBrdkF";
    CONST BASE = 62;
    CONST MAX_ALLOWED_ID = 2000000000;

    public static function encode($id)
    {
        if ($id > self::MAX_ALLOWED_ID){
            return false;
        }

        $ret = "";
        while ($id > 0) {
            $ret .= self::ALPHABET[$id % self::BASE];
            $id = (int)($id / self::BASE);
        }

        return strrev($ret);
    }

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