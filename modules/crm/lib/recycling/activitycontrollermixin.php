<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Main;
use Bitrix\Crm;

trait ActivityControllerMixin
{
	use BaseControllerMixin;

	/**
	 * Prepare Entity Activity Data.
	 * There are Owned and Shared Activity Data will be returned.
	 * @param int $entityID Entity ID.
	 * @param array $params Additional params.
	 * @return array
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\SystemException
	 */
	protected function prepareActivityData($entityID, array $params = array())
	{
		$entityTypeID = $this->getEntityTypeID();
		$connection = Main\Application::getInstance()->getConnection();
		$activityDbResult = $connection->query("
			SELECT b1.ACTIVITY_ID, COUNT(*) MULTIPLIER FROM b_crm_act_bind b1
				INNER JOIN (SELECT ACTIVITY_ID FROM b_crm_act_bind WHERE OWNER_ID = {$entityID} AND OWNER_TYPE_ID = {$entityTypeID}) b2 
				ON b1.ACTIVITY_ID = b2.ACTIVITY_ID
				GROUP BY b1.ACTIVITY_ID
		");

		$ownedActivityIDs = array();
		$sharedActivityIDs = array();
		while($activityInfo = $activityDbResult->fetch())
		{
			$activityID = isset($activityInfo['ACTIVITY_ID']) ? (int)$activityInfo['ACTIVITY_ID'] : 0;
			if($activityID <= 0)
			{
				continue;
			}

			$multiplier = isset($activityInfo['MULTIPLIER']) ? (int)$activityInfo['MULTIPLIER'] : 0;
			if($multiplier > 1)
			{
				$sharedActivityIDs[] = $activityID;
			}
			else if($multiplier === 1)
			{
				$ownedActivityIDs[] = $activityID;
			}
		}

		return [
			'SHARED_ACTIVITY_IDS' => $sharedActivityIDs,
			'OWNED_ACTIVITY_IDS' => $ownedActivityIDs
		];
	}

	/**
	 * Suspend Entity Activities.
	 * Owned Entity Activities will be removed.
	 * @param array $entityData Suspended Entity Data.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function suspendActivities(array $entityData, $entityID, $recyclingEntityID)
	{
		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS']) ? $entityData['SLOTS'] : [];
		$ownedActivityIDs = isset($slots['OWNED_ACTIVITY_IDS']) && is_array($slots['OWNED_ACTIVITY_IDS'])
			? $slots['OWNED_ACTIVITY_IDS'] : [];

		foreach($ownedActivityIDs as $activityID)
		{
			\CCrmActivity::Delete(
				$activityID,
				false,
				true,
				[ 'ENABLE_RECYCLE_BIN' => true ]
			);
		}

		//Rebind custom activity relations
		Crm\Activity\Provider\ProviderManager::transferOwnership(
			$this->getEntityTypeID(),
			$entityID,
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID
		);
	}

	/**
	 * Recover Entity Suspended Activities.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $oldEntityID Old Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @param array $entityData Suspended Entity Data.
	 * @param RelationMap $relationMap Relation Map.
	 * @throws Main\AccessDeniedException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 */
	protected function recoverActivities($recyclingEntityID, $oldEntityID, $newEntityID, array $entityData, RelationMap $relationMap)
	{
		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS'])
			? $entityData['SLOTS'] : [];
		$ownedActivityIDs = isset($slots['OWNED_ACTIVITY_IDS']) && is_array($slots['OWNED_ACTIVITY_IDS'])
			? $slots['OWNED_ACTIVITY_IDS'] : [];

		foreach($ownedActivityIDs as $activityID)
		{
			Crm\Integration\Recyclebin\RecyclingManager::restoreRecycleBinEntity(
				$relationMap->resolveRecycleBinEntityID(\CCrmOwnerType::Activity, $activityID)
			);
		}

		//Shared activities are stored in relations together with owned activities
		$sharedActivityIDs = array_diff(
			$relationMap->getEntityIDs(\CCrmOwnerType::Activity),
			$ownedActivityIDs
		);

		//Check SHARED_ACTIVITY_IDS key for backward compatibility only (it is not exists in recent items)
		if(isset($slots['SHARED_ACTIVITY_IDS']) && is_array($slots['SHARED_ACTIVITY_IDS']))
		{
			$sharedActivityIDs = array_unique(array_merge($sharedActivityIDs, $slots['SHARED_ACTIVITY_IDS']));
		}

		$entityTypeID = $this->getEntityTypeID();
		foreach($sharedActivityIDs as $activityID)
		{
			$bindings = \CCrmActivity::GetBindings($activityID);
			//Check if it still exists.
			if(empty($bindings))
			{
				continue;
			}

			$bindings[] = array('OWNER_TYPE_ID' => $entityTypeID, 'OWNER_ID' => $newEntityID);
			$activityFields = array('BINDINGS' => $bindings);

			$communications = \CCrmActivity::GetCommunications($activityID);
			if(is_array($communications))
			{
				$changed = false;
				for($i = 0, $length = count($communications); $i < $length; $i++)
				{
					$commEntityTypeID = isset($communications[$i]['ENTITY_TYPE_ID'])
						? (int)$communications[$i]['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
					$commEntityID = isset($communications[$i]['ENTITY_ID'])
						? (int)$communications[$i]['ENTITY_ID'] : 0;

					if($commEntityTypeID !== $entityTypeID || $commEntityID !== $oldEntityID)
					{
						continue;
					}

					$communications[$i]['ENTITY_TYPE_ID'] = $entityTypeID;
					$communications[$i]['ENTITY_ID'] = $newEntityID;

					if(!$changed)
					{
						$changed = true;
					}
				}

				if($changed)
				{
					$activityFields['COMMUNICATIONS'] = $communications;
				}
			}

			\CCrmActivity::Update($activityID, $activityFields, false);
		}

		//Rebind custom activity relations
		Crm\Activity\Provider\ProviderManager::transferOwnership(
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			$this->getEntityTypeID(),
			$newEntityID
		);
	}

	/**
	 * Erase Entity Suspended Activities Data.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param array $entityData Suspended Entity Data.
	 * @param RelationMap $relationMap Relation Map.
	 * @throws Main\AccessDeniedException
	 * @throws Main\InvalidOperationException
	 * @throws Main\LoaderException
	 */
	protected function eraseActivities($recyclingEntityID, array $entityData, RelationMap $relationMap)
	{
		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS'])
			? $entityData['SLOTS'] : [];
		$ownedActivityIDs = isset($slots['OWNED_ACTIVITY_IDS']) && is_array($slots['OWNED_ACTIVITY_IDS'])
			? $slots['OWNED_ACTIVITY_IDS'] : [];

		foreach($ownedActivityIDs as $activityID)
		{
			Crm\Integration\Recyclebin\RecyclingManager::removeRecycleBinEntity(
				$relationMap->resolveRecycleBinEntityID(\CCrmOwnerType::Activity, $activityID)
			);
		}

		//Remove custom activity relations
		Crm\Activity\Provider\ProviderManager::deleteByOwner($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}
}