<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Crm;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Timeline;
use Bitrix\Main;
use CCrmOwnerType;

class DealMerger extends EntityMerger
{
	private $entity = null;

	/**
	 * @param int $userID User ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 * @throws Main\ArgumentException
	 */
	public function __construct($userID, $enablePermissionCheck = false)
	{
		parent::__construct(\CCrmOwnerType::Deal, $userID, $enablePermissionCheck);
	}
	protected function getEntity()
	{
		if($this->entity === null)
		{
			$this->entity = new \CCrmDeal(false);
		}
		return $this->entity;
	}
	/**
	 * Get Entity field infos
	 * @return array
	 */
	protected function getEntityFieldsInfo()
	{
		return \CCrmDeal::GetFieldsInfo();
	}
	/**
	 * Get entity user field infos
	 * @return array
	 */
	protected function getEntityUserFieldsInfo()
	{
		return \CCrmDeal::GetUserFields();
	}
	/**
	 * Get field caption
	 * @param string $fieldId
	 * @return string
	 */
	protected function getFieldCaption(string $fieldId):string
	{
		return \CCrmDeal::GetFieldCaption($fieldId);
	}
	/**
	 * Get entity responsible ID
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return int
	 * @throws EntityMergerException
	 * @throws Main\NotImplementedException
	 */
	protected function getEntityResponsibleID($entityID, $roleID)
	{
		$dbResult = \CCrmDeal::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Deal, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}
	/**
	 * Get entity fields
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	protected function getEntityFields($entityID, $roleID)
	{
		$dbResult = \CCrmDeal::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Deal, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return $fields;
	}
	/**
	 * Check entity read permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityReadPermission($entityID, $userPermissions)
	{
		return \CCrmDeal::CheckReadPermission($entityID, $userPermissions);
	}
	/**
	 * Check entity update permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityUpdatePermission($entityID, $userPermissions)
	{
		return \CCrmDeal::CheckUpdatePermission($entityID, $userPermissions);
	}
	/**
	 * Check entity delete permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityDeletePermission($entityID, $userPermissions)
	{
		return \CCrmDeal::CheckDeletePermission($entityID, $userPermissions);
	}

	protected function getFieldConflictResolver(string $fieldId, string $type): ConflictResolver\Base
	{
		$userDefinedResolver = static::getUserDefinedConflictResolver(
			\CCrmOwnerType::Deal,
			$fieldId,
			$type
		);
		if ($userDefinedResolver)
		{
			return $userDefinedResolver;
		}

		switch($fieldId)
		{
			case 'TITLE':
				//Field Title is ignored
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);

			case 'CONTACT_ID':
				//Crutch for ContactID Field. It is obsolete and can be ignored. See DealMerger::innerMergeBoundEntities.
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);

			case 'OPPORTUNITY':
				//Crutch for Opportunity Field. It can be ignored if ProductRows are not empty. We will recalculate Opportunity after merging of ProductRows. See DealMerger::innerMergeBoundEntities.
				return new Crm\Merger\ConflictResolver\OpportunityField(
					$fieldId,
					CCrmOwnerType::Deal,
				);

			case 'TAX_VALUE':
				//Crutch for TaxValue Field. It can be ignored. We will recalculate TaxValue after merging of ProductRows. See DealMerger::innerMergeBoundEntities.
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);

			case 'COMMENTS':
				return new Crm\Merger\ConflictResolver\HtmlField($fieldId);

			case 'SOURCE_ID':
				return new Crm\Merger\ConflictResolver\SourceField($fieldId);

			case 'SOURCE_DESCRIPTION':
				return new Crm\Merger\ConflictResolver\TextField($fieldId);

			case 'OPENED':
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);
		}

		return parent::getFieldConflictResolver($fieldId, $type);
	}

	/** Check if source and target entities can be merged
	 * @param array $seed Source entity fields
	 * @param array $targ Target entity fields
	 * @return void
	 * @throws EntityMergerException
	 */
	protected static function checkEntityMergePreconditions(array $seed, array $targ)
	{
		if(isset($seed['CATEGORY_ID']) && isset($targ['CATEGORY_ID']) && $seed['CATEGORY_ID'] != $targ['CATEGORY_ID'])
		{
			throw new DealMergerException(
				\CCrmOwnerType::Deal,
				$seed['ID'],
				self::ROLE_SEED,
				DealMergerException::CONFLICT_OCCURRED_CATEGORY
			);
		}
		if(isset($seed['IS_RECURRING']) && isset($targ['IS_RECURRING']) && $seed['IS_RECURRING'] != $targ['IS_RECURRING'])
		{
			throw new DealMergerException(
				\CCrmOwnerType::Deal,
				$seed['ID'],
				self::ROLE_SEED,
				DealMergerException::CONFLICT_OCCURRED_RECURRENCE
			);
		}
	}

