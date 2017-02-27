<?php
namespace Modules\Monitor\Repositories;
use DB;
class DBRepository
{


	/**
	 * retrive the slow queries info 
	 * @param type|int $num 
	 * @return Collection
	 */
	public function getSlowQueries($num = 10)
	{
		return DB::collection('system.profile')
					->where('millis','>',100)
					->orderBy('millis','desc')
					->take($num)
					->get();
	} 

	/**
	 * get the last queries
	 * @param type|int $num 
	 * @return Collection
	 */
	public function getLatestQueries($num = 15)
	{
		$db = DB::getMongoDB();
		return DB::collection('system.profile')
					->where('op','!=','command')
					->where('ns','!=',$db.'.system.profile')
					->orderBy('ts','desc')
					->take($num)
					->get();
	}

	/**
	 * Get general database info
	 * @return type
	 */
	public function getDBInfo()
	{
		$db = DB::getMongoDB();
		$cursor = $db->command(['dbStats'=>1]);
		$info = $cursor->toArray()[0];
		return $info;
	}

}