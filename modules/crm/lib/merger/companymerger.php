<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Binding;
use Bitrix\Crm\Timeline;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/crm/lib/webform/entity.php');

class CompanyMerger extends EntityMerger
{
	/** @var bool */
	private static $langIncluded = false;
	/** @var \CCrmCompany|null */
	private $entity = null;

	/**
	 * @param int $userID User ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 * @throws Main\ArgumentException
	 */
	public function __construct($userID, $enablePermissionCheck = false)
	{
		parent::__construct(\CCrmOwnerType::Company, $userID, $enablePermissionCheck);
	}
	/**
	 * Get entity
	 * @return \CCrmCompany|null
	 */
	protected function getEntity()
	{
		if($this->entity === null)
		{
			$this->entity = new \CCrmCompany(false);
		}
		return $this->entity;
	}
	/**
	 * Get entity metadata
	 * @return array
	 */
	protected function getEntityFieldsInfo()
	{
		return \CCrmCompany::GetFieldsInfo();
	}
	/**
	 * Get entity user fields metadata
	 * @return array
	 */
	protected function getEntityUserFieldsInfo()
	{
		return \CCrmCompany::GetUserFields();
	}

	/**
	 * Get field caption
	 * @param string $fieldId
	 * @return string
	 */
	protected function getFieldCaption(string $fieldId):string
	{
		return \CCrmCompany::GetFieldCaption($fieldId);
	}
	/**
	 * Get entity responsible ID
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return int
	 * @throws EntityMergerException
	 */
	protected function getEntityResponsibleID($entityID, $roleID)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Company, $entityID, $roleID, EntityMergerException::NOT_FOUND);
		}
		return isset($fields['ASSIGNED_BY_ID']) ? (int)$fields['ASSIGNED_BY_ID'] : 0;
	}
	/**
	 * Get entity fields
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return array
	 * @throws EntityMergerException
	 */
	protected function getEntityFields($entityID, $roleID)
	{
		$dbResult = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('*', 'UF_*')
		);
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			throw new EntityMergerException(\CCrmOwnerType::Company, $entityID, $roleID, EntityMergerException::NOT_FOUND);
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
		return \CCrmCompany::CheckReadPermission($entityID, $userPermissions);
	}
	/**
	 * Check entity update permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityUpdatePermission($entityID, $userPermissions)
	{
		return \CCrmCompany::CheckUpdatePermission($entityID, $userPermissions);
	}
	/**
	 * Check entity delete permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	protected function checkEntityDeletePermission($entityID, $userPermissions)
	{
		return \CCrmCompany::CheckDeletePermission($entityID, $userPermissions);
	}
	/**
	 * Prepare recovery data
	 * @param Recovery\EntityRecoveryData $recoveryData Target recovery data.
	 * @param array &$fields Entity Fields.
	 * @return void
	 */
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

	protected function mergeBoundEntitiesBatch(array &$seeds, array &$targ, $skipEmpty = false, array $options = array())
	{
		$contactMerger = new CompanyContactBindingMerger();
		$contactMerger->merge($seeds, $targ, $skipEmpty, $options);

		parent::mergeBoundEntitiesBatch($seeds, $targ, $skipEmpty, $options);
	}

	protected function innerPrepareEntityFieldMergeData($fieldID, array $fieldParams,  array $seeds, array $targ, array $options = null)
	{
		if($fieldID === 'CONTACT_ID')
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
					$seedContactBindings = Binding\ContactCompanyTable::getCompanyBindings($seedID);
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
				$targContactBindings = Binding\ContactCompanyTable::getCompanyBindings($targID);
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
				'FIELD_ID' => 'CONTACT_ID',
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
				\CCrmOwnerType::Company,
				$entityID,
				$roleID,
				EntityMergerException::UPDATE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}
	/**
	 * Delete entity
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Operation options.
	 * @return void
	 * @throws EntityMergerException
	 */
	protected function deleteEntity($entityID, $roleID, array $options = array())
	{
		$entity = $this->getEntity();
		if(!$entity->Delete($entityID, $options))
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Company,
				$entityID,
				$roleID,
				EntityMergerException::DELETE_FAILED,
				'',
				0,
				new Main\SystemException($entity->LAST_ERROR)
			);
		}
	}
	/**
	 * Unbind dependencies from seed entity and bind them to target entity
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @return void
	 */
	protected function rebind($seedID, $targID)
	{
		$seedID = (int)$seedID;
		$targID = (int)$targID;

		$relations = \Bitrix\Crm\Service\Container::getInstance()
			->getRelationManager()
			->getChildRelations(\CCrmOwnerType::Company)
		;
		$itemFrom = new Crm\ItemIdentifier(\CCrmOwnerType::Company, $seedID);
		$itemTo = new Crm\ItemIdentifier(\CCrmOwnerType::Company, $targID);
		foreach ($relations as $relation)
		{
			if (
				($relation->getChildEntityTypeId() === \CCrmOwnerType::Contact)
				&& $this->map !== null
				&& isset($this->map['CONTACT_ID'])
			)
			{
				//Skip contacts if they were processed by map
				continue;
			}

			$relation->replaceAllItemBindings($itemFrom, $itemTo);
		}

		\CCrmActivity::Rebind(\CCrmOwnerType::Company, $seedID, $targID);
		\CCrmLiveFeed::Rebind(\CCrmOwnerType::Company, $seedID, $targID);
		\CCrmSonetRelation::RebindRelations(\CCrmOwnerType::Company, $seedID, $targID);
		\CCrmEvent::Rebind(\CCrmOwnerType::Company, $seedID, $targID);
		EntityRequisite::rebind(\CCrmOwnerType::Company, $seedID, $targID);

		Timeline\ActivityEntry::rebind(\CCrmOwnerType::Company, $seedID, $targID);
		Timeline\CreationEntry::rebind(\CCrmOwnerType::Company, $seedID, $targID);
		Timeline\MarkEntry::rebind(\CCrmOwnerType::Company, $seedID, $targID);
		Timeline\CommentEntry::rebind(\CCrmOwnerType::Company, $seedID, $targID);
		Timeline\LogMessageEntry::rebind(\CCrmOwnerType::Company, $seedID, $targID);

		Crm\Tracking\Entity::rebindTrace(
			\CCrmOwnerType::Company, $seedID,
			\CCrmOwnerType::Company, $targID
		);
	}
	/**
	 * Resolve merging collisions
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @param array &$results Result array.
	 * @return void
	 */
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
		$dbResult = \CCrmCompany::GetListEx(array(), array('=ID' => $seedID), false, false, array('ORIGINATOR_ID', 'ORIGIN_ID'));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return;
		}

		$originatorID = isset($fields['ORIGINATOR_ID']) ? $fields['ORIGINATOR_ID'] : '';
		$originID = isset($fields['ORIGIN_ID']) ? $fields['ORIGIN_ID'] : '';
		if($originatorID !== '' || $originID !== '')
		{
			$results[EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP] = new EntityMergeCollision(\CCrmOwnerType::Company, $seedID, $targID, EntityMergeCollision::SEED_EXTERNAL_OWNERSHIP);
		}
	}
	/**
	 * Prepare collision messages
	 * @param array &$collisions Collisions.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @return array|null
	 */
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
			$messages[] = GetMessage('CRM_COMPANY_MERGER_COLLISION_READ_UPDATE_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::READ_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_COMPANY_MERGER_COLLISION_READ_PERMISSION', $replacements);
		}
		elseif(isset($collisions[EntityMergeCollision::UPDATE_PERMISSION_LACK]))
		{
			$messages[] = GetMessage('CRM_COMPANY_MERGER_COLLISION_UPDATE_PERMISSION', $replacements);
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

	protected static function getFieldConflictResolver(string $fieldId, string $type): ConflictResolver\Base
	{
		$userDefinedResolver = static::getUserDefinedConflictResolver(
			\CCrmOwnerType::Company,
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
				$resolver = new Crm\Merger\ConflictResolver\StringField($fieldId);
				$resolver->setEmptyValues(static::getEqualTitleValues());
				return $resolver;

			case 'COMMENTS':
				return new Crm\Merger\ConflictResolver\HtmlField($fieldId);

			case 'BANKING_DETAILS':
				return new Crm\Merger\ConflictResolver\TextField($fieldId);

			case 'OPENED':
				return new Crm\Merger\ConflictResolver\IgnoredField($fieldId);
		}
		return parent::getFieldConflictResolver($fieldId, $type);
	}

	protected static function isFieldNotEmpty(array $fieldInfo, array $fields, string $fieldId): bool
	{
		$fieldValue = $fields[$fieldId];
		return !($fieldId === 'TITLE'
			&& in_array($fieldValue, static::getEqualTitleValues(), true));
	}

	/**
	 * @return array
	 */
	protected static function getEqualTitleValues(): array
	{
		return [
			Loc::getMessage('CRM_WEBFORM_ENTITY_FIELD_NAME_COMPANY_TEMPLATE'),
			Loc::getMessage('CRM_COMPANY_UNTITLED')
		];
	}

	/**
	 * Include language file
	 * @return void
	 */
	private static function includeLangFile()
	{
		if(!self::$langIncluded)
		{
			self::$langIncluded = IncludeModuleLangFile(__FILE__);
		}
	}
}
