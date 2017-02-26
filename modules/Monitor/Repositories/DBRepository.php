<?php
namespace Modules\Monitor\Repositories;
use DB;
class DBRepository
{
	protected $collection;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->collection = DB::collection('system.profile');
	}

	/**
	 * retrive the slow queries info 
	 * @param type|int $num 
	 * @return Collection
	 */
	public function getSlowQueries($num = 10)
	{
		return $this->collection->orderBy('millis','desc')->take($num)->get();
	} 

	/**
	 * get the last queries
	 * @param type|int $num 
	 * @return Collection
	 */
	public function getLatestQueries($num = 15)
	{
		return $this->collection->orderBy('ts','desc')->take($num)->get();
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