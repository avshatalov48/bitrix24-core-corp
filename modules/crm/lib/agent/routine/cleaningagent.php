<?php
namespace Bitrix\Crm\Agent\Routine;

use Bitrix\Crm;

class CleaningAgent extends Crm\Agent\AgentBase
{
	/** @var bool|null  */
	private static $isActive = null;

	public static function doRun()
	{
		$start = microtime(true);
		//\CCrmUtils::Trace("CleaningAgent: run", ConvertTimeStamp($now, 'FULL'), 1);
		$items = Crm\Cleaning\CleaningManager::getQueuedItems(50);
		if(empty($items))
		{
			return false;
		}

		foreach($items as $item)
		{
			$entityTypeID = (int)$item['ENTITY_TYPE_ID'];
			$entityID = (int)$item['ENTITY_ID'];

			if (\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeID))
			{
				$cleaner = Crm\Cleaning\CleaningManager::getCleaner($entityTypeID, $entityID);
				$cleaner->getOptions()->setEnvironment(Crm\Cleaning\Cleaner\Options::ENVIRONMENT_AGENT);

				try
				{
					$cleaner->cleanup();
				}
				catch (\Throwable $throwable)
				{
				}
			}
			else
			{
				//todo remove this branch after implementing factories for all entity types
				$entity = Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
				if($entity !== null)
				{
					try
					{
						$entity->cleanup($entityID);
					}
					catch(\Throwable $throwable)
					{
					}
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
		self::$isActive = !!(\CAgent::AddAgent(
			__CLASS__.'::run();',
			'crm',
			'N',
			10,
			'',
			'Y',
			ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL')
		));
	}
}
