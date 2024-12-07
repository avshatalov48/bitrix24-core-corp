<?php
namespace Bitrix\Crm\Recycling;

use Bitrix\Crm;
use Bitrix\Crm\Badge\Badge;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main;

abstract class BaseController
{
	protected static $lockedItems = array();

	protected static function lockItem($itemID)
	{
		if(isset(self::$lockedItems[$itemID]))
		{
			return false;
		}

		self::$lockedItems[$itemID] = true;
		return true;
	}

	protected static function unlockItem($itemID)
	{
		unset(self::$lockedItems[$itemID]);
	}

	public static function isItemLocked($itemID)
	{
		return isset(self::$lockedItems[$itemID]);
	}

	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		return Main\ModuleManager::isModuleInstalled('recyclebin');
	}

	//region getEntityTypeID, getSuspendedEntityTypeID, getRecyclebinEntityTypeName
	/**
	 * Get Entity Type ID
	 * @return int
	 */
	abstract public function getEntityTypeID();

	/**
	 * Get Entity Type Name
	 * @return string
	 */
	public function getEntityTypeName()
	{
		return \CCrmOwnerType::ResolveName($this->getEntityTypeID());
	}

	/**
	 * Get Suspended Entity Type ID
	 * @return int
	 */
	abstract public function getSuspendedEntityTypeID();

	/**
	 * Get Suspended Entity Type Name
	 * @return string
	 */
	public function getSuspendedEntityTypeName()
	{
		return \CCrmOwnerType::ResolveName($this->getSuspendedEntityTypeID());
	}

	/**
	 * Get recyclebin entity type name.
	 * @throws Main\NotImplementedException
	 * @return string
	 */
	public function getRecyclebinEntityTypeName()
	{
		throw new Main\NotImplementedException('Method '.__METHOD__.' must be implemented by successor');
	}
	//endregion

	public function getActivityOwnerNotFoundMessage($entityTypeID, $entityID, array $params)
	{
		return '';
	}

	//region MoveToBin, Recover and Erase
	/**
	 * Move entity to Recycle Bin.
	 * @param int $entityID Entity ID.
	 * @param array $params Additional operation parameters.
	 * @return void
	 */
	abstract public function moveToBin($entityID, array $params = array());

	/**
	 * Recover entity from Recycle Bin.
	 */
	abstract public function recover(int $entityID, array $params = []): ?int;

	/**
	 * Erase entity from Recycle Bin.
	 * @param int $entityID Entity ID.
	 * @param array $params Additional operation parameters.
	 * @return void
	 */
	abstract public function erase($entityID, array $params = array());
	//endregion

	protected function eraseFiles(array $files)
	{
		foreach($files as $file)
		{
			Crm\Integration\StorageManager::deleteFile(
				(int)$file['FILE_ID'],
				Crm\Integration\StorageType::resolveID(
					isset($file['STORAGE_TYPE']) ? $file['STORAGE_TYPE'] : ''
				)
			);
		}
	}

	//region User Fields
	/**
	 * Convert General Entity User Fields to Suspended Entity User Fields.
	 * @param int $entityID Entity ID.
	 * @return array
	 * @throws Crm\Synchronization\UserFieldSynchronizationException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	protected function prepareSuspendedUserFields($entityID)
	{
		//Enable preliminary field sets synchronization.
		//Original fields are not existed in suspended type will be created
		//Suspended fields are not existed in original type will be deleted
		$fields = array();
		$this->transformUserFields(
			$this->getEntityTypeID(),
			$entityID,
			$this->getSuspendedEntityTypeID(),
			$fields,
			array('ENABLE_SYNCHRONIZATION' => true, 'SYNCHRONIZATION_OPTIONS' => array('ENABLE_TRIM' => true))
		);
		return $fields;
	}

	/**
	 * Convert Suspended Entity User Fields to General Entity User Fields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @return array
	 * @throws Crm\Synchronization\UserFieldSynchronizationException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	protected function prepareRestoredUserFields($recyclingEntityID)
	{
		//Disable preliminary field sets synchronization.
		//Suspended fields are not existed in original type will be ignored
		$fields = array();
		$this->transformUserFields(
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			$this->getEntityTypeID(),
			$fields,
			array('ENABLE_SYNCHRONIZATION' => false)
		);
		return $fields;
	}

	/**
	 * Save Suspended Entity User Fields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param array $fields User Fields.
	 */
	protected function saveSuspendedUserFields($recyclingEntityID, array $fields)
	{
		$GLOBALS['USER_FIELD_MANAGER']->Update(
			\CCrmOwnerType::ResolveUserFieldEntityID($this->getSuspendedEntityTypeID()),
			$recyclingEntityID,
			$fields
		);
	}

	/**
	 * Erase Suspended Entity User Fields.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedUserFields($recyclingEntityID)
	{
		$GLOBALS['USER_FIELD_MANAGER']->Delete(
			\CCrmOwnerType::ResolveUserFieldEntityID($this->getSuspendedEntityTypeID()),
			$recyclingEntityID
		);
	}

	/**
	 * Transform user fields from Source Entity Type ID to Destination Entity Type ID.
	 * User filed synchronization will be done if required.
	 * @param int $srcEntityTypeID Source Entity Type ID.
	 * @param int $srcEntityID Source Entity ID.
	 * @param int $dstEntityTypeID Destination Entity Type ID.
	 * @param array $dstFields Destination Fields.
	 * @param array $options Operation options
	 * @return void
	 * @throws Crm\Synchronization\UserFieldSynchronizationException
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	protected function transformUserFields($srcEntityTypeID, $srcEntityID, $dstEntityTypeID, array &$dstFields, array $options = array())
	{
		if(!\CCrmOwnerType::IsDefined($srcEntityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('srcEntityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		$srcUserFieldEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($srcEntityTypeID);
		if($srcUserFieldEntityID == '')
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($srcUserFieldEntityID);
			throw new Main\ObjectNotFoundException("Could not resolve user field entity ID: {$entityTypeName}.");
		}

		if(!\CCrmOwnerType::IsDefined($dstEntityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('dstEntityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		$dstUserFieldEntityTypeID = \CCrmOwnerType::ResolveUserFieldEntityID($dstEntityTypeID);
		if($dstUserFieldEntityTypeID == '')
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($dstUserFieldEntityTypeID);
			throw new Main\ObjectNotFoundException("Could not resolve user field entity ID: {$entityTypeName}.");
		}

		if($srcEntityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero.', 'srcEntityID');
		}

		$enableSynchronization = !isset($options['ENABLE_SYNCHRONIZATION']) || $options['ENABLE_SYNCHRONIZATION'];
		$synchronizationOptions = isset($options['SYNCHRONIZATION_OPTIONS']) && is_array($options['SYNCHRONIZATION_OPTIONS'])
			? $options['SYNCHRONIZATION_OPTIONS'] : array();
		$synchronizationOptions['IS_RECYCLING'] = true;
		if (
			$enableSynchronization
			&& Crm\Synchronization\UserFieldSynchronizer::needForSynchronization(
				$srcEntityTypeID,
				$dstEntityTypeID,
				'',
				$synchronizationOptions
			)
		)
		{
			Crm\Synchronization\UserFieldSynchronizer::synchronize(
				$srcEntityTypeID,
				$dstEntityTypeID,
				'',
				array(),
				$synchronizationOptions
			);
		}

		$intersections = Crm\Synchronization\UserFieldSynchronizer::getIntersection($srcEntityTypeID, $dstEntityTypeID);
		if(empty($intersections))
		{
			return;
		}

		$srcFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($srcUserFieldEntityID, $srcEntityID);
		foreach($intersections as $intersection)
		{
			$srcFieldName = $intersection['SRC_FIELD_NAME'];
			$dstFieldName = $intersection['DST_FIELD_NAME'];

			$srcFieldData = isset($srcFields[$srcFieldName]) ? $srcFields[$srcFieldName] : null;
			if(!is_array($srcFieldData))
			{
				continue;
			}

			$isMultiple = $srcFieldData['MULTIPLE'] === 'Y';
			$typeID = $srcFieldData['USER_TYPE_ID'];

			if($typeID === 'file')
			{
				if(!$isMultiple)
				{
					$file = null;
					if(\CCrmFileProxy::TryResolveFile($srcFieldData['VALUE'], $file, array('ENABLE_ID' => true)))
					{
						$dstFields[$dstFieldName] = $file;
					}
				}
				elseif(is_array($srcFieldData['VALUE']))
				{
					$files = array();
					foreach($srcFieldData['VALUE'] as $fileID)
					{
						if(\CCrmFileProxy::TryResolveFile($fileID, $file, array('ENABLE_ID' => true)))
						{
							$files[] = $file;
						}
					}

					if(!empty($files))
					{
						$dstFields[$dstFieldName] = $files;
					}
				}
			}
			else
			{
				$dstFields[$dstFieldName] = $srcFields[$srcFieldName]['VALUE'];
			}
		}
	}
	//endregion

	//region Timeline
	/**
	 * Suspend entity timeline.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\Db\SqlQueryException
	 */
	protected function suspendTimeline($entityID, $recyclingEntityID)
	{
		Crm\Timeline\TimelineManager::transferOwnership(
			$this->getEntityTypeID(),
			$entityID,
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID
		);
	}

	/**
	 * Recover entity timeline.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @param array $params Params are required for timeline synchronization (OLD_ENTITY_ID, RELATIONS and etc.).
	 * @throws Main\Db\SqlQueryException
	 */
	protected function recoverTimeline($recyclingEntityID, $newEntityID, array $params = array())
	{
		Crm\Timeline\TimelineManager::transferOwnership(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);

		$oldEntityID = isset($params['OLD_ENTITY_ID']) ? (int)$params['OLD_ENTITY_ID'] : 0;
		$relationMap = isset($params['RELATIONS']) &&  $params['RELATIONS'] instanceOf Crm\Recycling\RelationMap
			? $params['RELATIONS'] : null;
		if($oldEntityID > 0 && $relationMap !== null)
		{
			self::synchronizeTimeline($oldEntityID, $newEntityID, $relationMap);
		}
	}

	/**
	 * Erase Suspended Entity Timeline.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function eraseSuspendedTimeline($recyclingEntityID)
	{
		Crm\Timeline\TimelineEntry::deleteByOwner($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}

	protected function synchronizeTimeline($oldEntityID, $newEntityID, RelationMap $relationMap)
	{
		$entityTypeID = $this->getEntityTypeID();
		if($entityTypeID === \CCrmOwnerType::Lead)
		{
			//Refreshing Creation on Base entities settings
			$childEntities = [];

			$childEntities[\CCrmOwnerType::Contact] = $relationMap->getDestinationEntityIDs(\CCrmOwnerType::Contact);
			$childEntities[\CCrmOwnerType::Company] = $relationMap->getDestinationEntityIDs(\CCrmOwnerType::Company);
			$childEntities[\CCrmOwnerType::Deal] = $relationMap->getDestinationEntityIDs(\CCrmOwnerType::Deal);

			foreach($childEntities as $childEntityTypeID => $childEntityIDs)
			{
				foreach($childEntityIDs as $childEntityID)
				{
					$childRecycleBinEntityID = $relationMap->resolveRecycleBinEntityID($childEntityTypeID, $childEntityID);

					$associatedEntityTypeID = $childRecycleBinEntityID > 0
						? \CCrmOwnerType::ResolveSuspended($childEntityTypeID) : $childEntityTypeID;
					$associatedEntityID = $childRecycleBinEntityID > 0 ? $childRecycleBinEntityID : $childEntityID;

					$fields = Crm\Timeline\Entity\TimelineTable::getRow(
						array(
							'filter' => array(
								'=ASSOCIATED_ENTITY_TYPE_ID' => $associatedEntityTypeID,
								'=ASSOCIATED_ENTITY_ID' => $associatedEntityID,
								'=TYPE_ID' => Crm\Timeline\TimelineType::CREATION
							)
						)
					);

					if(!is_array($fields))
					{
						continue;
					}

					$settings = isset($fields['SETTINGS']) && is_array($fields['SETTINGS']) ? $fields['SETTINGS'] : array();
					$base = isset($settings['BASE']) && is_array($settings['BASE'])
						? $settings['BASE'] : array();

					if(empty($base))
					{
						continue;
					}

					$baseEntityTypeID = isset($base['ENTITY_TYPE_ID']) ? (int)$base['ENTITY_TYPE_ID'] : 0;
					$baseEntityID = isset($base['ENTITY_ID']) ? (int)$base['ENTITY_ID'] : 0;

					if($entityTypeID === $baseEntityTypeID && $baseEntityID === $oldEntityID)
					{
						$base['ENTITY_ID'] = $newEntityID;
						$settings['BASE'] = $base;
						Crm\Timeline\ConversionEntry::update($fields['ID'], array('SETTINGS' => $settings));
					}
				}
			}
		}
		else
		{
			//Refreshing Lead Conversion entities settings
			$parentLeadIDs = $relationMap->getSourceEntityIDs(\CCrmOwnerType::Lead);
			if(empty($parentLeadIDs))
			{
				return;
			}

			$leadID = $parentLeadIDs[0];
			$leadRecycleBinEntityID = $relationMap->resolveRecycleBinEntityID(\CCrmOwnerType::Lead, $leadID);

			$associatedEntityTypeID = $leadRecycleBinEntityID > 0 ? \CCrmOwnerType::SuspendedLead : \CCrmOwnerType::Lead;
			$associatedEntityID = $leadRecycleBinEntityID > 0 ? $leadRecycleBinEntityID : $leadID;

			$fields = Crm\Timeline\Entity\TimelineTable::getRow(
				array(
					'filter' => array(
						'=ASSOCIATED_ENTITY_TYPE_ID' => $associatedEntityTypeID,
						'=ASSOCIATED_ENTITY_ID' => $associatedEntityID,
						'=TYPE_ID' => Crm\Timeline\TimelineType::CONVERSION
					)
				)
			);

			if(!is_array($fields))
			{
				return;
			}

			$settings = isset($fields['SETTINGS']) && is_array($fields['SETTINGS']) ? $fields['SETTINGS'] : array();
			$entities = isset($settings['ENTITIES']) && is_array($settings['ENTITIES'])
				? $settings['ENTITIES'] : array();

			$isChanged = false;
			for($i = 0, $length = count($entities); $i < $length; $i++)
			{
				$currentEntityData = $entities[$i];
				$currentEntityTypeID = isset($currentEntityData['ENTITY_TYPE_ID']) ? (int)$currentEntityData['ENTITY_TYPE_ID'] : 0;
				$currentEntityID = isset($currentEntityData['ENTITY_ID']) ? (int)$currentEntityData['ENTITY_ID'] : 0;

				if($entityTypeID === $currentEntityTypeID && $currentEntityID === $oldEntityID)
				{
					$entities[$i]['ENTITY_ID'] = $newEntityID;
					$isChanged = true;
					break;
				}
			}

			if($isChanged)
			{
				$settings['ENTITIES'] = $entities;
				Crm\Timeline\ConversionEntry::update($fields['ID'], array('SETTINGS' => $settings));
			}
		}
	}
	//endregion

	//region Live Feed
	/**
	 * Suspend entity Live Feed.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function suspendLiveFeed($entityID, $recyclingEntityID)
	{
		\CCrmLiveFeed::RebindAndActivate(
			$this->getEntityTypeID(),
			$entityID,
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			false
		);
	}

	/**
	 * Recover entity Live Feed.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 */
	protected function recoverLiveFeed($recyclingEntityID, $newEntityID)
	{
		\CCrmLiveFeed::RebindAndActivate(
			$this->getSuspendedEntityTypeID(),
			$recyclingEntityID,
			$this->getEntityTypeID(),
			$newEntityID,
			true
		);
	}

	/**
	 * Erase Suspended Entity Timeline.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedLiveFeed($recyclingEntityID)
	{
		\CCrmLiveFeed::DeleteLogEvents(
			array(
				'ENTITY_TYPE_ID' => $this->getSuspendedEntityTypeID(),
				'ENTITY_ID' => $recyclingEntityID,
				'INACTIVE' => true
			),
			array(
				'UNREGISTER_RELATION' => true,
				'UNREGISTER_SUBSCRIPTION' => true
			)
		);
	}
	//endregion

	//region UTM
	/**
	 * Suspend entity UTM.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function suspendUtm($entityID, $recyclingEntityID)
	{
		Crm\UtmTable::rebind(
			$this->getEntityTypeID(), $entityID,
			$this->getSuspendedEntityTypeID(), $recyclingEntityID
		);
	}

	/**
	 * Recover entity UTM.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 */
	protected function recoverUtm($recyclingEntityID, $newEntityID)
	{
		Crm\UtmTable::rebind(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);
	}

	/**
	 * Erase Suspended Entity UTM.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedUtm($recyclingEntityID)
	{
		Crm\UtmTable::deleteEntityUtm($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}
	//endregion

	//region Trace
	/**
	 * Suspend entity tracing data.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	protected function suspendTracing($entityID, $recyclingEntityID)
	{
		Crm\Tracking\Entity::rebindTrace(
			$this->getEntityTypeID(), $entityID,
			$this->getSuspendedEntityTypeID(), $recyclingEntityID
		);
	}

	/**
	 * Recover entity UTM.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	protected function recoverTracing($recyclingEntityID, $newEntityID)
	{
		Crm\Tracking\Entity::rebindTrace(
			$this->getSuspendedEntityTypeID(), $recyclingEntityID,
			$this->getEntityTypeID(), $newEntityID
		);
	}

	/**
	 * Erase Suspended entity tracing data.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedTracing($recyclingEntityID)
	{
		Crm\Tracking\Entity::deleteTrace($this->getSuspendedEntityTypeID(), $recyclingEntityID);
	}
	//endregion

	//region Document Generator
	/**
	 * Suspend entity Documents.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function suspendDocuments($entityID, $recyclingEntityID)
	{
		$manager = Crm\Integration\DocumentGeneratorManager::getInstance();
		if($manager->isEnabled())
		{
			$manager->transferDocumentsOwnership(
				$this->getEntityTypeID(),
				$entityID,
				$this->getSuspendedEntityTypeID(),
				$recyclingEntityID
			);
		}
	}

	/**
	 * Recover entity Documents.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 */
	protected function recoverDocuments($recyclingEntityID, $newEntityID)
	{
		$manager = Crm\Integration\DocumentGeneratorManager::getInstance();
		if($manager->isEnabled())
		{
			$manager->transferDocumentsOwnership(
				$this->getSuspendedEntityTypeID(),
				$recyclingEntityID,
				$this->getEntityTypeID(),
				$newEntityID
			);
		}
	}

	/**
	 * Erase Suspended Entity UTM.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedDocuments($recyclingEntityID)
	{
		//Documents will be deleted in Crm\Timeline\TimelineEntry::deleteByOwner
	}
	//endregion

	//region Scoring
	/**
	 * Suspend scoring history records.
	 * @param int $entityID Entity ID.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function suspendScoringHistory($entityID, $recyclingEntityID)
	{
		if(Crm\Ml\Scoring::isMlAvailable())
		{
			Crm\Ml\Scoring::replaceAssociatedEntity(
				$this->getEntityTypeID(),
				$entityID,
				$this->getSuspendedEntityTypeID(),
				$recyclingEntityID
			);
		}
	}

	/**
	 * Recover entity Documents.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 * @param int $newEntityID New Entity ID.
	 */
	protected function recoverScoringHistory($recyclingEntityID, $newEntityID)
	{
		if(Crm\Ml\Scoring::isMlAvailable())
		{
			Crm\Ml\Scoring::replaceAssociatedEntity(
				$this->getSuspendedEntityTypeID(),
				$recyclingEntityID,
				$this->getEntityTypeID(),
				$newEntityID
			);
		}
	}

	/**
	 * Erase Suspended Entity UTM.
	 * @param int $recyclingEntityID Recycle Bin Entity ID.
	 */
	protected function eraseSuspendedScoringHistory($recyclingEntityID)
	{
		if(Crm\Ml\Scoring::isMlAvailable())
		{
			Crm\Ml\Scoring::onEntityDelete($this->getSuspendedEntityTypeID(), $recyclingEntityID);
		}
	}
	//endregion

	//region Business Process
	protected function startRecoveryWorkflows($entityID)
	{
		\CCrmBizProcHelper::AutoStartWorkflows(
			$this->getEntityTypeID(),
			$entityID,
			\CCrmBizProcEventType::Create,
			$errors
		);
	}
	//endregion

	//region Events
	protected function fireAfterMoveToBinEvent($entityID, $recyclingEntityID)
	{
		$entityTypeID = $this->getEntityTypeID();
		$suspendedEntityTypeID = $this->getSuspendedEntityTypeID();

		$events = \GetModuleEvents('crm', 'OnAfterMoveToRecycleBin');
		while($event = $events->Fetch())
		{
			\ExecuteModuleEventEx(
				$event,
				[
					[
						'ENTITY_ID' => $entityID,
						'ENTITY_TYPE_ID' => $entityTypeID,
						'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($entityTypeID),
						'RECYCLEBIN_ENTITY_ID' => $recyclingEntityID,
						'RECYCLEBIN_ENTITY_TYPE_ID' => $suspendedEntityTypeID,
						'RECYCLEBIN_ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($suspendedEntityTypeID)
					]
				]
			);
		}
	}

	protected function fireAfterRecoverEvent($recyclingEntityID, $newEntityID)
	{
		$entityTypeID = $this->getEntityTypeID();
		$suspendedEntityTypeID = $this->getSuspendedEntityTypeID();

		$events = \GetModuleEvents('crm', 'OnAfterRecoverFromRecycleBin');
		while($event = $events->Fetch())
		{
			\ExecuteModuleEventEx(
				$event,
				[
					[
						'RECYCLEBIN_ENTITY_ID' => $recyclingEntityID,
						'RECYCLEBIN_ENTITY_TYPE_ID' => $suspendedEntityTypeID,
						'RECYCLEBIN_ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($suspendedEntityTypeID),
						'ENTITY_ID' => $newEntityID,
						'ENTITY_TYPE_ID' => $entityTypeID,
						'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($entityTypeID)
					]
				]
			);
		}
	}

	protected function fireAfterEraseEvent($recyclingEntityID)
	{
		$suspendedEntityTypeID = $this->getSuspendedEntityTypeID();

		$events = \GetModuleEvents('crm', 'OnAfterEraseFromRecycleBin');
		while($event = $events->Fetch())
		{
			\ExecuteModuleEventEx(
				$event,
				[
					[
						'RECYCLEBIN_ENTITY_ID' => $recyclingEntityID,
						'RECYCLEBIN_ENTITY_TYPE_ID' => $suspendedEntityTypeID,
						'RECYCLEBIN_ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($suspendedEntityTypeID)
					]
				]
			);
		}
	}
	//endregion

	protected function rebuildSearchIndex($entityID)
	{
		try
		{
			Crm\Search\SearchContentBuilderFactory::create($this->getEntityTypeID())->build($entityID);
		}
		catch(Main\NotSupportedException $ex)
		{
		}
	}

	public function getEntityInfos($entityIDs)
	{
		if(!Main\Loader::includeModule('recyclebin'))
		{
			return array();
		}

		$dbResult = \Bitrix\Recyclebin\Internals\Models\RecyclebinTable::getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE' => $this->getRecyclebinEntityTypeName(),
					'@ENTITY_ID' => $entityIDs
				),
				'select' => array('ENTITY_ID', 'NAME', 'USER_ID')
			)
		);

		$data = array();
		while($fields = $dbResult->fetch())
		{
			$data[] = array(
				'ID' => (int)$fields['ENTITY_ID'],
				'TITLE' => $fields['NAME'],
				'ASSIGNED_BY_ID' => (int)$fields['USER_ID']
			);
		}
		return $data;
	}

	//region Custom relations
	protected function suspendCustomRelations(int $entityId, int $recyclingEntityId): void
	{
		Crm\Relation\EntityRelationTable::rebind(
			new Crm\ItemIdentifier($this->getEntityTypeID(), $entityId),
			new Crm\ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityId)
		);
	}

	protected function recoverCustomRelations(int $recyclingEntityId, int $newEntityId): void
	{
		$result = Crm\Relation\EntityRelationTable::rebind(
			new Crm\ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityId),
			new Crm\ItemIdentifier($this->getEntityTypeID(), $newEntityId)
		);

		if (!$result->isSuccess())
		{
			return;
		}

		$parents = $result->getData()['affectedItems']['parents'];
		$children = $result->getData()['affectedItems']['children'];

		$registrar = Crm\Service\Container::getInstance()->getRelationRegistrar();
		$newEntity = new ItemIdentifier($this->getEntityTypeID(), $newEntityId);

		foreach ($parents as $item)
		{
			$registrar->registerBind($item, $newEntity);
		}
		foreach ($children as $item)
		{
			$registrar->registerBind($newEntity, $item);
		}
	}

	protected function eraseSuspendedCustomRelations(int $recyclingEntityId): void
	{
		Crm\Relation\EntityRelationTable::deleteByItem($this->getSuspendedEntityTypeID(), $recyclingEntityId);
	}
	//endregion

	//region Badges
	protected function suspendBadges(int $entityId, int $recyclingEntityId): void
	{
		Badge::rebindEntity(
			new ItemIdentifier($this->getEntityTypeID(), $entityId),
			new ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityId),
		);
	}

	protected function recoverBadges(int $recyclingEntityId, int $newEntityId): void
	{
		Badge::rebindEntity(
			new ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityId),
			new ItemIdentifier($this->getEntityTypeID(), $newEntityId),
		);
	}

	protected function eraseSuspendedBadges(int $recyclingEntityId): void
	{
		Badge::deleteByEntity(
			new ItemIdentifier($this->getSuspendedEntityTypeID(), $recyclingEntityId),
		);
	}
	//endregion

	//region Content Types
	//endregion
}
