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
		$cursor = $db->command(['dbStats'=>1])->toArray();

		$info = $cursor[0];
		return $info;
	}

	public function getIndexesDetails()
	{
		$db = DB::getMongoDB();
		
		//retrive collections
		$indexes = [];
		foreach($db->listCollections() as $collectionInfo)
		{
			$name = $collectionInfo->getName();
			if($name == 'system.profile') continue;
			$colStats = $db->command([
				'collStats'=>$name
			])->toArray();

			
			$indexes[$name] = [
				'size'=>$colStats[0]['size'],
				'totalIndexSize'=>$colStats[0]['totalIndexSize'],
				'indexSizes'=>$colStats[0]['indexSizes']
			];
		}

		return $indexes;
	}

}