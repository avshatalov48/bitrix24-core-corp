<?php
namespace Bitrix\Crm\Recycling;

use \Bitrix\Main;
use Bitrix\Crm;

class ActivityRelationManager extends BaseRelationManager
{
	/** @var ActivityRelationManager|null */
	protected static $instance = null;

	/**
	 * @return ActivityRelationManager
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ActivityRelationManager();
		}
		return self::$instance;
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Activity;
	}

	public function registerRecycleBin($recyclingEntityID, $entityID, array $recyclingData)
	{
		Relation::registerRecycleBin($this->getEntityTypeID(), $entityID, $recyclingEntityID);
	}

	public function buildCollection($entityID, array &$recyclingData)
	{
		$bindings = isset($recyclingData['BINDINGS']) && is_array($recyclingData['BINDINGS'])
			? $recyclingData['BINDINGS'] : array();
		unset($recyclingData['BINDINGS']);

		$relations = array();
		if(!empty($bindings))
		{
			foreach($bindings as $binding)
			{
				$relations[] = new Relation(
					(int)$binding['OWNER_TYPE_ID'],
					(int)$binding['OWNER_ID'],
					\CCrmOwnerType::Activity,
					$entityID
				);
			}
		}
		return $relations;
	}

	protected function prepareActivityRelations($entityTypeID, $entityID, array &$recyclingData, array &$relations)
	{
		throw new Main\InvalidOperationException('Operation is not supported in current context.');
	}
}