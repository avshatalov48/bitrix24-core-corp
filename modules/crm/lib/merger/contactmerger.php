<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Timeline;

class ContactMerger extends EntityMerger
{
	private static $langIncluded = false;
	private $entity = null;

	public function __construct($userID, $enablePermissionCheck = false)
	{
		parent::__construct(\CCrmOwnerType::Contact, $userID, $enablePermissionCheck);
	}
	protected function getEntity()
	{
		if($this->entity === null)
		{
			$this->entity = new \CCrmContact(false);
		}
		return $this->entity;
	}
	protected function getEntityFieldsInfo()
	{
		return \CCrmContact::GetFieldsInfo();
	}
	protected function getEntityUserFieldsInfo()
	{
		return \CCrmContact::GetUserFields();
	}
	protected function getEntityResponsibleID($entityID, $roleID)
	{
		$dbResult = \CCrmContact::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Contact, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}
	protected function getEntityFields($entityID, $roleID)
	{
		$dbResult = \CCrmContact::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Contact, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return $fields;
	}
	protected function checkEntityReadPermission($entityID, $userPermissions)
	{
		return \CCrmContact::CheckReadPermission($entityID, $userPermissions);
	}
	protected function checkEntityUpdatePermission($entityID, $userPermissions)
	{
		return \CCrmContact::CheckUpdatePermission($entityID, $userPermissions);
	}
	protected function checkEntityDeletePermission($entityID, $userPermissions)
	{
		return \CCrmContact::CheckDeletePermission($entityID, $userPermissions);
	}
	protected function setupRecoveryData(Recovery\EntityRecoveryData $recoveryData, array &$fields)
	{
		$recoveryData->setTitle(\CCrmContact::PrepareFormattedName($fields));
		if(isset($fields['ASSIGNED_BY_ID']))
		{
			$recoveryData->setResponsibleID((int)$fields['ASSIGNED_BY_ID']);
		}
	}

	protected static function canMergeEntityField($fieldID)
	{
		//Field CompanyID is obsolete. It is replaced by CompanyIDs
		if($fieldID === 'COMPANY_ID')
		{
			return false;
		}
		return parent::canMergeEntityField($fieldID);
	}

	protected function mergeBoundEntitiesBatch(array &$seeds, array &$targ, $skipEmpty = false, array $options = array())
	{
		$companyMerger = new ContactCompanyBindingMerger();
		$companyMerger->merge($seeds, $targ, $skipEmpty, $options);

		parent::mergeBoundEntitiesBatch($seeds, $targ, $skipEmpty, $options);
	}

	protected function innerPrepareEntityFieldMergeData($fieldID, array $fieldParams,  array $seeds, array $targ, array $options = null)
	{
		if($fieldID === 'COMPANY_IDS')
		{
			$enabledIdsMap = null;
			if(isset($options['enabledIds']) && is_array($options['enabledIds']))
			{
				$enabledIdsMap = array_fill_keys($options['enabledIds'], true);
			}

			$sourceEntityIDs = array();
			$resultCompanyBindings = array();
			foreach($seeds as $seed)
			{
				$seedID = (int)$seed['ID'];
				if(is_null($enabledIdsMap) || isset($enabledIdsMap[$seedID]))
				{
					$seedCompanyBindings = Binding\ContactCompanyTable::getContactBindings($seedID);
					if(!empty($seedCompanyBindings))
					{
						$sourceEntityIDs[] = $seedID;
						self::mergeEntityBindings(
							\CCrmOwnerType::Company,
							$seedCompanyBindings,
							$resultCompanyBindings
						);
					}
				}
			}

			$targID = (int)$targ['ID'];
			if(is_null($enabledIdsMap) || isset($enabledIdsMap[$targID]))
			{
				$targCompanyBindings = Binding\ContactCompanyTable::getContactBindings($targID);
				if(!empty($targCompanyBindings))
				{
					$sourceEntityIDs[] = $targID;
					self::mergeEntityBindings(
						\CCrmOwnerType::Company,
						$targCompanyBindings,
						$resultCompanyBindings
					);
				}
			}

			return array(
				'FIELD_ID' => 'COMPANY_IDS',
				'TYPE' => 'crm_company',
				'IS_MERGED' => true,
				'IS_MULTIPLE' => true,
				'SOURCE_ENTITY_IDS' => array_unique($sourceEntityIDs, SORT_NUMERIC),
				'VALUE' => Binding\EntityBinding::prepareEntityIDs(\CCrmOwnerType::Company, $resultCompanyBindings),
			);
		}
		return parent::innerPrepareEntityFieldMergeData($fieldID, $fieldParams, $seeds, $targ, $options);
	}

	/**
	 * Update entity
	 * @param int $entityID Entity ID.
	 * @param array &$fields Entity Fields.
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
				\CCrmOwnerType::Contact,
				$entityID,
				$roleID,
				EntityMergerException::UPDATE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}
	protected function deleteEntity($entityID, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		if(!$entity->Delete($entityID, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Contact,
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
		//Skip companies if they were processed by map
		if(!($this->map !== null && isset($this->map['COMPANY_IDS'])))
		{
			Binding\ContactCompanyTable::rebindAllCompanies($seedID, $targID);
		}

		Binding\DealContactTable::rebindAllDeals($seedID, $targID);
		Binding\QuoteContactTable::rebindAllQuotes($seedID, $targID);

		\CCrmDeal::Rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		\CCrmQuote::Rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		\CCrmInvoice::Rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		\CCrmActivity::Rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		\CCrmLiveFeed::Rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		\CCrmSonetRelation::RebindRelations(\CCrmOwnerType::Contact, $seedID, $targID);
		\CCrmEvent::Rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		EntityRequisite::rebind(\CCrmOwnerType::Contact, $seedID, $targID);

		Timeline\ActivityEntry::rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		Timeline\CreationEntry::rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		Timeline\MarkEntry::rebind(\CCrmOwnerType::Contact, $seedID, $targID);
		Timeline\CommentEntry::rebind(\CCrmOwnerType::Contact, $seedID, $targID);
	}
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
		$dbResult = \CCrmContact::GetListEx(array(), array('=ID' => $seedID), false, false, array('ORIGINATOR_ID', 'ORIGIN_ID'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return;
		}

		$originatorID = isset($fields['ORIGINATOR_ID']) ? $fields['ORIGINATOR_ID'] : '';
		$originID = isset($fields['ORIGIN_ID']) ? $fields['ORIGIN_ID'] : '';
		if($originatorID !== '' || $originID !== '')
		{
			$results[EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP] = new EntityMergeCollision(\CCrmOwnerType::Contact, $seedID, $targID, EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP);
		}
	}
	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ)
	{
		self::includeLangFile();
		$replacements = array(
			'#USER_NAME#' => $this->getUserName(),
			'#SEED_TITLE#' => \CCrmContact::PrepareFormattedName($seed),
			'#SEED_ID#' => isset($seed['ID']) ? $seed['ID'] : '',
			'#TARG_TITLE#' => \CCrmContact::PrepareFormattedName($targ),
			'#TARG_ID#' => isset($targ['ID']) ? $targ['ID'] : '',
		);

		$messages = array();
		if(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK])
			&& isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_CONTACT_MERGER_COLLISION_READ_UPDATE_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_CONTACT_MERGER_COLLISION_READ_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_CONTACT_MERGER_COLLISION_UPDATE_PERMISSION', $replacements);
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
	private static function includeLangFile()
	{
		if(!self::$langIncluded)
		{
			self::$langIncluded = IncludeModuleLangFile(__FILE__);
		}
	}
}