<?php namespace App\Services;

class TimezoneService
{

    CONST UTC_OFFSETS = [-12, -11, -10, -9.5, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 8, 8.5, 8.75, 9, 9.5, 10, 10.5, 11, 12, 12.75, 13, 14];

    /**
     * @param $offset
     * @return double
     */
    public static function getNext($offset)
    {
        return array_first(self::UTC_OFFSETS, function ($current) use ($offset) {
            return $current > $offset;
        });
    }
}