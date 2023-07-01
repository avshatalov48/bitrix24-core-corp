<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Crm\Timeline\Entity\NoteTable;
use Bitrix\Main;
use Bitrix\Recyclebin;

Main\Localization\Loc::loadMessages(__FILE__);

class ActivityController extends BaseController
{
	/** @var ActivityController|null  */
	protected static $instance = null;

	/** @var Array<int, int> */
	private array $entityIdToRecyclingEntityId = [];

	/**
	 * @return ActivityController|null
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new ActivityController();
		}
		return self::$instance;
	}

	public static function getFieldNames()
	{
		return [
			'ID', 'TYPE_ID',
			'PROVIDER_ID', 'PROVIDER_TYPE_ID', 'PROVIDER_GROUP_ID',
			'OWNER_TYPE_ID', 'OWNER_ID',
			'ASSOCIATED_ENTITY_ID', 'CALENDAR_EVENT_ID',
			'SUBJECT', 'IS_HANDLEABLE', 'COMPLETED', 'STATUS',
			'RESPONSIBLE_ID', 'PRIORITY', 'NOTIFY_TYPE', 'NOTIFY_VALUE',
			'DESCRIPTION', 'DESCRIPTION_TYPE', 'DIRECTION', 'LOCATION',
			'CREATED', 'LAST_UPDATED', 'START_TIME', 'END_TIME', 'DEADLINE',
			'STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS', 'PARENT_ID', 'THREAD_ID', 'URN', 'SETTINGS',
			'ORIGINATOR_ID', 'ORIGIN_ID', 'AUTHOR_ID', 'EDITOR_ID', 'PROVIDER_PARAMS',
			'RESULT_STATUS', 'RESULT_STREAM', 'RESULT_SOURCE_ID', 'RESULT_MARK', 'RESULT_VALUE', 'RESULT_SUM', 'RESULT_CURRENCY_ID',
			'AUTOCOMPLETE_RULE', 'IS_INCOMING_CHANNEL', 'PROVIDER_DATA',
		];
	}

	/**
	 * Get Entity Type ID
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Activity;
	}

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	public function getSuspendedEntityTypeID()
	{
		return \CCrmOwnerType::SuspendedActivity;
	}

	/**
	 * Get recyclebin entity type name.
	 * @see \Bitrix\Crm\Integration\Recyclebin\Company::getEntityName
	 * @return string
	 */
	public function getRecyclebinEntityTypeName()
	{
		return 'crm_activity';
	}

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		return Main\Localization\Loc::getMessage(
			'CRM_ACTIVITY_CTRL_OWNER_NOT_FOUND',
			[ '#TYPE_NANE#' => \CCrmOwnerType::GetDescription($entityTypeID), '#ID#' => $entityID ]
		);
	}

	public function getEntityFields($entityID)
	{
		$dbResult = \CCrmActivity::GetList(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*')
		);
		$fields = $dbResult->Fetch();
		if (is_array($fields))
		{
			$fields['IS_INCOMING_CHANNEL'] = \Bitrix\Crm\Activity\IncomingChannel::getInstance()->isIncomingChannel((int)$entityID) ? 'Y' : 'N';

			return $fields;
		}

		return null;
	}

	public function prepareEntityData($entityID, array $params = array())
	{
		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(empty($fields))
		{
			$fields = $this->getEntityFields($entityID);
		}

		if(empty($fields))
		{
			throw new Main\ObjectNotFoundException("Could not find entity: #{$entityID}.");
		}

		$slots = array('FIELDS' => array_intersect_key($fields, array_flip(self::getFieldNames())));

		if(!isset($params['ENABLE_COMMUNICATIONS']) || $params['ENABLE_COMMUNICATIONS'])
		{
			$slots['COMMUNICATIONS'] = \CCrmActivity::GetCommunications(
				$entityID,
				0,
				array('ENTITY_SETTINGS' => false)
			);
		}

		if(!isset($params['ENABLE_BINDINGS']) || $params['ENABLE_BINDINGS'])
		{
			$slots['BINDINGS'] = \CCrmActivity::GetBindings($entityID);
		}

		return array(
			'TITLE' => isset($fields['SUBJECT']) ? $fields['SUBJECT'] : "Activity #{$entityID}",
			'SLOTS' => $slots
		);
	}

	public function moveToBin($entityID, array $params = array())
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			throw new Main\InvalidOperationException("Could not load module RecycleBin.");
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(empty($fields))
		{
			$fields = $params['FIELDS'] = $this->getEntityFields($entityID);
		}

		if(empty($fields))
		{
			throw new Main\ObjectNotFoundException("Could not find entity: #{$entityID}.");
		}

		if(!self::lockItem($entityID))
		{
			return new Main\Result();
		}

		$entityData = $this->prepareEntityData(
			$entityID,
			array_merge($params, [ 'ENABLE_BINDINGS' => true ])
		);

		$recyclingEntity = Crm\Integration\Recyclebin\Activity::createRecycleBinEntity($entityID);
		$recyclingEntity->setTitle($entityData['TITLE']);

		$slots = isset($entityData['SLOTS']) && is_array($entityData['SLOTS']) ? $entityData['SLOTS'] : array();
		$this->notifyTimelineMonitorAboutMoveToBin($slots['BINDINGS'] ?? []);

		$relations = ActivityRelationManager::getInstance()->buildCollection($entityID, $slots);
		foreach($slots as $slotKey => $slotData)
		{
			$recyclingEntity->add($slotKey, $slotData);
		}

		//region Files
		\CCrmActivity::PrepareStorageElementIDs($fields);
		$storageElementIDs = isset($fields['STORAGE_ELEMENT_IDS']) && is_array($fields['STORAGE_ELEMENT_IDS'])
			? $fields['STORAGE_ELEMENT_IDS'] : [];
		if(!empty($storageElementIDs))
		{
			$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
				? (int)$fields['STORAGE_TYPE_ID'] : \CCrmActivity::GetDefaultStorageTypeID();
			$storageTypeName = Crm\Integration\StorageType::resolveName($storageTypeID);
			foreach($storageElementIDs as $storageElementID)
			{
				$recyclingEntity->addFile($storageElementID, $storageTypeName);
			}
		}
		//endregion

		$saveResult = $recyclingEntity->save();
		$saveErrors = $saveResult->getErrors();
		if(!empty($saveErrors))
		{
			throw new Main\SystemException($saveErrors[0]->getMessage(), $saveErrors[0]->getCode());
		}

		$recyclingEntityID = $recyclingEntity->getId();
		$this->entityIdToRecyclingEntityId[$entityID] = $recyclingEntityID;

		//region Relations
		foreach($relations as $relation)
		{
			/** @var Relation $relation */
			$relation->setRecycleBinID(\CCrmOwnerType::Activity, $entityID, $recyclingEntityID);
			$relation->save();
		}
		ActivityRelationManager::getInstance()->registerRecycleBin($recyclingEntityID, $entityID, $slots);
		//endregion

		//region Convert User Fields to Suspended Type
		$suspendedUserFields = $this->prepareSuspendedUserFields($entityID);
		if(!empty($suspendedUserFields))
		{
			$this->saveSuspendedUserFields($recyclingEntityID, $suspendedUserFields);
		}
		//endregion

		$this->suspendTimeline($entityID, $recyclingEntityID);
		$this->suspendLiveFeed($entityID, $recyclingEntityID);
		$this->suspendBadges((int)$entityID, (int)$recyclingEntityID);
		$this->suspendNotes((int)$entityID, (int)$recyclingEntityID);

		\CCrmActivity::DoDeleteElementIDs($entityID);

		$result = new Main\Result();
		$result->setData([ 'recyclingEntityId' => $recyclingEntityID ]);
		$provider = \CCrmActivity::GetActivityProvider($fields);
		if($provider)
		{
			$providerResult = $provider::processMovingToRecycleBin(
				$fields,
				[ 'deletionParams' => [ 'MOVED_TO_RECYCLE_BIN' => true ] ]
			);
			if($providerResult->isSuccess())
			{
				$result->setData(
					array_merge(
						$result->getData(),
						$providerResult->getData()
					)
				);
			}
		}
		self::unlockItem($entityID);

		$this->fireAfterMoveToBinEvent($entityID, $recyclingEntityID);
		return $result;
	}

	public function recover($entityID, array $params = array())
	{
		if($entityID <= 0)
		{
			return false;
		}

		$recyclingEntityID = isset($params['ID']) ? (int)$params['ID'] : 0;
		if($recyclingEntityID <= 0)
		{
			return false;
		}

		$slots = isset($params['SLOTS']) ? $params['SLOTS'] : null;
		if(!is_array($slots))
		{
			return false;
		}

		$fields = isset($slots['FIELDS']) ? $slots['FIELDS'] : null;
		if(!(is_array($fields) && !empty($fields)))
		{
			return false;
		}

		unset($fields['ID'], $fields['COMPANY_ID'], $fields['COMPANY_IDS'], $fields['LEAD_ID']);

		$relationMap = RelationMap::createByEntity(\CCrmOwnerType::Activity, $entityID, $recyclingEntityID);
		$relationMap->build();

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : 0;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		if($ownerTypeID > 0 && $ownerID > 0)
		{
			$newOwnerID = $relationMap->findRenewedEntityID($ownerTypeID, $ownerID);
			if($newOwnerID > 0)
			{
				$fields['OWNER_ID'] = $ownerID = $newOwnerID;
			}

			if(empty(Crm\Entity\EntityManager::selectExisted($ownerTypeID, [ $ownerID ])))
			{
				$errorMessage = '';

				$controller = ControllerManager::resolveController($ownerTypeID);
				if($controller)
				{
					$errorMessage = $controller->getActivityOwnerNotFoundMessage(
						$ownerTypeID,
						$ownerID,
						[
							'ID' => $entityID,
							'title' => isset($params['ENTITY']) ? $params['ENTITY']->getTitle() : ''
						]
					);
				}

				if($errorMessage === '')
				{
					$errorMessage = $this->getActivityOwnerNotFoundMessage($ownerTypeID, $ownerID, []);
				}

				throw new Main\InvalidOperationException($errorMessage);
			}
		}

		ActivityRelationManager::getInstance()->prepareRecoveryFields($fields, $relationMap);

		//region Convert User Fields from Suspended Type
		$userFields = $this->prepareRestoredUserFields($recyclingEntityID);
		if(!empty($userFields))
		{
			$fields = array_merge($fields, $userFields);
		}
		//endregion

		$communications = isset($slots['COMMUNICATIONS'])
			? $slots['COMMUNICATIONS'] : null;
		if(is_array($communications))
		{
			for($i = 0, $length = count($communications); $i < $length; $i++)
			{
				$commEntityTypeID = isset($communications[$i]['ENTITY_TYPE_ID'])
					? (int)$communications[$i]['ENTITY_TYPE_ID'] : \CCrmOwnerType::Undefined;
				$commEntityID = isset($communications[$i]['ENTITY_ID'])
					? (int)$communications[$i]['ENTITY_ID'] : 0;

				if(!$relationMap->isEmpty())
				{
					$newCommEntityID = $relationMap->findRenewedEntityID($commEntityTypeID, $commEntityID);
					if($newCommEntityID > 0)
					{
						$communications[$i]['ENTITY_ID'] = $newCommEntityID;
					}
				}
			}
			$fields['COMMUNICATIONS'] = $communications;
		}

		$bindingMap = array();

		if($ownerTypeID > 0 && $ownerID > 0)
		{
			$bindingMap["{$ownerTypeID}_{$ownerID}"] = array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID);
		}

		$entityInfos = array_merge(
			$relationMap->getSourceEntityInfos(),
			$relationMap->getDestinationEntityInfos()
		);
		foreach($entityInfos as $entityInfo)
		{
			$bindingKey = "{$entityInfo['ENTITY_TYPE_ID']}_{$entityInfo['ENTITY_ID']}";
			if(!isset($bindingMap[$bindingKey]))
			{
				$bindingMap[$bindingKey] = array(
					'OWNER_TYPE_ID' => $entityInfo['ENTITY_TYPE_ID'],
					'OWNER_ID' => $entityInfo['ENTITY_ID']
				);
			}
		}
		$fields['BINDINGS'] = array_values($bindingMap);

		//region Files
		$files = isset($params['FILES']) ? $params['FILES'] : null;
		if(is_array($files))
		{
			$storageElementIDs = [];
			foreach($files as $file)
			{
				$storageElementIDs[] = (int)$file['FILE_ID'];
			}
			$fields['STORAGE_ELEMENT_IDS'] = $storageElementIDs;
		}
		//endregion

		$newEntityID = 0;

		$provider = \CCrmActivity::GetActivityProvider($fields);
		if($provider)
		{
			$result = $provider::processRestorationFromRecycleBin(
				$fields,
				[ 'creationParams' => [ 'IS_RESTORATION' => true, 'DISABLE_USER_FIELD_CHECK' => true ] ]
			);
			if($result->isSuccess())
			{
				$resultData = $result->getData();
				if(is_array($resultData) && isset($resultData['entityId']))
				{
					$newEntityID = $resultData['entityId'];
				}
			}
		}

		if($newEntityID <= 0)
		{
			$newEntityID = \CCrmActivity::Add(
				$fields,
				false,
				false,
				array(
					'IS_RESTORATION' => true,
					'DISABLE_USER_FIELD_CHECK' => true
				)
			);
		}

		if($newEntityID <= 0)
		{
			return false;
		}

		$this->notifyTimelineMonitorAboutMoveFromBin($fields['BINDINGS'] ?? []);

		//region Relations
		ActivityRelationManager::getInstance()->recoverBindings($newEntityID, $relationMap);
		Relation::updateEntityID(\CCrmOwnerType::Activity, $entityID, $newEntityID, $recyclingEntityID);
		//endregion

		$this->eraseSuspendedUserFields($recyclingEntityID);

		$this->recoverTimeline($recyclingEntityID, $newEntityID);
		$this->recoverLiveFeed($recyclingEntityID, $newEntityID);
		$this->recoverBadges((int)$recyclingEntityID, (int)$newEntityID);
		$this->recoverNotes((int)$recyclingEntityID, (int)$newEntityID);

		//region Relations
		Relation::unregisterRecycleBin($recyclingEntityID);
		Relation::deleteJunks();
		//endregion

		unset($this->entityIdToRecyclingEntityId[$entityID]);
		$this->rebuildSearchIndex($newEntityID);
		$this->fireAfterRecoverEvent($recyclingEntityID, $newEntityID);
		return true;
	}

	public function erase($entityID, array $params = [])
	{
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'entityID');
		}

		$recyclingEntityID = isset($params['ID']) ? (int)$params['ID'] : 0;
		if($recyclingEntityID <= 0)
		{
			throw new Main\ArgumentException('Could not find parameter named: "ID".', 'params');
		}

		$slots = isset($params['SLOTS']) && is_array($params['SLOTS']) ? $params['SLOTS'] : [];
		$fields = isset($slots['FIELDS']) && is_array($slots['FIELDS']) ? $slots['FIELDS'] : [];

		$this->eraseSuspendedTimeline($recyclingEntityID);
		$this->eraseSuspendedLiveFeed($recyclingEntityID);
		$this->eraseSuspendedUserFields($recyclingEntityID);
		$this->eraseSuspendedBadges($recyclingEntityID);
		$this->eraseSuspendedNotes($recyclingEntityID);

		//region Files
		$files = isset($params['FILES']) ? $params['FILES'] : null;
		if(is_array($files))
		{
			$storageElementIDs = [];
			foreach($files as $file)
			{
				$storageElementIDs[] = (int)$file['FILE_ID'];
			}

			$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
				? (int)$fields['STORAGE_TYPE_ID'] : \CCrmActivity::GetDefaultStorageTypeID();
			\CCrmActivity::DoDeleteStorageElements($storageTypeID, $storageElementIDs);
		}
		//endregion

		$provider = \CCrmActivity::GetActivityProvider($fields);
		$associatedEntityID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
		if($provider && $associatedEntityID > 0)
		{
			$deleteParams = ['IS_ERASING_FROM_RECYCLE_BIN' => true];
			if ($provider === Crm\Activity\Provider\Task::class)
			{
				$deleteParams['SKIP_TASKS'] = $params['SKIP_TASKS'] ?? true;
			}
			$provider::deleteAssociatedEntity($associatedEntityID, $fields, $deleteParams);
		}

		Relation::deleteByRecycleBin($recyclingEntityID);

		unset($this->entityIdToRecyclingEntityId[$entityID]);
		$this->fireAfterEraseEvent($recyclingEntityID);
	}

	//region Timeline
	/**
	 * Suspend entity timeline.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\Db\SqlQueryException
	 */
	protected function suspendTimeline($entityID, $recyclingEntityID)
	{
		Crm\Timeline\TimelineManager::transferAssociation(
			$this->getEntityTypeID(), $entityID,
			$this->getSuspendedEntityTypeID(), $recyclingEntityID
		);
	}

	/**
	 * Recover entity timeline.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @param array $params Params are required for timeline synchronization.
	 * @throws Main\Db\SqlQueryException
	 */
	protected function recoverTimeline($recyclingEntityID, $newEntityID, array $params = array())
	{
		//Timeline synchronization is not required for activities
		Crm\Timeline\TimelineManager::transferAssociation(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);
	}

	/**
	 * Erase Suspended Entity Timeline.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedTimeline($recyclingEntityID)
	{
		Crm\Timeline\TimelineEntry::deleteByAssociatedEntity($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}
	//endregion

	//region Badges
	protected function suspendBadges(int $entityId, int $recyclingEntityId): void
	{
		Badge::rebindSource(
			$this->getSourceIdentifier($entityId),
			$this->getSuspendedSourceIdentifier($recyclingEntityId),
		);
	}

	protected function recoverBadges(int $recyclingEntityId, int $newEntityId): void
	{
		Badge::rebindSource(
			$this->getSuspendedSourceIdentifier($recyclingEntityId),
			$this->getSourceIdentifier($newEntityId),
		);
	}

	protected function eraseSuspendedBadges(int $recyclingEntityId): void
	{
		Badge::deleteBySource($this->getSuspendedSourceIdentifier($recyclingEntityId));
	}

	private function getSourceIdentifier(int $entityId): Crm\Badge\SourceIdentifier
	{
		return new Crm\Badge\SourceIdentifier(
			Crm\Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			$this->getEntityTypeID(),
			$entityId,
		);
	}

	private function getSuspendedSourceIdentifier(int $recyclingEntityId): Crm\Badge\SourceIdentifier
	{
		return new Crm\Badge\SourceIdentifier(
			Crm\Badge\SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityId,
		);
	}
	//endregion

	final public function getRecyclingEntityId(int $entityId): int
	{
		if (isset($this->entityIdToRecyclingEntityId[$entityId]))
		{
			return (int)$this->entityIdToRecyclingEntityId[$entityId];
		}

		if (Main\Loader::includeModule('recyclebin'))
		{
			$row =
				Recyclebin\Internals\Models\RecyclebinTable::query()
					->setSelect(['ID'])
					->where('ENTITY_TYPE', $this->getRecyclebinEntityTypeName())
					->where('ENTITY_ID', $entityId)
					->setLimit(1)
					->fetchObject()
			;

			$recyclingEntityId = $row ? (int)$row->getId() : 0;
		}
		else
		{
			$recyclingEntityId = 0;
		}

		$this->entityIdToRecyclingEntityId[$entityId] = $recyclingEntityId;

		return $recyclingEntityId;
	}

	//region Notes
	protected function suspendNotes(int $entityId, int $recyclingEntityId): void
	{
		NoteTable::rebind(NoteTable::NOTE_TYPE_ACTIVITY, $entityId, NoteTable::NOTE_TYPE_SUSPENDED_ACTIVITY, $recyclingEntityId);
	}

	protected function recoverNotes(int $recyclingEntityId, int $newEntityId): void
	{
		NoteTable::rebind(NoteTable::NOTE_TYPE_SUSPENDED_ACTIVITY, $recyclingEntityId, NoteTable::NOTE_TYPE_ACTIVITY, $newEntityId);
	}

	protected function eraseSuspendedNotes(int $recyclingEntityId): void
	{
		NoteTable::deleteByItemId(NoteTable::NOTE_TYPE_SUSPENDED_ACTIVITY, $recyclingEntityId);
	}
	//endregion

	protected function notifyTimelineMonitorAboutMoveToBin(array $bindings): void
	{
		$monitor = Monitor::getInstance();
		foreach ($bindings as $binding)
		{
			if (\CCrmOwnerType::IsDefined($binding['OWNER_TYPE_ID']) && $binding['OWNER_ID'] > 0)
			{
				$monitor->onTimelineEntryRemove(new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']));
			}
		}
	}

	protected function notifyTimelineMonitorAboutMoveFromBin(array $bindings): void
	{
		$monitor = Monitor::getInstance();
		foreach ($bindings as $binding)
		{
			if (\CCrmOwnerType::IsDefined($binding['OWNER_TYPE_ID']) && $binding['OWNER_ID'] > 0)
			{
				$monitor->onTimelineEntryAdd(new ItemIdentifier($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']));
			}
		}
	}
}