	protected static function canMergeEntityField($fieldID)
	{
		//Field ContactID is obsolete. It is replaced by ContactIDs
		//Field StageID is progress field
		if($fieldID === 'CONTACT_ID' || $fieldID === 'STAGE_ID')
		{
			return false;
		}
		return parent::canMergeEntityField($fieldID);
	}

	protected function mergeBoundEntitiesBatch(array &$seeds, array &$targ, $skipEmpty = false, array $options = array())
	{
		$contactMerger = new DealContactBindingMerger();
		$contactMerger->merge($seeds, $targ, $skipEmpty, $options);

		$resultSeedObserverIDs = array();
		$resultSeedProductRows = array();

		foreach($seeds as $seed)
		{
			$seedID = isset($seed['ID']) ? (int)$seed['ID'] : 0;

			//region Observers
			$seedObserverIDs = null;
			if(isset($seed['OBSERVER_IDS']) && is_array($seed['OBSERVER_IDS']))
			{
				$seedObserverIDs = $seed['OBSERVER_IDS'];
			}
			elseif($seedID > 0)
			{
				$seedObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Deal, $seedID);
			}

			if($seedObserverIDs !== null)
			{
				$addedObserverIDs = array_diff($seedObserverIDs, $resultSeedObserverIDs);
				if(!empty($addedObserverIDs))
				{
					$resultSeedObserverIDs = array_merge($resultSeedObserverIDs, $addedObserverIDs);
				}
			}
			//endregion

			//region Product Rows
			$seedProductRows = null;
			if(isset($seed['PRODUCT_ROWS']) && is_array($seed['PRODUCT_ROWS']))
			{
				$seedProductRows = $seed['PRODUCT_ROWS'];
			}
			elseif($seedID > 0)
			{
				$seedProductRows = \CCrmDeal::LoadProductRows($seedID);
			}

			if($seedProductRows !== null)
			{
				\CCrmProductRow::Merge($seedProductRows, $resultSeedProductRows);
			}
			//endregion
		}

