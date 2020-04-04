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

	protected function innerMergeBoundEntities(array &$seed, array &$targ, $skipEmpty = false, array $options = array())
	{
		$seedID = isset($seed['ID']) ? (int)$seed['ID'] : 0;
		$targID = isset($targ['ID']) ? (int)$targ['ID'] : 0;

		//region Merge company bindings
		$seedBindings = null;
		if($seedID > 0)
		{
			$seedBindings = Binding\ContactCompanyTable::getContactBindings($seedID);
		}
		elseif(isset($seed['COMPANY_BINDINGS']) && is_array($seed['COMPANY_BINDINGS']))
		{
			$seedBindings = $seed['COMPANY_BINDINGS'];
		}
		elseif(isset($seed['COMPANY_ID']) || (isset($seed['COMPANY_IDS']) && is_array($seed['COMPANY_IDS'])))
		{
			$seedBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Company,
				isset($seed['COMPANY_IDS']) && is_array($seed['COMPANY_IDS'])
					? $seed['COMPANY_IDS']
					: array($seed['COMPANY_ID'])
			);
		}

		$targBindings = null;
		if($targID > 0)
		{
			$targBindings = Binding\ContactCompanyTable::getContactBindings($targID);
		}
		elseif(isset($targ['COMPANY_BINDINGS']) && is_array($targ['COMPANY_BINDINGS']))
		{
			$targBindings = $targ['COMPANY_BINDINGS'];
		}
		elseif(isset($targ['COMPANY_ID']) || (isset($targ['COMPANY_IDS']) && is_array($targ['COMPANY_IDS'])))
		{
			$targBindings = Binding\EntityBinding::prepareEntityBindings(
				\CCrmOwnerType::Company,
				isset($targ['COMPANY_IDS']) && is_array($targ['COMPANY_IDS'])
					? $targ['COMPANY_IDS']
					: array($targ['COMPANY_ID'])
			);
		}

		//TODO: Rename SKIP_MULTIPLE_USER_FIELDS -> ENABLE_MULTIPLE_FIELDS_ENRICHMENT
		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];
		if($seedBindings !== null && count($seedBindings) > 0)
		{
			if(!$skipMultipleFields)
			{
				if($targBindings === null || count($targBindings) === 0)
				{
					$targBindings = $seedBindings;
				}
				else
				{
					self::mergeEntityBindings(\CCrmOwnerType::Company, $seedBindings, $targBindings);
				}
				$targ['COMPANY_BINDINGS'] = $targBindings;
			}
			elseif($targBindings === null || (count($targBindings) === 0 && !$skipEmpty))
			{
				$targ['COMPANY_BINDINGS'] = $seedBindings;
			}
		}
		//endregion

		parent::innerMergeBoundEntities($seed, $targ, $skipEmpty, $options);
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
		Binding\DealContactTable::rebindAllDeals($seedID, $targID);
		Binding\QuoteContactTable::rebindAllQuotes($seedID, $targID);
		Binding\ContactCompanyTable::rebindAllCompanies($seedID, $targID);
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