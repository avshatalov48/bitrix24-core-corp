<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Timeline;

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
	 * Get Enity field infos
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
			//Crutch for ContactID Field. It is obsolete and can be ignored. See DealMerger::innerMergeBoundEntities.
			return true;
		}

		//Crutch for Opportunity Field. It can be ignored if ProductRows are not empty. We will recalculate Opportunity after merging of ProductRows. See DealMerger::innerMergeBoundEntities.
		if($fieldID === 'OPPORTUNITY')
		{
			$seedProductRows = isset($seed['PRODUCT_ROWS']) && is_array($seed['PRODUCT_ROWS'])
				? $seed['PRODUCT_ROWS'] : \CCrmDeal::LoadProductRows($seedID);

			if(!empty($seedProductRows))
			{
				$seed['PRODUCT_ROWS'] = $seedProductRows;
			}

			$targProductRows = isset($targ['PRODUCT_ROWS']) && is_array($targ['PRODUCT_ROWS'])
				? $targ['PRODUCT_ROWS'] : \CCrmDeal::LoadProductRows($targID);

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
		if($fieldID === 'CONTACT_ID')
		{
			return false;
		}
		return parent::canMergeEntityField($fieldID);
	}

	/**
	 * @param array $seed
	 * @param array $targ
	 * @param bool $skipEmpty
	 * @param array $options
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	protected function innerMergeBoundEntities(array &$seed, array &$targ, $skipEmpty = false, array $options = array())
	{
		$seedID = isset($seed['ID']) ? (int)$seed['ID'] : 0;
		$targID = isset($targ['ID']) ? (int)$targ['ID'] : 0;

		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];

		//region Contacts
		$seedContactBindings = null;
		if($seedID > 0)
		{
			$seedContactBindings = Binding\DealContactTable::getDealBindings($seedID);
		}
		elseif(isset($seed['CONTACT_BINDINGS']) && is_array($seed['CONTACT_BINDINGS']))
		{
			$seedContactBindings = $seed['CONTACT_BINDINGS'];
		}
		elseif(isset($seed['CONTACT_ID']) || (isset($seed['CONTACT_IDS']) && is_array($seed['CONTACT_IDS'])))
		{
			$seedContactBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				isset($seed['CONTACT_IDS']) && is_array($seed['CONTACT_IDS'])
					? $seed['CONTACT_IDS']
					: array($seed['CONTACT_ID'])
			);
		}

		$targContactBindings = null;
		if($targID > 0)
		{
			$targContactBindings = Binding\DealContactTable::getDealBindings($targID);
		}
		elseif(isset($targ['CONTACT_BINDINGS']) && is_array($targ['CONTACT_BINDINGS']))
		{
			$targContactBindings = $targ['CONTACT_BINDINGS'];
		}
		elseif(isset($targ['CONTACT_ID']) || (isset($targ['CONTACT_IDS']) && is_array($targ['CONTACT_IDS'])))
		{
			$targContactBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Contact,
				isset($targ['CONTACT_IDS']) && is_array($targ['CONTACT_IDS'])
					? $targ['CONTACT_IDS']
					: array($targ['CONTACT_ID'])
			);
		}

		if($seedContactBindings !== null && count($seedContactBindings) > 0)
		{
			if(!$skipMultipleFields)
			{
				if($targContactBindings === null || count($targContactBindings) === 0)
				{
					$targContactBindings = $seedContactBindings;
				}
				else
				{
					self::mergeEntityBindings(\CCrmOwnerType::Contact, $seedContactBindings, $targContactBindings);
				}

				$targ['CONTACT_BINDINGS'] = $targContactBindings;
			}
			elseif($targContactBindings === null || (count($targContactBindings) === 0 && !$skipEmpty))
			{
				$targ['CONTACT_BINDINGS'] = $seedContactBindings;
			}
		}
		//endregion

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

		$targObserverIDs = null;
		if(isset($targ['OBSERVER_IDS']) && is_array($targ['OBSERVER_IDS']))
		{
			$targObserverIDs = $targ['OBSERVER_IDS'];
		}
		elseif($targID > 0)
		{
			$targObserverIDs = Crm\Observer\ObserverManager::getEntityObserverIDs(\CCrmOwnerType::Deal, $targID);
		}

		if($seedObserverIDs !== null && count($seedObserverIDs) > 0)
		{
			if(!$skipMultipleFields)
			{
				if($targObserverIDs === null || count($targObserverIDs) === 0)
				{
					$targObserverIDs = $seedObserverIDs;
				}
				else
				{
					$addedObserverIDs = array_diff($seedObserverIDs, $targObserverIDs);
					if(!empty($addedObserverIDs))
					{
						$targObserverIDs = array_merge($targObserverIDs, $addedObserverIDs);
					}
				}

				$targ['OBSERVER_IDS'] = $targObserverIDs;
			}
			elseif($targObserverIDs === null || (count($targObserverIDs) === 0 && !$skipEmpty))
			{
				$targ['OBSERVER_IDS'] = $seedObserverIDs;
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

		$targProductRows = null;
		if(isset($targ['PRODUCT_ROWS']) && is_array($targ['PRODUCT_ROWS']))
		{
			$targProductRows = $targ['PRODUCT_ROWS'];
		}
		elseif($targID > 0)
		{
			$targProductRows = \CCrmDeal::LoadProductRows($targID);
		}

		if($seedProductRows !== null && count($seedProductRows) > 0)
		{
			if(!$skipMultipleFields)
			{
				if($targProductRows === null || count($targProductRows) === 0)
				{
					$targ['PRODUCT_ROWS'] = $seedProductRows;
				}
				else
				{
					$diffProductRows = \CCrmProductRow::GetDiff(array($seedProductRows), array($targProductRows));
					if(!empty($diffProductRows))
					{
						$productRowMaxSort = 0;
						$productRowCount = count($targProductRows);
						if($productRowCount > 0 && isset($targProductRows[$productRowCount - 1]['SORT']))
						{
							$productRowMaxSort = (int)$targProductRows[$productRowCount - 1]['SORT'];
						}

						foreach($diffProductRows as $productRow)
						{
							$productRow['SORT'] = ($productRowMaxSort += 10);
							$targProductRows[] = $productRow;
						}

						$targ['PRODUCT_ROWS'] = $targProductRows;
					}
				}
			}
			elseif($targProductRows === null || (count($targProductRows) === 0 && !$skipEmpty))
			{
				$targ['PRODUCT_ROWS'] = $seedProductRows;
			}
		}
		//endregion
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
				new Main\SystemException($entity->LAST_ERROR)
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
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}

	protected function rebind($seedID, $targID)
	{
		Binding\DealContactTable::rebindAllContacts($seedID, $targID);
		\CCrmQuote::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmActivity::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmLiveFeed::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmSonetRelation::RebindRelations(\CCrmOwnerType::Deal, $seedID, $targID);
		\CCrmEvent::Rebind(\CCrmOwnerType::Deal, $seedID, $targID);

		Timeline\ActivityEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\CreationEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\MarkEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
		Timeline\CommentEntry::rebind(\CCrmOwnerType::Deal, $seedID, $targID);
	}
}