<?php

use Carbon\Carbon;
use MongoDB\BSON\UTCDatetime;

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


if (! function_exists('date_string_boundaries')) {
    /**
     * Return the lower and upper boundary of a date string (today, yesterday,... etc)
     *
     * @param string $date
     *
     * @return Carbon[]
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
}

if (! function_exists('date_boundaries')) {
    /**
     * Return the lower and upper boundary of a specific date or of a date string (today, yesterday,... etc)
     *
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
}


if (! function_exists('stable_usort')) {
    /**
     * Stable sort for PHP (on ties preserve input order)
     *
     * @see https://github.com/vanderlee/PHP-stable-sort-functions
     *
     * @param array $array
     * @param       $cmp
     *
     * @return bool
     */
    function stable_usort(array &$array, $cmp)
    {
        $index = 0;
        foreach ($array as &$item) {
            $item = [$index++, $item];
        }
        $result = usort($array, function ($a, $b) use ($cmp) {
            $result = call_user_func($cmp, $a[1], $b[1]);

            return $result == 0 ? $a[0] - $b[0] : $result;
        });
        foreach ($array as &$item) {
            $item = $item[1];
        }

        return $result;
    }
}

if (! function_exists('base64_url_decode')) {
    /**
     * A helper function used by function `parse_facebook_signed_request`
     *
     * @param $input
     *
     * @return string
     */
    function base64_url_decode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}

if (! function_exists('parse_Facebook_signed_request')) {
    /**
     * Parses a signed request from Facebook.
     *
     * @see https://developers.facebook.com/docs/games/gamesonfacebook/login#usingsignedrequest/
     * @see http://stackoverflow.com/a/32654932
     *
     * @param $signedRequest
     * @param $clientSecret
     *
     * @return mixed|null
     */
    function parse_Facebook_signed_request($signedRequest, $clientSecret)
    {
        list($encoded_sig, $payload) = explode('.', $signedRequest, 2);

        $secret = $clientSecret;

        $sig = base64_url_decode($encoded_sig);
        $data = json_decode(base64_url_decode($payload), true);

        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expected_sig) {
            return null;
        }

        return $data;
    }
}

if (! function_exists('notify_frontend')) {
    /**
     * @param string $channel
     * @param string $event
     * @param array  $data
     *
     * @return bool|string
     */
    function notify_frontend($channel, $event, array $data)
    {
        /** @type Pusher $pusher */
        $pusher = app('pusher');

        return $pusher->trigger($channel, $event, $data);
    }
}

if (! function_exists('to_bytes')) {

    /**
     * http://stackoverflow.com/a/11807179
     * @param $from
     *
     * @return double
     */
    function to_bytes($from)
    {
        $number = substr($from, 0, -2);
        switch (strtoupper(substr($from, -2))) {
            case "KB":
                return $number * 1024;
            case "MB":
                return $number * pow(1024, 2);
            case "GB":
                return $number * pow(1024, 3);
            case "TB":
                return $number * pow(1024, 4);
            case "PB":
                return $number * pow(1024, 5);
            default:
                return $from;
        }
    }
}

if (! function_exists('mongo_date')) {

    /**
     * @param Carbon|UTCDateTime|string|int|null $date
     *
     * @return UTCDateTime
     */
    function mongo_date($date = null)
    {
        if ($date === null) {
            return new UTCDateTime();
        }

        if (is_a($date, UTCDateTime::class)) {
            return $date;
        }

        if (is_a($date, DateTime::class)) {
            return new UTCDateTime(carbon_date($date)->getTimestamp() * 1000);
        }

        return $date;
    }
}


if (! function_exists('carbon_date')) {

    /**
     *
     * Jenssegers\Mongodb\Eloquent\Model::asDateTime
     * Illuminate\Database\Eloquent\Model::asDateTime
     *
     * @param $date
     *
     * @return Carbon
     * @throws Exception
     */
    function carbon_date($date)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($date instanceof Carbon) {
            return $date;
        }

        if (is_a($date, UTCDateTime::class)) {
            return Carbon::createFromTimestamp($date->toDateTime()->getTimestamp());
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($date instanceof DateTimeInterface) {
            return new Carbon(
                $date->format('Y-m-d H:i:s.u'), $date->getTimeZone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($date)) {
            return Carbon::createFromTimestamp($date);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date)) {
            return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        }

        throw new Exception("Unknown date: {$date}");
    }
}

if (! function_exists('change_date')) {

    function change_date(Carbon $dateTime, $changes)
    {
        $dateTime->addDays($changes['days']);
        $dateTime->addHours($changes['hours']);
        $dateTime->addMinutes($changes['minutes']);

        return $dateTime;
    }
}

