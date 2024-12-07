<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Crm;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Conversion;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\Timeline;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

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
	/**
	 * Get field caption
	 * @param string $fieldId
	 * @return string
	 */
	protected function getFieldCaption(string $fieldId):string
	{
		return \CCrmLead::GetFieldCaption($fieldId);
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

	protected function getFieldConflictResolver(string $fieldId, string $type): ConflictResolver\Base
	{
		$userDefinedResolver = static::getUserDefinedConflictResolver(
			\CCrmOwnerType::Lead,
			$fieldId,
			$type
		);
		if ($userDefinedResolver)
		{
			return $userDefinedResolver;
		}

		switch($fieldId)
		{
			case 'NAME':
				$resolver = new Crm\Merger\ConflictResolver\NameField($fieldId);
				$resolver->setRelatedFieldsCheckRequired(true);
				return $resolver;

			case 'SECOND_NAME':
			case 'LAST_NAME':
				return new Crm\Merger\ConflictResolver\NameField($fieldId);

			case 'TITLE':
				//Field Title is ignored
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);

			case 'ADDRESS_LOC_ADDR_ID':
				//Field Location in address is ignored
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);

			case 'CONTACT_ID':
				//Crutch for ContactID Field. It is obsolete and can be ignored. See DealMerger::innerMergeBoundEntities.
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);

			case 'OPPORTUNITY':
				//Crutch for Opportunity Field. It can be ignored if ProductRows are not empty. We will recalculate Opportunity after merging of ProductRows. See DealMerger::innerMergeBoundEntities.
				return new Crm\Merger\ConflictResolver\OpportunityField(
					$fieldId,
					\CCrmOwnerType::Lead,
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

	protected static function canMergeEntityField($fieldID)
	{
		//Field ContactID is obsolete. It is replaced by ContactIDs
		//Field StatusID is progress field
		//Field StatusDescription depend on StatusID
		//Field ADDRESS_LOC_ADDR_ID is redundant if other address parts are same
		if($fieldID === 'CONTACT_ID' || $fieldID === 'STATUS_ID' || $fieldID === 'STATUS_DESCRIPTION' || $fieldID === 'ADDRESS_LOC_ADDR_ID')
		{
			return false;
		}
		return parent::canMergeEntityField($fieldID);
	}

	protected static function applyMappedValue(string $fieldID, array &$seed, array &$targ)
	{
		if ($fieldID === 'ADDRESS')
		{
			$locationAddressId = (int)$seed['ADDRESS_LOC_ADDR_ID'];
			if ($locationAddressId > 0 && Main\Loader::includeModule('location'))
			{
				unset($targ['ADDRESS_LOC_ADDR_ID']);
				$targ['ADDRESS_LOC_ADDR'] = \Bitrix\Crm\EntityAddress::cloneLocationAddress($locationAddressId);
			}
			else
			{
				foreach (self::getBaseAddressFieldNames() as $fieldName)
				{
					$targ[$fieldName] = $seed[$fieldName];
				}
			}
			return;
		}
		parent::applyMappedValue($fieldID, $seed, $targ);
	}

	protected static function getBaseAddressFieldNames():array
	{
		return [
			'ADDRESS',
			'ADDRESS_2',
			'ADDRESS_CITY',
			'ADDRESS_POSTAL_CODE',
			'ADDRESS_REGION',
			'ADDRESS_PROVINCE',
			'ADDRESS_COUNTRY',
			'ADDRESS_COUNTRY_CODE',
		];
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
		// check all address components
		if($fieldID === 'ADDRESS')
		{
			$addressFields = [];
			$result = parent::innerPrepareEntityFieldMergeData(
				'ADDRESS_LOC_ADDR_ID',
				$fieldParams,
				$seeds,
				$targ,
				$options
			);
			if ($result['VALUE'] > 0)
			{
				$addressFields['LOC_ADDR_ID'] = $result['VALUE'];
			}
			else
			{
				$result = parent::innerPrepareEntityFieldMergeData(
					$fieldID,
					$fieldParams,
					$seeds,
					$targ,
					$options
				);
				foreach (self::getBaseAddressFieldNames() as $addrFieldId)
				{
					if ($addrFieldId === $fieldID)
					{
						continue;
					}
					$extraFieldMergeResult = parent::innerPrepareEntityFieldMergeData(
						$addrFieldId,
						$fieldParams,
						$seeds,
						$targ,
						$options
					);

					if (empty($result['SOURCE_ENTITY_IDS']))
					{
						$result = $extraFieldMergeResult;
					}

					$result['IS_MERGED'] = $result['IS_MERGED'] && $extraFieldMergeResult['IS_MERGED'];
					if (!$result['IS_MERGED'])
					{
						break;
					}
				}

				$addressSourceId = $result['SOURCE_ENTITY_IDS'][0] ?? null;
				if ($addressSourceId)
				{
					$addressSource = null;
					if ($targ['ID'] === $addressSourceId)
					{
						$addressSource = $targ;
					}
					else
					{
						foreach ($seeds as $seed)
						{
							if ($seed['ID'] === $addressSourceId)
							{
								$addressSource = $seed;
								break;
							}
						}
					}
					if ($addressSource)
					{
						foreach (self::getBaseAddressFieldNames() as $addrFieldId)
						{
							$addressValue = (string)$addressSource[$addrFieldId];
							if ($addressValue !== '')
							{
								if ($addrFieldId !== 'ADDRESS' && $addrFieldId !== 'ADDRESS_2')
								{
									$addrFieldId = str_replace('ADDRESS_', '', $addrFieldId);
								}
								if ($addrFieldId === 'ADDRESS')
								{
									$addrFieldId = 'ADDRESS_1';
								}
								$addressFields[$addrFieldId] = $addressValue;
							}
						}
					}
				}
			}

			if (Main\Loader::includeModule('location') && !empty($addressFields))
			{
				$address = \Bitrix\Crm\EntityAddress::makeLocationAddressByFields($addressFields);
				if ($address)
				{
					$result['VALUE'] = $address->toJson();
				}
			}

			return $result;
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
				new Main\SystemException($entity->getLastError())
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
				new Main\SystemException($entity->getLastError())
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
		Timeline\LogMessageEntry::rebind(\CCrmOwnerType::Lead, $seedID, $targID);
		Timeline\AI\Call\Entry::rebind(\CCrmOwnerType::Lead, $seedID, $targID);

		Crm\Tracking\Entity::rebindTrace(
			\CCrmOwnerType::Lead, $seedID,
			\CCrmOwnerType::Lead, $targID
		);

		Crm\Relation\EntityRelationTable::rebindWhereItemIsChild(
			new Crm\ItemIdentifier(\CCrmOwnerType::Lead, $seedID),
			new Crm\ItemIdentifier(\CCrmOwnerType::Lead, $targID)
		);
	}
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
		$dbResult = \CCrmLead::GetListEx([], ['=ID' => $seedID, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ORIGINATOR_ID', 'ORIGIN_ID']);
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
	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ): array
	{
		$notifyMessageCallback = function (?string $languageId = null) use (
			$collisions,
			$seed,
			$targ,
		): ?string
		{
			self::includeLangFile();

			$replacements = [
				'#USER_NAME#' => $this->getUserName(),
				'#SEED_TITLE#' => $seed['TITLE'] ?? '',
				'#SEED_ID#' => $seed['ID'] ?? '',
				'#TARG_TITLE#' => $targ['TITLE'] ?? '',
				'#TARG_ID#' => $targ['ID'] ?? '',
			];

			$messages = [];
			if (isset(
				$collisions[EntityMergeCollision::READ_PERMISSION_LACK],
				$collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK],
			))
			{
				$messages[] = Loc::getMessage(
					'CRM_LEAD_MERGER_COLLISION_READ_UPDATE_PERMISSION',
					$replacements,
					$languageId,
				);
			}
			elseif (isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK]))
			{
				$messages[] = Loc::getMessage(
					'CRM_LEAD_MERGER_COLLISION_READ_PERMISSION',
					$replacements,
					$languageId,
				);
			}
			elseif (isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
			{
				$messages[] = Loc::getMessage(
					'CRM_LEAD_MERGER_COLLISION_UPDATE_PERMISSION',
					$replacements,
					$languageId,
				);
			}

			if (empty($messages))
			{
				return null;
			}

			return implode('<br/>', $messages);
		};

		return array(
			'TO_USER_ID' => isset($seed['ASSIGNED_BY_ID']) ? (int)$seed['ASSIGNED_BY_ID'] : 0,
			'NOTIFY_MESSAGE' => $notifyMessageCallback,
			'NOTIFY_MESSAGE_OUT' => $notifyMessageCallback,
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