		//TODO: Rename SKIP_MULTIPLE_USER_FIELDS -> ENABLE_MULTIPLE_FIELDS_ENRICHMENT
		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];

		$targID = isset($targ['ID']) ? (int)$targ['ID'] : 0;

		//region Merge Observers bindings
		if(!empty($resultSeedObserverIDs))
		{
			$targObserverIDs = null;
			if(isset($targ['OBSERVER_IDS']) && is_array($targ['OBSERVER_IDS']))
			{
				$targObserverIDs = $targ['OBSERVER_IDS'];
			}
			elseif($targID > 0)
			{
				$targObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Deal, $targID);
			}

			if(!$skipMultipleFields)
			{
				if($targObserverIDs === null || count($targObserverIDs) === 0)
				{
					$targObserverIDs = $resultSeedObserverIDs;
				}
				else
				{
					$addedObserverIDs = array_diff($resultSeedObserverIDs, $targObserverIDs);
					if(!empty($addedObserverIDs))
					{
						$targObserverIDs = array_merge($targObserverIDs, $addedObserverIDs);
					}
				}

				$targ['OBSERVER_IDS'] = $targObserverIDs;
			}
			elseif($targObserverIDs === null || (count($targObserverIDs) === 0 && !$skipEmpty))
			{
				$targ['OBSERVER_IDS'] = $resultSeedObserverIDs;
			}
		}
		//endregion

		//region Merge Product Rows
		$targProductRows = null;
		if(isset($targ['PRODUCT_ROWS']) && is_array($targ['PRODUCT_ROWS']))
		{
			$targProductRows = $targ['PRODUCT_ROWS'];
		}
		elseif($targID > 0)
		{
			$targProductRows = \CCrmDeal::LoadProductRows($targID);
		}

		if(!empty($resultSeedProductRows))
		{
			if(!$skipMultipleFields)
			{
				if($targProductRows === null || count($targProductRows) === 0)
				{
					$targ['PRODUCT_ROWS'] = $resultSeedProductRows;
				}
				else
				{
					\CCrmProductRow::Merge($resultSeedProductRows, $targProductRows);
					$targ['PRODUCT_ROWS'] = $targProductRows;
				}
			}
			elseif($targProductRows === null || (count($targProductRows) === 0 && !$skipEmpty))
			{
				$targ['PRODUCT_ROWS'] = $resultSeedProductRows;
			}
		}
		//endregion
	}

	protected function innerPrepareEntityFieldMergeData($fieldID, array $fieldParams,  array $seeds, array $targ, array $options = null)
	{
		if($fieldID === 'CONTACT_IDS')
		{
			$enabledIdsMap = null;
			if(isset($options['enabledIds']) && is_array($options['enabledIds']))
			{
				$enabledIdsMap = array_fill_keys($options['enabledIds'], true);
			}

			$sourceEntityIDs = array();
			$resultContactBindings = array();
			foreach($seeds as $seed)
			{
				$seedID = (int)$seed['ID'];
				if(is_null($enabledIdsMap) || isset($enabledIdsMap[$seedID]))
				{
					$seedContactBindings = Binding\DealContactTable::getDealBindings($seedID);
					if(!empty($seedContactBindings))
					{
						$sourceEntityIDs[] = $seedID;
						self::mergeEntityBindings(
							\CCrmOwnerType::Contact,
							$seedContactBindings,
							$resultContactBindings
						);
					}
				}
			}

			$targID = (int)$targ['ID'];
			if(is_null($enabledIdsMap) || isset($enabledIdsMap[$targID]))
			{
				$targContactBindings = Binding\DealContactTable::getDealBindings($targID);
				if(!empty($targContactBindings))
				{
					$sourceEntityIDs[] = $targID;
					self::mergeEntityBindings(
						\CCrmOwnerType::Contact,
						$targContactBindings,
						$resultContactBindings
					);
				}
			}

			return array(
				'FIELD_ID' => 'CONTACT_IDS',
				'TYPE' => 'crm_contact',
				'IS_MERGED' => true,
				'IS_MULTIPLE' => true,
				'SOURCE_ENTITY_IDS' => array_unique($sourceEntityIDs, SORT_NUMERIC),
				'VALUE' => Binding\EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $resultContactBindings),
			);
		}
		return parent::innerPrepareEntityFieldMergeData($fieldID, $fieldParams, $seeds, $targ, $options);
	}
	/**
	 * Update entity
	 * @param int $entityID Entity ID.
	 * @param array &$fields Entity fields.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 */
	protected function updateEntity($entityID, array &$fields, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		//Required for set current user as last modification author
		unset($fields['CREATED_BY_ID'], $fields['DATE_CREATE'], $fields['MODIFY_BY_ID'], $fields['DATE_MODIFY']);
		if(!$entity->Update($entityID, $fields, true, true, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Deal,
				$entityID,
				$roleID,
				EntityMergerException::UPDATE_FAILED,
				'',
				0,
				new Main\SystemException($entity->getLastError())
			);
		}

		if(isset($fields['PRODUCT_ROWS'])
			&& is_array($fields['PRODUCT_ROWS'])
			&& !empty($fields['PRODUCT_ROWS']))
		{
			\CCrmDeal::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, true, true);
		}
	}
	/**
	 * Delete entity
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 */
	protected function deleteEntity($entityID, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		if(!$entity->Delete($entityID, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Deal,
				$entityID,
				$roleID,
				EntityMergerException::DELETE_FAILED,
				'',
				0,
				new Main\SystemException($entity->getLastError())
			);
		}
	}

	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ): ?array
	{
		return null;
	}

	protected function rebind($seedID, $targID)
	{
		\CCrmQuote::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmActivity::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmLiveFeed::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmSonetRelation::RebindRelations(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmEvent::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);

		Timeline\ActivityEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\CreationEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\MarkEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\CommentEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\LogMessageEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\AI\Call\Entry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);

		Crm\Tracking\Entity::rebindTrace(
			\CCrmOwnerType::Deal, $seedID,
			\CCrmOwnerType::Deal, $targID
		);

		Crm\Relation\EntityRelationTable::rebindWhereItemIsChild(
			new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $seedID),
			new Crm\ItemIdentifier(\CCrmOwnerType::Deal, $targID)
		);
	}
}
