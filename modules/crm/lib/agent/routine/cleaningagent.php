<?php
namespace Bitrix\Crm\Agent\Routine;

use Bitrix\Crm;
use Bitrix\Crm\Cleaning\Entity\CleaningTable;

class CleaningAgent extends Crm\Agent\AgentBase
{
	/** @var bool|null  */
	private static $isActive = null;

	public static function doRun()
	{
		$start = microtime(true);
		//\CCrmUtils::Trace("CleaningAgent: run", ConvertTimeStamp($now, 'FULL'), 1);
		$items = Crm\Cleaning\CleaningManager::getQueuedItems(50);
		foreach($items as $item)
		{
			$entityTypeID = (int)$item['ENTITY_TYPE_ID'];
			$entityID = (int)$item['ENTITY_ID'];

			$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
			if($entity !== null)
			{
				try
				{
					$entity->cleanup($entityID);
				}
				catch(\Exception $ex)
				{
				}
			}
			Crm\Cleaning\CleaningManager::unregister($entityTypeID, $entityID);
			$end = microtime(true);
			if(($end - $start) >= 1.0)
			{
				break;
			}
		}
		return true;
	}

	public static function isActive()
	{
		if(self::$isActive === null)
		{
			$dbResult = \CAgent::GetList(array('ID' => 'DESC'), array('NAME' => __CLASS__.'::run(%'));
			self::$isActive = is_array($dbResult->Fetch());
		}
		return self::$isActive;
	}

	public static function activate()
	{
		\CAgent::AddAgent(
			__CLASS__.'::run();',
			'crm',
			'N',
			10,
			'',
			'Y',
			ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL')
		);
	}
}
