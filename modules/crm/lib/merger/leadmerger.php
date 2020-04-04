<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\Conversion;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Timeline;

class LeadMerger extends EntityMerger
{
	private static $langIncluded = false;
	private $entity = null;

	public function __construct($userID, $enablePermissionCheck = false)
	{
		parent::__construct(\CCrmOwnerType::Lead, $userID, $enablePermissionCheck);
	}
	protected function getEntity()
	{
		if($this->entity === null)
		{
			$this->entity = new \CCrmLead(false);
		}
		return $this->entity;
	}
	protected function getEntityFieldsInfo()
	{
		return \CCrmLead::GetFieldsInfo();
	}
	protected function getEntityUserFieldsInfo()
	{
		return \CCrmLead::GetUserFields();
	}
	protected function getEntityResponsibleID($entityID, $roleID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Lead, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}
	protected function getEntityFields($entityID, $roleID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Lead, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return $fields;
	}
	protected function checkEntityReadPermission($entityID, $userPermissions)
	{
		return \CCrmLead::CheckReadPermission($entityID, $userPermissions);
	}
	protected function checkEntityUpdatePermission($entityID, $userPermissions)
	{
		return \CCrmLead::CheckUpdatePermission($entityID, $userPermissions);
	}
	protected function checkEntityDeletePermission($entityID, $userPermissions)
	{
		return \CCrmLead::CheckDeletePermission($entityID, $userPermissions);
	}
	protected function setupRecoveryData(Recovery\EntityRecoveryData $recoveryData, array &$fields)
	{
		if(isset($fields['TITLE']))
		{
			$recoveryData->setTitle($fields['TITLE']);
		}
		if(isset($fields['ASSIGNED_BY_ID']))
		{
			$recoveryData->setResponsibleID((int)$fields['ASSIGNED_BY_ID']);
		}
	}

	protected static function resolveEntityFieldConflict(array &$seed, array &$targ, $fieldID)
	{
		$seedID = isset($seed['ID']) ? (int)$seed['ID'] : 0;
		$targID = isset($targ['ID']) ? (int)$targ['ID'] : 0;

		//Field Title is ignored
		if($fieldID === 'TITLE')
		{
			return true;
		}

		if($fieldID === 'CONTACT_ID')
		{
			//Crutch for ContactID Field. It is obsolete and can be ignored. See LeadMerger::innerMergeBoundEntities.
			return true;
		}

		//Crutch for Opportunity Field. It can be ignored if ProductRows are not empty. We will recalculate Opportunity after merging of ProductRows. See LeadMerger::innerMergeBoundEntities.
		if($fieldID === 'OPPORTUNITY')
		{
			$seedProductRows = isset($seed['PRODUCT_ROWS']) && is_array($seed['PRODUCT_ROWS'])
				? $seed['PRODUCT_ROWS'] : \CCrmLead::LoadProductRows($seedID);

			if(!empty($seedProductRows))
			{
				$seed['PRODUCT_ROWS'] = $seedProductRows;
			}

			$targProductRows = isset($targ['PRODUCT_ROWS']) && is_array($targ['PRODUCT_ROWS'])
				? $targ['PRODUCT_ROWS'] : \CCrmLead::LoadProductRows($targID);

			if(!empty($targProductRows))
			{
				$targ['PRODUCT_ROWS'] = $targProductRows;
			}

			if(!empty($seedProductRows) || !empty($targProductRows))
			{
				//Opportunity is depends on Product Rows. Product Rows will be merged in innerMergeBoundEntities
				return true;
			}
		}

		//Crutch for TaxValue Field. It can be ignored. We will recalculate TaxValue after merging of ProductRows. See DealMerger::innerMergeBoundEntities.
		if($fieldID === 'TAX_VALUE')
		{
			return true;
		}

		return parent::resolveEntityFieldConflict($seed,$targ, $fieldID);
	}

	protected static function canMergeEntityField($fieldID)
	{
		//Field ContactID is obsolete. It is replaced by ContactIDs
		//Field StatusID is progress field
		//Field StatusDescription depend on StatusID
		if($fieldID === 'CONTACT_ID' || $fieldID === 'STATUS_ID' || $fieldID === 'STATUS_DESCRIPTION')
		{
			return false;
		}
		return parent::canMergeEntityField($fieldID);
	}

