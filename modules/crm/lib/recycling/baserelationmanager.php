<?php
namespace Bitrix\Crm\Recycling;

abstract class BaseRelationManager
{
	/**
	 * Get Entity Type ID
	 * @return int
	 */
	abstract public function getEntityTypeID();

	public function buildCollection($entityID, array &$recyclingData)
	{
		return [];
	}
	public function prepareRecoveryFields(array &$fields, RelationMap $map)
	{
	}
	public function recoverBindings($entityID, RelationMap $map)
	{
	}

	public function registerRecycleBin($recyclingEntityID, $entityID, array $recyclingData)
	{
		Relation::registerRecycleBin($this->getEntityTypeID(), $entityID, $recyclingEntityID);

		$ownedActivityIDs = isset($recyclingData['OWNED_ACTIVITY_IDS']) ? $recyclingData['OWNED_ACTIVITY_IDS'] : null;
		if(is_array($ownedActivityIDs))
		{
			foreach($ownedActivityIDs as $activityID)
			{
				Relation::registerRecycleBin(\CCrmOwnerType::Activity, $activityID, $recyclingEntityID);
			}
		}
	}

	protected function prepareActivityRelations($entityTypeID, $entityID, array &$recyclingData, array &$relations)
	{
		$sharedActivityIDs = isset($recyclingData['SHARED_ACTIVITY_IDS']) && is_array($recyclingData['SHARED_ACTIVITY_IDS'])
			? $recyclingData['SHARED_ACTIVITY_IDS'] : array();
		unset($recyclingData['SHARED_ACTIVITY_IDS']);

		if(!empty($sharedActivityIDs))
		{
			foreach($sharedActivityIDs as $activityID)
			{
				$relations[] = new Relation(
					$entityTypeID,
					$entityID,
					\CCrmOwnerType::Activity,
					$activityID
				);
			}
		}
	}
}