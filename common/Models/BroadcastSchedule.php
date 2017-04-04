<?php namespace Common\Models;

use MongoDB\BSON\UTCDateTime;

/**
 * Class BroadcastSchedule
 * @package Common\Models
 * @property int         utc_offset
 * @property int         status
 * @property UTCDateTime send_at
 */
class BroadcastSchedule extends ArrayModel
{

}
