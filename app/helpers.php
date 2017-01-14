<?php

use Carbon\Carbon;

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param  string $path
     *
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}
if (! function_exists('public_path')) {
    /**
     * Get the public path.
     *
     * @param  string $path
     *
     * @return string
     */
    function public_path($path = '')
    {
        return app()->basePath() . '/public' . ($path ? '/' . $path : $path);
    }
}

/**
 * @param $date
 *
 * @return array
 * @throws Exception
 */
function date_string_boundaries($date)
{
    $today = Carbon::today();
    $tomorrow = Carbon::tomorrow();
    $thisMonthBeginning = (new Carbon('first day of this month midnight'));

    switch ($date) {
        case 'today':
            return [$today, $tomorrow];
        case 'yesterday':
            return [Carbon::yesterday(), $today];
        case 'last_seven_days':
            return [(new Carbon('today'))->subDays(6), $tomorrow];
        case 'last_thirty_days':
            return [(new Carbon('today'))->subDays(29), $tomorrow];
        case 'this_month':
            return [$thisMonthBeginning, $tomorrow];
        case 'last_month':
            return [(new Carbon('first day of last month midnight')), $thisMonthBeginning];
        default:
            throw new \Exception("Unknown Date String {$date}");
    }
}

/**
 * @param Carbon|string $date
 *
 * @return Carbon[]
 * @throws \Exception
 */
function date_boundaries($date)
{
    if (is_string($date)) {
        return date_string_boundaries($date);
    }

    return [$date, $date->copy()->addDay()];
}


/**
 * @param        $array
 * @param string $attribute
 *
 * @return array
 */
function extract_attribute($array, $attribute = 'id')
{
    $attributes = array_map(function ($object) use ($attribute) {
        return $object[$attribute];
    }, (array)$array);

    return $attributes;
}

function stable_usort(array &$array, $value_compare_func)
{
    $index = 0;
    foreach ($array as &$item) {
        $item = [$index++, $item];
    }
    $result = usort($array, function ($a, $b) use ($value_compare_func) {
        $result = call_user_func($value_compare_func, $a[1], $b[1]);

        return $result == 0 ? $a[0] - $b[0] : $result;
    });
    foreach ($array as &$item) {
        $item = $item[1];
    }

    return $result;
}


function parse_signed_request($signed_request)
{
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);

    $secret = config('services.facebook.client_secret');

    $sig = base64_url_decode($encoded_sig);
    $data = json_decode(base64_url_decode($payload), true);

    $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
    if ($sig !== $expected_sig) {
        return null;
    }

    return $data;
}

function base64_url_decode($input)
{
    return base64_decode(strtr($input, '-_', '+/'));
}