	protected function mergeBoundEntitiesBatch(array &$seeds, array &$targ, $skipEmpty = false, array $options = array())
	{
		$contactMerger = new LeadContactBindingMerger();
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
				$seedObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Lead, $seedID);
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
				$seedProductRows = \CCrmLead::LoadProductRows($seedID);
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
				$targObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Lead, $targID);
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
			$targProductRows = \CCrmLead::LoadProductRows($targID);
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

		parent::mergeBoundEntitiesBatch($seeds, $targ, $skipEmpty, $options);
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
					$seedContactBindings = Binding\LeadContactTable::getLeadBindings($seedID);
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
				$targContactBindings = Binding\LeadContactTable::getLeadBindings($targID);
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
	 * @throws EntityMergerException
	 */
	protected function updateEntity($entityID, array &$fields, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		//Required for set current user as last modification author
		unset($fields['CREATED_BY_ID'], $fields['DATE_CREATE'], $fields['MODIFY_BY_ID'], $fields['DATE_MODIFY']);
		if(!$entity->Update($entityID, $fields, true, true, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Lead,
				$entityID,
				$roleID,
				EntityMergerException::UPDATE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}

		if(isset($fields['PRODUCT_ROWS'])
			&& is_array($fields['PRODUCT_ROWS'])
			&& !empty($fields['PRODUCT_ROWS']))
		{
			\CCrmLead::SaveProductRows($entityID, $fields['PRODUCT_ROWS'], false, true, true);
		}
	}
	protected function deleteEntity($entityID, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		if(!$entity->Delete($entityID, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Lead,
				$entityID,
				$roleID,
				EntityMergerException::DELETE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}
	protected function rebind($seedID, $targID)
	{
		\CCrmQuote::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmActivity::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmLiveFeed::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmSonetRelation::RebindRelations(\CCrmOwnerType::Lead, $seedID, $targID);
		\CCrmEvent::Rebind(\CCrmOwnerType::Lead, $seedID, $targID);

		Timeline\ActivityEntry::rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		Timeline\CreationEntry::rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		Timeline\MarkEntry::rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		Timeline\CommentEntry::rebind(\CCrmOwnerType::Lead, $seedID, $targID);
	}
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
		$dbResult = \CCrmLead::GetListEx(array(), array('=ID' => $seedID), false, false, array('ORIGINATOR_ID', 'ORIGIN_ID'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return;
		}

		$originatorID = isset($fields['ORIGINATOR_ID']) ? $fields['ORIGINATOR_ID'] : '';
		$originID = isset($fields['ORIGIN_ID']) ? $fields['ORIGIN_ID'] : '';
		if($originatorID !== '' || $originID !== '')
		{
			$results[EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP] = new EntityMergeCollision(\CCrmOwnerType::Lead, $seedID, $targID, EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP);
		}
	}
	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ)
	{
		self::includeLangFile();

		$replacements = array(
			'#USER_NAME#' => $this->getUserName(),
			'#SEED_TITLE#' => isset($seed['TITLE']) ? $seed['TITLE'] : '',
			'#SEED_ID#' => isset($seed['ID']) ? $seed['ID'] : '',
			'#TARG_TITLE#' => isset($targ['TITLE']) ? $targ['TITLE'] : '',
			'#TARG_ID#' => isset($targ['ID']) ? $targ['ID'] : '',
		);

		$messages = array();
		if(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK])
			&& isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_LEAD_MERGER_COLLISION_READ_UPDATE_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_LEAD_MERGER_COLLISION_READ_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_LEAD_MERGER_COLLISION_UPDATE_PERMISSION', $replacements);
		}

		if(empty($messages))
		{
			return null;
		}

		$html = implode('<br/>', $messages);
		return array(
			'TO_USER_ID' => isset($seed['ASSIGNED_BY_ID']) ? (int)$seed['ASSIGNED_BY_ID'] : 0,
			'NOTIFY_MESSAGE' => $html,
			'NOTIFY_MESSAGE_OUT' => $html
		);
	}
	/**
	 * Map entity to custom type.
	 * @param int $sourceEntityID Source Entity ID.
	 * @param int $destinationEntityTypeID Destination Entity ID.
	 * @return array|null
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	protected function mapEntity($sourceEntityID, $destinationEntityTypeID)
	{
		$mapper = new Conversion\LeadConversionMapper($sourceEntityID);
		$map = Conversion\EntityConversionMap::load(\CCrmOwnerType::Lead, $destinationEntityTypeID);
		if($map === null)
		{
			$map = Conversion\LeadConversionMapper::createMap($destinationEntityTypeID);
		}
		return $mapper->map($map, array('DISABLE_USER_FIELD_INIT' => true));
	}
	private static function includeLangFile()
	{
		if(!self::$langIncluded)
		{
			self::$langIncluded = IncludeModuleLangFile(__FILE__);
		}
	}
}