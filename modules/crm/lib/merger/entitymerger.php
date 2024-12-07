<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Crm;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Recovery;
use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Main;

abstract class EntityMerger
{
	const ROLE_UNDEFINED = 0;
	const ROLE_SEED = 1;
	const ROLE_TARG = 2;

	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $userID = 0;
	protected $userIsAdmin = false;
	protected $userPermissions = null;
	protected $userName = null;

	protected $enablePermissionCheck = false;
	protected $conflictResolutionMode = ConflictResolutionMode::UNDEFINED;
	protected $map = null;
	protected $isAutomatic = false;

	/**
	 * @param int $entityTypeID Entity Type ID.
	 * @param int $userID User ID.
	 * @param bool|false $enablePermissionCheck Permission check flag.
	 * @throws Main\ArgumentException
	 */
	public function __construct($entityTypeID, $userID, $enablePermissionCheck = false)
	{
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined', 'entityTypeID');
		}

		$this->entityTypeID = $entityTypeID;
		$this->setUserID($userID);
		$this->enabledPermissionCheck($enablePermissionCheck);
		$this->conflictResolutionMode = self::getDefaultConflictResolutionMode();
	}
	/**
	 * Create new entity merger by specified entity type ID.
	 * @static
	 * @param int $entityTypeID Entity type ID.
	 * @param int $currentUserID User ID.
	 * @param bool $enablePermissionCheck Permission check flag.
	 * @return EntityMerger
	 */
	public static function create($entityTypeID, $currentUserID, $enablePermissionCheck = false)
	{
		return EntityMergerFactory::create($entityTypeID, $currentUserID, $enablePermissionCheck);
	}
	/**
	 * Get entity type ID.
	 * @return int
	 */
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	/**
	 * Get entity type name.
	 * @return string
	 */
	public function getEntityTypeName()
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeID);
	}
	/**
	 * Check if permission check is enabled.
	 * @return bool
	 */
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	/**
	 * Enable or disable permission check flag.
	 * @param bool $enable Enable permission check flag.
	 * @return void
	 */
	public function enabledPermissionCheck($enable)
	{
		$this->enablePermissionCheck = is_bool($enable) ? $enable : (bool)$enable;
	}

	public function getConflictResolutionMode()
	{
		return $this->conflictResolutionMode;
	}
	public function setConflictResolutionMode($mode)
	{
		if(!is_int($mode))
		{
			$mode = (int)$mode;
		}

		if(!ConflictResolutionMode::isDefined($mode))
		{
			throw new Main\ArgumentOutOfRangeException('mode',
				ConflictResolutionMode::FIRST,
				ConflictResolutionMode::LAST
			);
		}

		$this->conflictResolutionMode = $mode;
	}

	public function getMap()
	{
		return $this->map;
	}
	public function setMap(array $map)
	{
		$this->map = $map;
	}

	public function isAutomatic():bool
	{
		return $this->isAutomatic;
	}
	public function setIsAutomatic(bool $isAutomatic): void
	{
		$this->isAutomatic = $isAutomatic;
	}

	public static function getDefaultConflictResolutionMode()
	{
		return ConflictResolutionMode::NEVER_OVERWRITE;
	}

	/**
	 * Check if user is admin.
	 * @return bool
	 */
	public function isAdminUser()
	{
		return $this->userIsAdmin;
	}
	/**
	 * Get user ID.
	 * @return int
	 */
	public function getUserID()
	{
		return $this->userID;
	}
	/**
	 * Set user ID.
	 * @param int $userID User ID.
	 * @return void
	 */
	public function setUserID($userID)
	{
		if(!is_integer($userID))
		{
			$userID = intval($userID);
		}
		$userID = max($userID, 0);

		if($this->userID === $userID)
		{
			return;
		}

		$this->userID = $userID;
		$this->userPermissions = null;
		$this->userName = null;
		$this->userIsAdmin =  \CCrmPerms::IsAdmin($userID);
	}
	/**
	 * Get user name.
	 * @return string
	 */
	public function getUserName()
	{
		if($this->userName !== null)
		{
			return $this->userName;
		}

		if($this->userID <= 0)
		{
			return ($this->userName = '');
		}

		$dbResult = \CUser::GetList(
			'id',
			'asc',
			array('ID'=> $this->userID),
			array('FIELDS'=> array('ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE')
			)
		);

		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return ($this->userName = '');
		}
		return ($this->userName = \CUser::FormatName(Crm\Format\PersonNameFormatter::getFormat(), $fields, false, false));
	}
	/**
	 * Check if role ID id defined.
	 * @param int $roleID Role ID.
	 * @return bool
	 */
	public static function isRoleDefined($roleID)
	{
		if(!is_int($roleID))
		{
			$roleID = (int)$roleID;
		}
		return $roleID === self::ROLE_SEED || $roleID === self::ROLE_TARG;
	}
	/**
	 * Check if entity can be merged.
	 * @param int $entityID Entity ID.
	 * @param int $roleID Role ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public function isMergable($entityID, $roleID)
	{
		if(!$this->enablePermissionCheck)
		{
			return true;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($roleID))
		{
			$roleID = (int)$roleID;
		}
		if(!self::isRoleDefined($roleID))
		{
			throw new Main\ArgumentException('Merge role is not defined', 'roleID');
		}

		$entityTypeID = $this->entityTypeID;
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$permissions = $this->getUserPermissions();

		if($roleID === self::ROLE_SEED)
		{
			return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $permissions)
				&& \CCrmAuthorizationHelper::CheckDeletePermission($entityTypeName, $entityID, $permissions);
		}
		else
		{
			return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $permissions)
				&& \CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $permissions);
		}
	}
	/**
	 * Get possible merge collisions.
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @return array[EntityMergeCollision]
	 */
	public function getMergeCollisions($seedID, $targID)
	{
		if(!is_int($seedID))
		{
			$seedID = (int)$seedID;
		}

		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		$results = array();

		$seedResponsibleID = $this->getEntityResponsibleID($seedID, self::ROLE_SEED);
		$targResponsibleID = $this->getEntityResponsibleID($targID, self::ROLE_TARG);

		if($seedResponsibleID > 0 && $seedResponsibleID !== $targResponsibleID)
		{
			$responsiblePermissions = \CCrmPerms::GetUserPermissions($seedResponsibleID);
			if(!$this->checkEntityReadPermission($targID, $responsiblePermissions))
			{
				$results[EntityMergeCollision::READ_PERMISSION_LACK] = new EntityMergeCollision($this->entityTypeID, $seedID, $targID, EntityMergeCollision::READ_PERMISSION_LACK);
			}
			if($this->checkEntityUpdatePermission($seedID, $responsiblePermissions)
				&& !$this->checkEntityUpdatePermission($targID, $responsiblePermissions))
			{
				$results[EntityMergeCollision::UPDATE_PERMISSION_LACK] = new EntityMergeCollision($this->entityTypeID, $seedID, $targID, EntityMergeCollision::UPDATE_PERMISSION_LACK);
			}
		}

		$this->resolveMergeCollisions($seedID, $targID, $results);
		return $results;
	}
	/**
	 * Get merging collisions.
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @param array &$results Result array.
	 * @return void
	 */
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
	}
	/**
	 * Merge fields. If value is absent in target field it will be copied from seed field.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @param bool $skipEmpty Skip empty fields flag. If is enabled then empty fields of "seed" will not be replaced by fields from "targ"
	 * @param array $options Options array.
	 * @return void
	 */
	public function mergeFields(array &$seed, array &$targ, $skipEmpty = false, array $options = array())
	{
		$entityFieldInfos = $this->getEntityFieldsInfo();
		$userFieldInfos = $this->getEntityUserFieldsInfo();

		$this->innerMergeEntityFields($seed, $targ, $entityFieldInfos, $skipEmpty, $options);
		$this->mergeUserFields($seed, $targ, $userFieldInfos, $options);

		$seedMultiFields = isset($seed['FM']) && is_array($seed['FM']) ? $seed['FM'] : array();
		$targMultiFields = isset($targ['FM']) && is_array($targ['FM']) ? $targ['FM'] : array();

		//Skip multifields if target fields is defined in skipempty mode.
		if(!$skipEmpty || empty($targMultiFields))
		{
			EntityMerger::mergeMultiFields($seedMultiFields, $targMultiFields, $skipEmpty);
		}

		if(!empty($targMultiFields))
		{
			$targ['FM'] = $targMultiFields;
		}
	}
	public function mergeBatch(array $seedIDs, $targID, Integrity\DuplicateCriterion $targCriterion = null)
	{
		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		$seedIDs = array_filter(
			array_map('intval', $seedIDs),
			function($entityId) use($targID)
			{
				return $entityId > 0 && $entityId !== $targID;
			}
		);

		if(empty($seedIDs))
		{
			return;
		}

		if($this->enablePermissionCheck && !$this->userIsAdmin)
		{
			$userPermissions = $this->getUserPermissions();
			foreach($seedIDs as $seedID)
			{
				if(!$this->checkEntityReadPermission($seedID, $userPermissions))
				{
					throw new EntityMergerException(
						$this->entityTypeID,
						$seedID,
						self::ROLE_SEED,
						EntityMergerException::READ_DENIED
					);
				}
				if(!$this->checkEntityDeletePermission($seedID, $userPermissions))
				{
					throw new EntityMergerException(
						$this->entityTypeID,
						$seedID,
						self::ROLE_SEED,
						EntityMergerException::DELETE_DENIED
					);
				}
				if(!$this->checkEntityReadPermission($targID, $userPermissions))
				{
					throw new EntityMergerException(
						$this->entityTypeID,
						$targID,
						self::ROLE_TARG,
						EntityMergerException::READ_DENIED
					);
				}
				if(!$this->checkEntityUpdatePermission($targID, $userPermissions))
				{
					throw new EntityMergerException(
						$this->entityTypeID,
						$targID,
						self::ROLE_TARG,
						EntityMergerException::UPDATE_DENIED
					);
				}
			}
		}

		$collisionMap = array();
		foreach($seedIDs as $seedID)
		{
			$collisionMap[$seedID] = self::getMergeCollisions($seedID, $targID);
		}

		$seedMap = array();
		foreach($seedIDs as $seedID)
		{
			$seedMap[$seedID] = $this->getEntityFields($seedID, self::ROLE_SEED);
		}
		$seeds = array_values($seedMap);

		$targ = $this->getEntityFields($targID, self::ROLE_TARG);

		$entityFieldInfos = $this->getEntityFieldsInfo();
		$userFieldInfos = $this->getEntityUserFieldsInfo();

		$options = array('conflictResolutionMode' => $this->conflictResolutionMode);
		if(is_array($this->map) && !empty($this->map))
		{
			$options['map'] = $this->map;
		}

		$this->mergeEntityFieldsBatch($seeds, $targ, $entityFieldInfos, false, $options);
		$this->mergeBoundEntitiesBatch($seeds, $targ, false, $options);
		$this->mergeUserFieldsBatch($seeds, $targ, $userFieldInfos, $options);

		$matchMap = array();
		foreach($seedIDs as $seedID)
		{
			$matchMap[$seedID] = $this->getRegisteredEntityMatches($this->entityTypeID, $seedID);
			if($targCriterion)
			{
				$targIndexTypeID = $targCriterion->getIndexTypeID();
				$targScope = $targCriterion->getScope();
				if(!isset($matchMap[$seedID][$targIndexTypeID][$targScope]))
				{
					$matchMap[$seedID][$targIndexTypeID][$targScope] = array();
				}

				$targetMatchHash = $targCriterion->getMatchHash();
				if(!isset($matchMap[$seedID][$targIndexTypeID][$targScope][$targetMatchHash]))
				{
					$matchMap[$seedID][$targIndexTypeID][$targScope][$targetMatchHash] = $targCriterion->getMatches();
				}
			}

			//region Merge requisites
			if ($this->entityTypeID === \CCrmOwnerType::Company || $this->entityTypeID === \CCrmOwnerType::Contact)
			{
				$requisiteMergingHelper = new RequisiteMergingHelper($this->entityTypeID, $seedID, $targID);
				$requisiteMergingHelper->merge();
			}
			//endregion Merge requisites
		}

		$historyItems = null;
		if (isset($targ['HISTORY_ITEMS']))
		{
			$historyItems = $targ['HISTORY_ITEMS'];
			unset($targ['HISTORY_ITEMS']);
		}
		$this->updateEntity($targID, $targ, self::ROLE_TARG, [
			'DISABLE_USER_FIELD_CHECK' => true,
			'CURRENT_USER' => $this->getUserID() ?: Crm\Service\Container::getInstance()->getContext()->getUserId(),
		]);
		if (is_array($historyItems))
		{
			$this->saveHistoryItems($targID, $historyItems);
		}

		foreach($seedIDs as $seedID)
		{
			$this->rebind($seedID, $targID);
			$this->deleteEntity($seedID, self::ROLE_SEED, array('ENABLE_DUP_INDEX_INVALIDATION' => false));
			if(isset($matchMap[$seedID]) && !empty($matchMap[$seedID]))
			{
				$this->processEntityDeletion(
					$this->entityTypeID,
					$seedID,
					$matchMap[$seedID],
					array('ROOT_ENTITY_ID' => $targID)
				);
			}

			Integrity\DuplicateManager::markDuplicateIndexAsJunk($this->entityTypeID, $seedID);

			//region Send event
			$event = new Main\Event(
				'crm',
				'OnAfterEntityMerge',
				array(
					'entityTypeID' => $this->entityTypeID,
					'entityTypeName' => \CCrmOwnerType::ResolveName($this->entityTypeID),
					'seedEntityID'  => $seedID,
					'targetEntityID' => $targID,
					'userID' => $this->getUserID()
				)
			);
			$event->send();
			//endregion

			if(isset($collisionMap[$seedID]) && !empty($collisionMap[$seedID]) && isset($seedMap[$seedID]))
			{
				$messageFields = $this->prepareCollisionMessageFields($collisionMap[$seedID], $seedMap[$seedID], $targ);
				if(is_array($messageFields) && !empty($messageFields) && Main\Loader::includeModule('im'))
				{
					$messageFields['FROM_USER_ID'] = $this->userID;
					$messageFields['MESSAGE_TYPE'] = IM_MESSAGE_SYSTEM;
					$messageFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
					$messageFields['NOTIFY_MODULE'] = 'crm';
					$messageFields['NOTIFY_EVENT'] = 'merge';
					$messageFields['NOTIFY_TAG'] = 'CRM|MERGE|COLLISION';

					\CIMNotify::Add($messageFields);
				}
			}
		}
	}
	/**
	 * Merge entities.
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @param Integrity\DuplicateCriterion $targCriterion Criterion.
	 * @return void
	 * @throws EntityMergerException
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 */
	public function merge($seedID, $targID, Integrity\DuplicateCriterion $targCriterion)
	{
		if(!is_int($seedID))
		{
			$seedID = (int)$seedID;
		}

		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		$entityTypeID = $this->entityTypeID;
		if($this->enablePermissionCheck && !$this->userIsAdmin)
		{
			$userPermissions = $this->getUserPermissions();
			if(!$this->checkEntityReadPermission($seedID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $seedID, self::ROLE_SEED, EntityMergerException::READ_DENIED);
			}
			if(!$this->checkEntityDeletePermission($seedID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $seedID, self::ROLE_SEED, EntityMergerException::DELETE_DENIED);
			}
			if(!$this->checkEntityReadPermission($targID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $targID, self::ROLE_TARG, EntityMergerException::READ_DENIED);
			}
			if(!$this->checkEntityUpdatePermission($targID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $targID, self::ROLE_TARG, EntityMergerException::UPDATE_DENIED);
			}
		}

		$collisions = self::getMergeCollisions($seedID, $targID);

		$seed = $this->getEntityFields($seedID, self::ROLE_SEED);
		$targ = $this->getEntityFields($targID, self::ROLE_TARG);

		$entityFieldInfos = $this->getEntityFieldsInfo();
		$userFieldInfos = $this->getEntityUserFieldsInfo();

		$options = array('conflictResolutionMode' => $this->conflictResolutionMode);
		$this->innerMergeEntityFields($seed, $targ, $entityFieldInfos, false, $options);
		$this->mergeUserFields($seed, $targ, $userFieldInfos, $options);

		$matches = $this->getRegisteredEntityMatches($entityTypeID, $seedID);

		//region Merge requisites
		if ($entityTypeID === \CCrmOwnerType::Company || $entityTypeID === \CCrmOwnerType::Contact)
		{
			$requisiteMergingHelper = new RequisiteMergingHelper($entityTypeID, $seedID, $targID);
			$requisiteMergingHelper->merge();
		}
		//endregion Merge requisites

		$historyItems = null;
		if (isset($targ['HISTORY_ITEMS']))
		{
			$historyItems = $targ['HISTORY_ITEMS'];
			unset($targ['HISTORY_ITEMS']);
		}
		$this->updateEntity($targID, $targ, self::ROLE_TARG, [
			'DISABLE_USER_FIELD_CHECK' => true,
			'CURRENT_USER' => $this->getUserID() ?: Crm\Service\Container::getInstance()->getContext()->getUserId(),
		]);

		if (is_array($historyItems))
		{
			$this->saveHistoryItems($targID, $historyItems);
		}

		$this->rebind($seedID, $targID);

		$targIndexTypeID = $targCriterion->getIndexTypeID();
		$targScope = $targCriterion->getScope();
		if(!isset($matches[$targIndexTypeID][$targScope]))
		{
			$matches[$targIndexTypeID][$targScope] = array();
		}

		$targetMatchHash = $targCriterion->getMatchHash();
		if(!isset($matches[$targIndexTypeID][$targScope][$targetMatchHash]))
		{
			$matches[$targIndexTypeID][$targScope][$targetMatchHash] = $targCriterion->getMatches();
		}

		$this->deleteEntity($seedID, self::ROLE_SEED, array('ENABLE_DUP_INDEX_INVALIDATION' => false));
		if(!empty($matches))
		{
			$this->processEntityDeletion($entityTypeID, $seedID, $matches);
		}

		Integrity\DuplicateManager::markDuplicateIndexAsJunk($entityTypeID, $seedID);

		//region Send event
		$event = new Main\Event(
			'crm',
			'OnAfterEntityMerge',
			array(
				'entityTypeID' => $this->entityTypeID,
				'entityTypeName' => \CCrmOwnerType::ResolveName($this->entityTypeID),
				'seedEntityID'  => $seedID,
				'targetEntityID' => $targID,
				'userID' => $this->getUserID()
			)
		);
		$event->send();
		//endregion

		if(!empty($collisions))
		{
			$messageFields = $this->prepareCollisionMessageFields($collisions, $seed, $targ);
			if(is_array($messageFields) && !empty($messageFields) && Main\Loader::includeModule('im'))
			{
				$messageFields['FROM_USER_ID'] = $this->userID;
				$messageFields['MESSAGE_TYPE'] = IM_MESSAGE_SYSTEM;
				$messageFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
				$messageFields['NOTIFY_MODULE'] = 'crm';
				$messageFields['NOTIFY_EVENT'] = 'merge';
				$messageFields['NOTIFY_TAG'] = 'CRM|MERGE|COLLISION';

				\CIMNotify::Add($messageFields);
			}
		}
	}

	protected function checkIfEmptyValue($type, $value)
	{
		if($type === 'string' ||
			$type === 'char' ||
			$type === 'datetime' ||
			$type === 'date' ||
			$type === 'crm_status' ||
			$type === 'crm_currency'
		)
		{
			return $value == '';
		}
		elseif($type === 'double')
		{
			return $value == 0.0;
		}
		elseif($type === 'integer' ||
			$type === 'user' ||
			$type === 'crm_company' ||
			$type === 'crm_contact'
		)
		{
			return $value == 0;
		}
		elseif($type === 'user_field')
		{
			if(is_array($value))
			{
				return empty($value);
			}
			elseif(is_string($value))
			{
				return $value == '';
			}
			return $value == null;
		}
		elseif ($type === 'boolean')
		{
			return $value === null;
		}

		return $value == null;
	}
	protected function innerPrepareEntityFieldMergeData($fieldID, array $fieldParams,  array $seeds, array $targ, array $options = null)
	{
		$type = isset($fieldParams['TYPE']) ? $fieldParams['TYPE'] : 'string';
		$isMultiple = isset($fieldParams['IS_MULTIPLE']) && $fieldParams['IS_MULTIPLE'];
		$result = array('FIELD_ID' => $fieldID, 'TYPE' => $type, 'IS_MERGED' => true, 'IS_MULTIPLE' => $isMultiple);

		$fieldInfo = isset($fieldParams['FIELD_INFO']) && is_array($fieldParams['FIELD_INFO'])
			? $fieldParams['FIELD_INFO'] : array();

		if($options === null)
		{
			$options = array();
		}

		if(!$isMultiple)
		{
			$value = null;
			$sourceEntityID = 0;
			if(isset($targ[$fieldID]))
			{
				$value = $targ[$fieldID];
				$sourceEntityID = (int)$targ['ID'];
			}

			foreach($seeds as $seed)
			{
				if(!(isset($seed[$fieldID]) && !$this->checkIfEmptyValue($type, $seed[$fieldID])))
				{
					continue;
				}

				if($result['IS_MERGED'])
				{
					if($this->checkIfEmptyValue($type, $value))
					{
						$value = $seed[$fieldID];
						$sourceEntityID = (int)$seed['ID'];
					}
					elseif($value != $seed[$fieldID])
					{
						$result['IS_MERGED'] = false;
					}
				}
			}

			if($type === 'user_field')
			{
				if(!$this->checkIfEmptyValue($type, $value))
				{
					$result['VALUE'] = [
						'VALUE' => $value,
						'SIGNATURE' => Crm\UserField\UserFieldManager::prepareUserFieldSignature($fieldInfo, $value),
						'IS_EMPTY' => false
					];
				}
				else
				{
					$result['VALUE'] = [
						'SIGNATURE' => Crm\UserField\UserFieldManager::prepareUserFieldSignature(
							isset($fieldParams['FIELD_INFO']) && is_array($fieldParams['FIELD_INFO'])
								? $fieldParams['FIELD_INFO'] : array()
						),
						'IS_EMPTY' => true
					];
				}
			}
			else
			{
				$result['VALUE'] = $value;
			}

			if($sourceEntityID > 0)
			{
				$result['SOURCE_ENTITY_IDS'] = array($sourceEntityID);
			}
		}
		else
		{
			$enabledIdsMap = null;
			if(isset($options['enabledIds']) && is_array($options['enabledIds']))
			{
				$enabledIdsMap = array_fill_keys($options['enabledIds'], true);
			}

			if($type === 'crm_multifield')
			{
				$sourceEntityIDs = array();
				$multiFieldMap = array();
				if((is_null($enabledIdsMap) || isset($enabledIdsMap[$targ['ID']])))
				{
					$targMultiFieldValues = isset($targ['FM']) && isset($targ['FM'][$fieldID]) ? $targ['FM'][$fieldID] : array();
					$multiFieldMap = self::prepareMultiFieldMap($fieldID, $targMultiFieldValues);
					if(!empty($multiFieldMap))
					{
						$sourceEntityIDs[] = (int)$targ['ID'];
					}
				}
				foreach($seeds as $seed)
				{
					if(!(is_null($enabledIdsMap) || isset($enabledIdsMap[$seed['ID']])))
					{
						continue;
					}

					$seedMultiFieldValues = isset($seed['FM']) && isset($seed['FM'][$fieldID]) ? $seed['FM'][$fieldID] : array();
					$seedMultiFieldMap = self::prepareMultiFieldMap($fieldID, $seedMultiFieldValues);
					foreach($seedMultiFieldMap as $multiFieldKey => $multiFieldValue)
					{
						if(!isset($multiFieldMap[$multiFieldKey]))
						{
							$multiFieldMap[$multiFieldKey] = $multiFieldValue;
							$sourceEntityIDs[] = (int)$seed['ID'];
						}
					}
				}
				$result['VALUE'] = array_values($multiFieldMap);
				$result['SOURCE_ENTITY_IDS'] = array_values(array_unique($sourceEntityIDs, SORT_NUMERIC));
			}
			else
			{
				$ownershipMap = array();
				if(isset($targ[$fieldID]) && is_array($targ[$fieldID]) && (is_null($enabledIdsMap) || isset($enabledIdsMap[$targ['ID']])))
				{
					foreach($targ[$fieldID] as $targValue)
					{
						$ownershipMap[$targValue] = (int)$targ['ID'];
					}
				}

				foreach($seeds as $seed)
				{
					if(isset($seed[$fieldID]) && is_array($seed[$fieldID]) && (is_null($enabledIdsMap) || isset($enabledIdsMap[$seed['ID']])))
					{
						foreach($seed[$fieldID] as $seedValue)
						{
							$ownershipMap[$seedValue] = (int)$seed['ID'];
						}
					}
				}

				if(!empty($ownershipMap))
				{
					$sourceEntityIDs = array_values(array_unique(array_values($ownershipMap), SORT_NUMERIC));
					$values = array_keys($ownershipMap);
					sort($values);

					if($type === 'user_field')
					{
						$result['VALUE'] = [
							'VALUE' => $values,
							'SIGNATURE' => Crm\UserField\UserFieldManager::prepareUserFieldSignature($fieldInfo, $values),
							'IS_EMPTY' => false
						];
					}
					else
					{
						$result['VALUE'] = $values;
					}
					$result['SOURCE_ENTITY_IDS'] = $sourceEntityIDs;
				}
				else if($type === 'user_field')
				{
					$result['VALUE'] = [
						'SIGNATURE' => Crm\UserField\UserFieldManager::prepareUserFieldSignature(
							isset($fieldParams['FIELD_INFO']) && is_array($fieldParams['FIELD_INFO'])
								? $fieldParams['FIELD_INFO'] : array()
						),
						'IS_EMPTY' => true
					];
				}
			}
		}
		return $result;
	}
	public function prepareEntityMergeData(array $seedIDs, $targID)
	{
		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		if ($this->isPermissionCheckEnabled() && !$this->isAdminUser())
		{
			$userPermissions = $this->getUserPermissions();
			foreach($seedIDs as $seedID)
			{
				if (!$this->checkEntityReadPermission($seedID, $userPermissions))
				{
					throw new EntityMergerException(
						$this->entityTypeID,
						$seedID,
						self::ROLE_SEED,
						EntityMergerException::READ_DENIED
					);
				}
			}
			if (!$this->checkEntityReadPermission($targID, $userPermissions))
			{
				throw new EntityMergerException(
					$this->entityTypeID,
					$targID,
					self::ROLE_TARG,
					EntityMergerException::READ_DENIED
				);
			}
		}

		$results = array();

		$entityFieldInfos = $this->getEntityFieldsInfo();
		$userFieldInfos = $this->getEntityUserFieldsInfo();
		$targ = $this->getEntityFields($targID, self::ROLE_TARG);

		$seeds = array();
		foreach($seedIDs as $seedID)
		{
			$seeds[$seedID] = $this->getEntityFields($seedID, self::ROLE_SEED);
		}

		foreach($entityFieldInfos as $fieldID => $fieldInfo)
		{
			if(!static::canMergeEntityField($fieldID))
			{
				continue;
			}

			// Skip READONLY and PROGRESS fields
			$fieldAttrs = isset($fieldInfo['ATTRIBUTES']) && is_array($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : array();
			if (in_array(\CCrmFieldInfoAttr::ReadOnly, $fieldAttrs, true)
				|| in_array(\CCrmFieldInfoAttr::Progress, $fieldAttrs, true)
				|| in_array(\CCrmFieldInfoAttr::Hidden, $fieldAttrs, true)
			)
			{
				continue;
			}

			$results[$fieldID] = $this->innerPrepareEntityFieldMergeData(
				$fieldID,
				array(
					'TYPE' => isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : 'string',
					'IS_MULTIPLE' => in_array(\CCrmFieldInfoAttr::Multiple, $fieldAttrs, true)
				),
				$seeds,
				$targ
			);
		}

		//region Multifields
		$targ['FM'] = $this->getEntityMultiFields($targ['ID'], self::ROLE_TARG);
		foreach($seedIDs as $seedID)
		{
			$seeds[$seedID]['FM'] = $this->getEntityMultiFields($seedID, self::ROLE_SEED);
		}

		$multiFieldTypeIDs = array_keys(\CCrmFieldMulti::GetEntityTypeInfos());
		foreach($multiFieldTypeIDs as $multiFieldTypeID)
		{
			$results[$multiFieldTypeID] = $this->innerPrepareEntityFieldMergeData(
				$multiFieldTypeID,
				array(
					'TYPE' => 'crm_multifield',
					'IS_MULTIPLE' => true
				),
				$seeds,
				$targ
			);
		}
		//endregion

		foreach($userFieldInfos as $fieldID => $fieldInfo)
		{
			$results[$fieldID] = $this->innerPrepareEntityFieldMergeData(
				$fieldID,
				array(
					'ENTITY_TYPE_ID' => 0,
					'ENTITY_ID' => 0,
					'TYPE' => 'user_field',
					'IS_MULTIPLE' => $fieldInfo['MULTIPLE'] === 'Y',
					'FIELD_INFO' => $fieldInfo
				),
				$seeds,
				$targ
			);
		}

		return $results;
	}
	public function prepareEntityFieldMergeData($fieldID, array $seedIDs, $targID, array $options = null)
	{
		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		$entityFieldInfos = $this->getEntityFieldsInfo();
		$userFieldInfos = $this->getEntityUserFieldsInfo();
		$targ = $this->getEntityFields($targID, self::ROLE_TARG);

		$seeds = array();
		foreach($seedIDs as $seedID)
		{
			$seeds[$seedID] = $this->getEntityFields($seedID, self::ROLE_SEED);
		}

		if(isset($entityFieldInfos[$fieldID]))
		{
			$fieldInfo = $entityFieldInfos[$fieldID];
			if(!static::canMergeEntityField($fieldID))
			{
				return array();
			}

			// Skip READONLY and PROGRESS fields
			if(isset($fieldInfo['ATTRIBUTES']) && is_array($fieldInfo['ATTRIBUTES']))
			{
				if (in_array(\CCrmFieldInfoAttr::ReadOnly, $fieldInfo['ATTRIBUTES'], true)
					|| in_array(\CCrmFieldInfoAttr::Progress, $fieldInfo['ATTRIBUTES'], true)
					|| in_array(\CCrmFieldInfoAttr::Hidden, $fieldInfo['ATTRIBUTES'], true)
				)
				{
					return array();
				}
			}

			return $this->innerPrepareEntityFieldMergeData(
				$fieldID,
				array(
					'TYPE' => $fieldInfo['TYPE'] ?? 'string',
					'IS_MULTIPLE' => in_array(\CCrmFieldInfoAttr::Multiple, $fieldInfo['ATTRIBUTES'], true),
				),
				$seeds,
				$targ,
				$options
			);
		}

		//region Multifields
		$multiFieldTypes = \CCrmFieldMulti::GetEntityTypeInfos();
		if(isset($multiFieldTypes[$fieldID]))
		{
			$targ['FM'] = $this->getEntityMultiFields($targ['ID'], self::ROLE_TARG);
			foreach($seedIDs as $seedID)
			{
				$seeds[$seedID]['FM'] = $this->getEntityMultiFields($seedID, self::ROLE_SEED);
			}

			return $this->innerPrepareEntityFieldMergeData(
				$fieldID,
				array(
					'TYPE' => 'crm_multifield',
					'IS_MULTIPLE' => true
				),
				$seeds,
				$targ,
				$options
			);
		}
		//endregion

		if(isset($userFieldInfos[$fieldID]))
		{
			$fieldInfo = $userFieldInfos[$fieldID];
			return $this->innerPrepareEntityFieldMergeData(
				$fieldID,
				array(
					'ENTITY_TYPE_ID' => 0,
					'ENTITY_ID' => 0,
					'TYPE' => 'user_field',
					'IS_MULTIPLE' => $fieldInfo['MULTIPLE'] === 'Y',
					'FIELD_INFO' => $fieldInfo
				),
				$seeds,
				$targ,
				$options
			);
		}



		return array();
	}


	/**
	 * Map entity to custom type.
	 * Type of the source entity is determined by this instance.
	 * Type of the destination entity is determined by param "destinationEntityTypeID".
	 * @param int $sourceEntityID Source Entity ID.
	 * @param int $destinationEntityTypeID Destination Entity ID.
	 * @return array|null
	 * @throws Main\NotImplementedException
	 */
	protected function mapEntity($sourceEntityID, $destinationEntityTypeID)
	{
		throw new Main\NotImplementedException('Method mapEntity must be overridden');
	}

	/**
	 * Enrich destination entity with source entity.
	 * Source and Destination entities may have different types.
	 * Type of the source entity is determined by Source Instance.
	 * Type of the destination entity is determined by this instance.
	 * @param EntityMerger $sourceMerger Source Instance.
	 * @param int $sourceID Source Entity ID.
	 * @param int $destinationID Destination Entity ID.
	 * @throws Main\NotImplementedException
	 */
	public function enrich($sourceMerger, $sourceID, $destinationID)
	{
		$sourceFields = $sourceMerger->mapEntity($sourceID, $this->getEntityTypeID());
		if(!is_array($sourceFields))
		{
			return;
		}

		$destinationFields = $this->getEntityFields($destinationID, self::ROLE_TARG);
		$destinationFields['FM'] = $this->getEntityMultiFields($destinationID, self::ROLE_TARG);

		$this->mergeFields($sourceFields, $destinationFields, false);
		$this->updateEntity(
			$destinationID,
			$destinationFields,
			self::ROLE_TARG,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'EXCLUDE_FROM_RELATION_REGISTRATION' => [
					new Crm\ItemIdentifier((int)$sourceMerger->getEntityTypeID(), (int)$sourceID),
				],
			]
		);
	}

	/**
	 * Register mismatch in duplicate index.
	 * @param Integrity\DuplicateCriterion $criterion Creterion.
	 * @param int $leftEntityID Left entity ID.
	 * @param int $rightEntityID Right entity ID.
	 * @return void
	 * @throws Main\ArgumentException
	 */
	public function registerCriterionMismatch(Integrity\DuplicateCriterion $criterion, $leftEntityID, $rightEntityID)
	{
		$entityTypeID = $this->entityTypeID;
		$userID = $this->userID;
		$typeID = $criterion->getIndexTypeID();
		$matchHash = $criterion->getMatchHash();
		if($matchHash === '')
		{
			throw new Main\ArgumentException('Match hash is empty', 'criterion');
		}
		$scope = $criterion->getScope();

		Integrity\DuplicateIndexMismatch::register(
			$entityTypeID, $leftEntityID, $rightEntityID, $typeID, $matchHash, $userID, $scope
		);
	}

	protected function mergeEntityFieldsBatch(array &$seeds, array &$targ, array &$fieldInfos, $skipEmpty = false, array $options = null)
	{
		if(empty($seeds))
		{
			return;
		}

		if($options === null)
		{
			$options = array();
		}

		$conflictResolutionMode = isset($options['conflictResolutionMode'])
			? (int)$options['conflictResolutionMode'] : ConflictResolutionMode::UNDEFINED;
		if(!ConflictResolutionMode::isDefined($conflictResolutionMode))
		{
			$conflictResolutionMode = self::getDefaultConflictResolutionMode();
		}

		if($conflictResolutionMode === ConflictResolutionMode::ALWAYS_OVERWRITE)
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Undefined,
				0,
				self::ROLE_UNDEFINED,
				EntityMergerException::CONFLICT_RESOLUTION_NOT_SUPPORTED,
				'',
				0,
				null,
				array('conflictResolutionMode' => $conflictResolutionMode)
			);
		}

		if($conflictResolutionMode === ConflictResolutionMode::MANUAL)
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Undefined,
				0,
				self::ROLE_UNDEFINED,
				EntityMergerException::CONFLICT_OCCURRED,
				'',
				0,
				null,
				array('conflictResolutionMode' => $conflictResolutionMode)
			);
		}

		$seedMap = array();
		foreach($seeds as $seed)
		{
			static::checkEntityMergePreconditions($seed, $targ);
			$seedMap[$seed['ID']] = $seed;
		}

		$map = null;
		if(isset($options['map']) && is_array($options['map']))
		{
			$map = $options['map'];
		}
		$enableMap = $map !== null && !empty($map);

		$historyItems = [];
		foreach($fieldInfos as $fieldID => $fieldInfo)
		{
			if(!static::canMergeEntityField($fieldID))
			{
				continue;
			}

			// Skip READONLY and PROGRESS fields
			if(isset($fieldInfo['ATTRIBUTES']) && is_array($fieldInfo['ATTRIBUTES']))
			{
				if (in_array(\CCrmFieldInfoAttr::ReadOnly, $fieldInfo['ATTRIBUTES'], true)
					|| in_array(\CCrmFieldInfoAttr::Progress, $fieldInfo['ATTRIBUTES'], true)
					|| in_array(\CCrmFieldInfoAttr::Hidden, $fieldInfo['ATTRIBUTES'], true)
				)
				{
					continue;
				}
			}

			if($enableMap)
			{
				foreach($seeds as $seed)
				{
					$seedID = $seed['ID'];
					if(isset($map[$fieldID]) && is_array($map[$fieldID]))
					{
						$sourceIDs = isset($map[$fieldID]['SOURCE_ENTITY_IDS']) && is_array($map[$fieldID]['SOURCE_ENTITY_IDS'])
							? $map[$fieldID]['SOURCE_ENTITY_IDS'] : array();
						if(in_array($seedID, $sourceIDs))
						{
							//\CCrmFieldInfoAttr::Multiple
							static::applyMappedValue($fieldID, $seed, $targ);
							break;
						}
					}
				}
				continue;
			}

			$targFlg = static::doesFieldHaveValue($fieldInfo, $targ, $fieldID, $skipEmpty);

			$seedValueMap = array();
			foreach($seedMap as $seedID => $seed)
			{
				$seedFlg = static::doesFieldHaveValue($fieldInfo, $seed, $fieldID, $skipEmpty);

				if($seedFlg)
				{
					$seedValueMap[$seedID] = $seed[$fieldID];
				}
			}

			if(empty($seedValueMap))
			{
				continue;
			}

			if($conflictResolutionMode === ConflictResolutionMode::ASK_USER && ($targFlg || count($seedValueMap) > 1))
			{
				$currentSeedIDs = array_keys($seedValueMap);
				$currentTarg = $targFlg ? $targ : $seedMap[array_shift($currentSeedIDs)];

				$fieldConflictResolver = $this->getFieldConflictResolver($fieldID, $fieldInfo['TYPE'] ?? 'string');
				$fieldConflictResolver->setTarget($currentTarg);

				foreach($currentSeedIDs as $seedID)
				{
					$fieldConflictResolver->addSeed((int)$seedID, $seedMap[$seedID]);
				}
				$resolveResult = $fieldConflictResolver->resolve();
				if(!$resolveResult->isSuccess())
				{
					throw new EntityMergerException(
						\CCrmOwnerType::Undefined,
						0,
						self::ROLE_UNDEFINED,
						EntityMergerException::CONFLICT_OCCURRED
					);
				}
				$targFlg = $targFlg || $resolveResult->isTargetChanged();
				$resolveResult->updateTarget($targ);

				foreach($currentSeedIDs as $seedID)
				{
					$resolveResult->updateSeed((int)$seedID, $seedMap[$seedID]);
				}
				$historyItems = array_merge($historyItems, $resolveResult->getHistoryItems());
			}

			// Skip if target entity field is defined
			// Skip if seed entity field is not defined
			if(!$targFlg)
			{
				$targ[$fieldID] = $seedValueMap[array_keys($seedValueMap)[0]];
			}
		}
		if (!empty($historyItems))
		{
			$targ['HISTORY_ITEMS'] = $historyItems;
		}
	}

	protected static function applyMappedValue(string $fieldID, array &$seed, array &$targ)
	{
		$targ[$fieldID] = $seed[$fieldID];
	}

	/**
	 * Merge entity fields.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @param array &$fieldInfos Entity field infos.
	 * @param bool $skipEmpty Skip empty fields flag.
	 * @param array $options Operation options.
	 * @return void
	 */
	protected function mergeEntityFields(array &$seed, array &$targ, array &$fieldInfos, $skipEmpty = false, array $options = null)
	{
		if(empty($seed))
		{
			return;
		}

		if($options === null)
		{
			$options = array();
		}

		$conflictResolutionMode = isset($options['conflictResolutionMode'])
			? (int)$options['conflictResolutionMode'] : ConflictResolutionMode::UNDEFINED;
		if(!ConflictResolutionMode::isDefined($conflictResolutionMode))
		{
			$conflictResolutionMode = self::getDefaultConflictResolutionMode();
		}

		if($conflictResolutionMode === ConflictResolutionMode::ALWAYS_OVERWRITE)
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Undefined,
				0,
				self::ROLE_UNDEFINED,
				EntityMergerException::CONFLICT_RESOLUTION_NOT_SUPPORTED,
				'',
				0,
				null,
				array('conflictResolutionMode' => $conflictResolutionMode)
			);
		}

		$historyItems = [];
		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			if(!static::canMergeEntityField($fieldID))
			{
				continue;
			}

			// Skip READONLY and PROGRESS fields
			if(isset($fieldInfo['ATTRIBUTES']) && is_array($fieldInfo['ATTRIBUTES']))
			{
				if (in_array(\CCrmFieldInfoAttr::ReadOnly, $fieldInfo['ATTRIBUTES'], true)
					|| in_array(\CCrmFieldInfoAttr::Progress, $fieldInfo['ATTRIBUTES'], true)
					|| in_array(\CCrmFieldInfoAttr::Hidden, $fieldInfo['ATTRIBUTES'], true)
				)
				{
					continue;
				}
			}

			$targFlg = static::doesFieldHaveValue($fieldInfo, $targ, $fieldID, $skipEmpty);
			$seedFlg = static::doesFieldHaveValue($fieldInfo, $seed, $fieldID, $skipEmpty);
			$type = ($fieldInfo['TYPE'] ?? 'string');

			$fieldConflictResolver = $this->getFieldConflictResolver($fieldID, $type);
			$fieldConflictResolver->addSeed((int)($seed['ID'] ?? 0), $seed);
			$fieldConflictResolver->setTarget($targ);
			if($targFlg
				&& $seedFlg
				&& $conflictResolutionMode === ConflictResolutionMode::ASK_USER
			)
			{
				$resolveResult = $fieldConflictResolver->resolve();
				if (!$resolveResult->isSuccess())
				{
					throw new EntityMergerException(
						\CCrmOwnerType::Undefined,
						0,
						self::ROLE_UNDEFINED,
						EntityMergerException::CONFLICT_OCCURRED
					);
				}
				$resolveResult->updateSeed((int)$seed['ID'], $seed);
				$resolveResult->updateTarget($targ);
				$historyItems = array_merge($historyItems, $resolveResult->getHistoryItems());

				$targFlg = $targFlg || $resolveResult->isTargetChanged();
			}

			// Skip if target entity field is defined
			// Skip if seed entity field is not defined
			if(!$targFlg && $seedFlg)
			{
				$targ[$fieldID] = $seed[$fieldID];
			}
		}
		if (!empty($historyItems))
		{
			$targ['HISTORY_ITEMS'] = $historyItems;
		}
	}

	/**
	 * Check is field empty or not.
	 * @param array $fieldInfo
	 * @param array $fields
	 * @param string $fieldId
	 * @param bool $skipEmpty
	 * @return bool
	 */
	final protected static function doesFieldHaveValue(
		array $fieldInfo,
		array $fields,
		string $fieldId,
		bool $skipEmpty
	): bool
	{
		$hasValue = isset($fields[$fieldId]);

		if(!$skipEmpty)
		{
			$type = ($fieldInfo['TYPE'] ?? 'string');
			$fieldValue = $fields[$fieldId] ?? '';

			if (!$hasValue = ($hasValue && static::isFieldNotEmpty($fieldInfo, $fields, $fieldId)))
			{
				return $hasValue;
			}

			if (in_array($type, ['string', 'char', 'datetime', 'crm_status', 'crm_currency'], true))
			{
				$hasValue = ($hasValue && $fieldValue !== '');
			}
			elseif ($type === 'double')
			{
				$hasValue = ($hasValue && (float)$fieldValue !== 0.0);
			}
			elseif ($type === 'integer' || $type === 'user')
			{
				$hasValue = ($hasValue && (int)$fieldValue !== 0);
			}
		}

		return $hasValue;
	}

	/**
	 * Additional verification rules for inherited classes
	 * @param array $fieldInfo
	 * @param array $fields
	 * @param string $fieldId
	 * @return bool
	 */
	protected static function isFieldNotEmpty(array $fieldInfo, array $fields, string $fieldId): bool
	{
		return true;
	}

	/** Check if source and target entities can be merged
	 * @param array $seed Source entity fields
	 * @param array $targ Target entity fields
	 */
	protected static function checkEntityMergePreconditions(array $seed, array $targ)
	{
	}

	protected static function canMergeEntityField($fieldID)
	{
		// Skip PK
		if($fieldID === 'ID')
		{
			return false;
		}
		return true;
	}

	/**
	 * Merge entity fields. May be overridden by descendants.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @param array $fieldInfos Metadata fields.
	 * @param bool $skipEmpty Skip empty fields flag. If is enabled then empty fields of "seed" will not be replaced by fields from "targ"
	 * @param array $options Options array.
	 */
	protected function innerMergeEntityFields(array &$seed, array &$targ, array &$fieldInfos, $skipEmpty = false, array $options = array())
	{
		$this->mergeEntityFields($seed, $targ, $fieldInfos, $skipEmpty, $options);
		$this->innerMergeBoundEntities($seed, $targ, $skipEmpty, $options);
	}

	protected function innerMergeBoundEntities(array &$seed, array &$targ, $skipEmpty = false, array $options = array())
	{
		$seeds = array(&$seed);
		$this->mergeBoundEntitiesBatch($seeds, $targ, $skipEmpty, $options);
	}

	protected function mergeBoundEntitiesBatch(array &$seeds, array &$targ, $skipEmpty = false, array $options = [])
	{
		$targID = (int)($targ['ID'] ?? 0);
		$targMultiFields = $targID > 0 ? $this->getEntityMultiFields($targID, self::ROLE_TARG) : [];

		$seedMap = [];
		foreach($seeds as $seed)
		{
			$seedMap[$seed['ID'] ?? 0] = $seed;
		}

		$map = null;
		if(isset($options['map']) && is_array($options['map']))
		{
			$map = $options['map'];
		}

		$seedIDs = array_keys($seedMap);
		$effectiveSeedIDs = $seedIDs;
		if($map !== null && !empty($map))
		{
			$effectiveSeedIDs = array();
			foreach(array_keys(\CCrmFieldMulti::GetEntityTypeInfos()) as $typeID)
			{
				if(isset($map[$typeID]) && is_array($map[$typeID]))
				{
					$sourceIDs = isset($map[$typeID]['SOURCE_ENTITY_IDS']) && is_array($map[$typeID]['SOURCE_ENTITY_IDS'])
						? $map[$typeID]['SOURCE_ENTITY_IDS'] : array();
					$effectiveSeedIDs = array_merge(
						$effectiveSeedIDs,
						array_diff(array_intersect($sourceIDs, $seedIDs), $effectiveSeedIDs)
					);
				}
			}
		}

		$seedMultiFields = array();
		foreach($effectiveSeedIDs as $seedID)
		{
			if($seedID <= 0)
			{
				continue;
			}

			$multiFields = $this->getEntityMultiFields($seedID, self::ROLE_SEED);
			if(!empty($multiFields))
			{
				$seedMultiFields[$seedID] = $multiFields;
			}
		}

		//TODO: Rename SKIP_MULTIPLE_USER_FIELDS -> ENABLE_MULTIPLE_FIELDS_ENRICHMENT
		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];
		if(!empty($seedMultiFields) && (!$skipMultipleFields || (!$skipEmpty && empty($targMultiFields))))
		{
			$options['targID'] = $targID;
			foreach($seedMultiFields as $seedID => $multiFields)
			{
				$options['seedID'] = $seedID;
				self::mergeMultiFields($multiFields, $targMultiFields, false, $options);
			}
			if(!empty($targMultiFields))
			{
				$targ['FM'] = $targMultiFields;
			}
		}
	}

	/**
	 * Get field conflicts resolver.
	 * @param string $fieldId
	 * @param string $type
	 * @return ConflictResolver\Base
	 */
	protected function getFieldConflictResolver(string $fieldId, string $type): ConflictResolver\Base
	{
		if ($type === 'string')
		{
			return new Crm\Merger\ConflictResolver\StringField($fieldId);
		}
		return new ConflictResolver\Base($fieldId);
	}

	protected static function getUserDefinedConflictResolver(int $entityTypeId, string $fieldId, string $type)
	{
		$event = new Main\Event(
			'crm',
			'onGetFieldConflictResolver',
			[
			'entityTypeId' => $entityTypeId,
			'fieldId' => $fieldId,
			'type' =>$type
			]
		);
		$event->send();
		/** @var @var \Bitrix\Main\EventResult $eventResult */
		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === Main\EventResult::SUCCESS)
			{
				$parameters = $eventResult->getParameters();
				if (
					is_array($parameters)
					&& isset($parameters['conflictResolver'])
					&& ($parameters['conflictResolver'] instanceof ConflictResolver\Base)
				)
				{
					return $parameters['conflictResolver'];
				}
			}
		}

		return null;
	}

	/**
	 * Merge user fields.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @param array &$fieldInfos Entity field infos.
	 * @return void
	 */
	protected function mergeUserFields(array &$seed, array &$targ, array &$fieldInfos, array $options = array())
	{
		if(empty($seed))
		{
			return;
		}

		$conflictResolutionMode = isset($options['conflictResolutionMode'])
			? (int)$options['conflictResolutionMode'] : ConflictResolutionMode::UNDEFINED;
		if(!ConflictResolutionMode::isDefined($conflictResolutionMode))
		{
			$conflictResolutionMode = self::getDefaultConflictResolutionMode();
		}

		if($conflictResolutionMode === ConflictResolutionMode::ALWAYS_OVERWRITE)
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Undefined,
				0,
				self::ROLE_UNDEFINED,
				EntityMergerException::CONFLICT_RESOLUTION_NOT_SUPPORTED,
				'',
				0,
				null,
				array('conflictResolutionMode' => $conflictResolutionMode)
			);
		}

		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];
		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			$isMultiple = $fieldInfo['MULTIPLE'] === 'Y';
			$typeID = $fieldInfo['USER_TYPE_ID'];

			if (
				!$isMultiple &&
				$typeID == 'boolean' &&
				isset($targ[$fieldID]) &&
				isset($seed[$fieldID]) &&
				(in_array($targ[$fieldID], [false, 0, '0', 'N', ''], true))
			)
			{
				unset($targ[$fieldID]);
			}

			if(!$isMultiple
				&& isset($targ[$fieldID])
				&& isset($seed[$fieldID])
				&& $conflictResolutionMode === ConflictResolutionMode::ASK_USER
			)
			{
				$fieldConflictResolver = $this->getFieldConflictResolver($fieldID, $typeID);
				$fieldConflictResolver->setTarget($targ);
				$fieldConflictResolver->addSeed((int)$seed['ID'], $seed);
				$resolveResult = $fieldConflictResolver->resolve();
				if(!$resolveResult->isSuccess())
				{
					throw new EntityMergerException(
						\CCrmOwnerType::Undefined,
						0,
						self::ROLE_UNDEFINED,
						EntityMergerException::CONFLICT_OCCURRED
					);
				}
				$resolveResult->updateTarget($targ);
				$resolveResult->updateSeed((int)$seed['ID'], $seed);
			}

			if($typeID === 'file')
			{
				$fileOptions = array('ENABLE_ID' => true,);
				if(isset($options['ENABLE_UPLOAD']))
				{
					$fileOptions['ENABLE_UPLOAD'] = $options['ENABLE_UPLOAD'];
				}
				if(isset($options['ENABLE_UPLOAD_CHECK']))
				{
					$fileOptions['ENABLE_UPLOAD_CHECK'] = $options['ENABLE_UPLOAD_CHECK'];
				}

				if(!$isMultiple)
				{
					if(!isset($targ[$fieldID]) && isset($seed[$fieldID]))
					{
						$file = null;
						if(\CCrmFileProxy::TryResolveFile($seed[$fieldID], $file, $fileOptions))
						{
							$targ[$fieldID] = $file;
						}
					}
					elseif(isset($targ[$fieldID]))
					{
						//HACK: Convert file ID to file information for preventing error during UF check.
						$file = null;
						if(\CCrmFileProxy::TryResolveFile($targ[$fieldID], $file, $fileOptions))
						{
							$targ[$fieldID] = $file;
						}
					}
				}
				else
				{
					if(!$skipMultipleFields)
					{
						if(isset($seed[$fieldID]) && is_array($seed[$fieldID]))
						{
							$previousFileIDs = array();
							if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
							{
								foreach($targ[$fieldID] as $data)
								{
									if(is_array($data))
									{
										$fileID = isset($data['ID']) ? (int)$data['ID'] : 0;
										if($fileID > 0)
										{
											$previousFileIDs[] = $fileID;
										}
									}
									else
									{
										$previousFileIDs[] = (int)$data;
									}
								}
							}

							$targ[$fieldID] = array();
							if(!empty($previousFileIDs))
							{
								foreach($previousFileIDs as $data)
								{
									if(is_array($data))
									{
										$targ[$fieldID][] = $data;
									}
									elseif(is_numeric($data) && $data > 0)
									{
										$file = null;
										if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
										{
											$targ[$fieldID][] = $file;
										}
									}
								}
							}

							foreach($seed[$fieldID] as $data)
							{
								if(is_array($data))
								{
									$fileID = isset($data['ID']) ? $data['ID'] : 0;

									//Check if already added from previous values
									if(array_search($fileID, $previousFileIDs, true) !== false)
									{
										continue;
									}

									$targ[$fieldID][] = $data;
								}
								else
								{
									$fileID = (int)$data;

									//Check if already added from previous values
									if(array_search($fileID, $previousFileIDs, true) !== false)
									{
										continue;
									}

									$file = null;
									if(\CCrmFileProxy::TryResolveFile($fileID, $file, $fileOptions))
									{
										$targ[$fieldID][] = $file;
									}
								}
							}
						}
						elseif(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							//HACK: Convert file IDs to file info for preventing error during UF check.
							$fileIDs = $targ[$fieldID];
							$targ[$fieldID] = array();
							if(!empty($fileIDs))
							{
								foreach($fileIDs as $data)
								{
									if(is_array($data))
									{
										$targ[$fieldID][] = $data;
									}
									elseif(is_numeric($data) && $data > 0)
									{
										$file = null;
										if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
										{
											$targ[$fieldID][] = $file;
										}
									}
								}
							}
						}
					}
					else
					{
						$fileIDs = null;
						if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$fileIDs = $targ[$fieldID];
						}
						elseif(isset($seed[$fieldID]) && is_array($seed[$fieldID]))
						{
							$fileIDs = $seed[$fieldID];
						}

						//HACK: Convert file IDs to file info for preventing error during UF check.
						$targ[$fieldID] = array();
						if(!empty($fileIDs))
						{
							foreach($fileIDs as $data)
							{
								if(is_array($data))
								{
									$targ[$fieldID][] = $data;
								}
								elseif(is_numeric($data) && $data > 0)
								{
									$file = null;
									if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
									{
										$targ[$fieldID][] = $file;
									}
								}
							}
						}
					}
				}
			}
			elseif ($typeID === 'address') // sorry
			{
				if (!$isMultiple)
				{
					if (!isset($targ[$fieldID]) && isset($seed[$fieldID]))
					{
						$targ[$fieldID] = static::getAddressFields($seed[$fieldID]);
					}
					elseif (isset($targ[$fieldID]))
					{
						$targ[$fieldID] = static::getAddressFields($targ[$fieldID]);
					}
				}
				else
				{
					if (!$skipMultipleFields)
					{
						if(isset($seed[$fieldID]) && is_array($seed[$fieldID]))
						{
							$previousAddresses = [];
							if (isset($targ[$fieldID]) && is_array($targ[$fieldID]))
							{
								foreach($targ[$fieldID] as $data)
								{
									$previousAddresses[] = $data;
								}
							}

							$targ[$fieldID] = [];
							if (!empty($previousAddresses))
							{
								foreach ($previousAddresses as $address)
								{
									$targ[$fieldID][] = static::getAddressFields($address);
								}
							}

							foreach ($seed[$fieldID] as $data)
							{
								if(in_array($data, $previousAddresses, true))
								{
									continue;
								}

								$targ[$fieldID][] = static::getAddressFields($data);
							}
						}
						elseif (isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$addresses = $targ[$fieldID];
							$targ[$fieldID] = array();
							if(!empty($addresses))
							{
								foreach($addresses as $address)
								{
									$targ[$fieldID][] = static::getAddressFields($address);
								}
							}
						}
					}
					else
					{
						$addresses = null;
						if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$addresses = $targ[$fieldID];
						}
						elseif(isset($seed[$fieldID]) && is_array($seed[$fieldID]))
						{
							$addresses = $seed[$fieldID];
						}

						$targ[$fieldID] = array();
						if(!empty($addresses))
						{
							foreach($addresses as $address)
							{
								$targ[$fieldID][] = static::getAddressFields($address);
							}
						}
					}
				}
			}
			else
			{
				if(!$isMultiple && !isset($targ[$fieldID]) && isset($seed[$fieldID]))
				{
					$targ[$fieldID] = $seed[$fieldID];
				}
				elseif($isMultiple && isset($seed[$fieldID]) && is_array($seed[$fieldID]))
				{
					if(!$skipMultipleFields)
					{
						if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$targ[$fieldID] = array_merge(
								$targ[$fieldID],
								array_diff($seed[$fieldID], $targ[$fieldID])
							);
						}
						else
						{
							$targ[$fieldID] = $seed[$fieldID];
						}
					}
					else if(!isset($targ[$fieldID]))
					{
						$targ[$fieldID] = $seed[$fieldID];
					}
				}
			}
		}
		unset($fieldInfo);
	}

	protected static function prepareFileInfos($fileData)
	{
		$fileOptions = array('ENABLE_ID' => true);
		$results = array();
		foreach($fileData as $fileItem)
		{
			if(is_array($fileItem))
			{
				$results[] = $fileItem;
			}
			elseif(is_numeric($fileItem))
			{
				if(\CCrmFileProxy::TryResolveFile($fileItem, $file, $fileOptions))
				{
					$results[] = $file;
				}
			}
		}
		return $results;
	}

	protected static function getAddressFields($value)
	{
		if (!Main\Loader::includeModule('fileman') || !$value)
		{
			return null;
		}

		// the value has been cleared;
		// we have to return an empty value so that the original entity's value stays intact
		$isDelete = (is_string($value) && mb_strlen($value) > 4 && mb_substr($value, -4) === '_del');
		if ($isDelete)
		{
			return null;
		}

		$addressFields = AddressType::getAddressFieldsByValue($value);
		if (!$addressFields)
		{
			return null;
		}

		unset($addressFields['id']);

		try
		{
			$addressFields = Main\Web\Json::encode($addressFields, 0);
		}
		catch (Main\ArgumentException $exception)
		{
			return null;
		}

		return $addressFields;
	}

	protected function mergeUserFieldsBatch(array &$seeds, array &$targ, array &$fieldInfos, array $options = array())
	{
		if(empty($seeds))
		{
			return;
		}

		$conflictResolutionMode = isset($options['conflictResolutionMode'])
			? (int)$options['conflictResolutionMode'] : ConflictResolutionMode::UNDEFINED;
		if(!ConflictResolutionMode::isDefined($conflictResolutionMode))
		{
			$conflictResolutionMode = self::getDefaultConflictResolutionMode();
		}

		if($conflictResolutionMode === ConflictResolutionMode::ALWAYS_OVERWRITE)
		{
			throw new EntityMergerException(
				\CCrmOwnerType::Undefined,
				0,
				self::ROLE_UNDEFINED,
				EntityMergerException::CONFLICT_RESOLUTION_NOT_SUPPORTED,
				'',
				0,
				null,
				array('conflictResolutionMode' => $conflictResolutionMode)
			);
		}

		$seedMap = array();
		foreach($seeds as $seed)
		{
			$seedMap[$seed['ID']] = $seed;
		}

		$map = null;
		if(isset($options['map']) && is_array($options['map']))
		{
			$map = $options['map'];
		}
		$enableMap = $map !== null && !empty($map);

		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];
		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			$isMultiple = $fieldInfo['MULTIPLE'] === 'Y';
			$typeID = $fieldInfo['USER_TYPE_ID'];

			$sourceIDs = null;
			if($enableMap && isset($map[$fieldID]) && is_array($map[$fieldID]))
			{
				$sourceIDs = isset($map[$fieldID]['SOURCE_ENTITY_IDS']) && is_array($map[$fieldID]['SOURCE_ENTITY_IDS'])
					? $map[$fieldID]['SOURCE_ENTITY_IDS'] : array();
			}

			//region Seed Values
			$seedValueMap = array();
			foreach($seedMap as $seedID => $seed)
			{
				if($enableMap && $sourceIDs !== null && !in_array($seedID, $sourceIDs))
				{
					continue;
				}

				if(isset($seed[$fieldID]))
				{
					$seedValueMap[$seedID] = $seed[$fieldID];
				}
			}

			$seedCount = count($seedValueMap) ;
			if(!$isMultiple && $conflictResolutionMode === ConflictResolutionMode::ASK_USER && ($seedCount > 1 || (isset($targ[$fieldID]) && $seedCount > 0)))
			{
				$currentSeedIDs = array_keys($seedValueMap);
				$currentTarg = isset($targ[$fieldID]) ? $targ : $seedMap[array_shift($currentSeedIDs)];

				$fieldConflictResolver = $this->getFieldConflictResolver($fieldID, $typeID);
				$fieldConflictResolver->setTarget($currentTarg);
				foreach($currentSeedIDs as $seedID)
				{
					if($sourceIDs === null || !in_array($seedID, $sourceIDs))
					{
						$fieldConflictResolver->addSeed((int)$seedID, $seedMap[$seedID]);
					}
				}
				$resolveResult = $fieldConflictResolver->resolve();
				if(!$resolveResult->isSuccess())
				{
					throw new EntityMergerException(
						\CCrmOwnerType::Undefined,
						0,
						self::ROLE_UNDEFINED,
						EntityMergerException::CONFLICT_OCCURRED
					);
				}
				$resolveResult->updateTarget($targ);
				foreach($currentSeedIDs as $seedID)
				{
					if($sourceIDs === null || !in_array($seedID, $sourceIDs))
					{
						$resolveResult->updateSeed((int)$seedID, $seedMap[$seedID]);
					}
				}
			}

			if(!$isMultiple)
			{
				$seedValues = array_values($seedValueMap);
			}
			else
			{
				$seedValues = array();
				foreach($seedValueMap as $seedValue)
				{
					if(!is_array($seedValue))
					{
						$seedValues[] = $seedValue;
					}
					else
					{
						$seedValues = array_merge($seedValues, array_diff($seedValue, $seedValues));
					}
				}
			}
			//endregion

			if($enableMap)
			{
				if($sourceIDs === null)
				{
					continue;
				}

				//Remove multiple target field if it not defined in map
				if($isMultiple && !in_array($targ['ID'], $sourceIDs))
				{
					unset($targ[$fieldID]);
				}

				if(!empty($seedValues))
				{
					if($isMultiple)
					{
						if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$diffValues = array_diff($seedValues, $targ[$fieldID]);
							if($typeID === 'file')
							{
								$diffValues = self::prepareFileInfos($diffValues);
								$targ[$fieldID] = self::prepareFileInfos($targ[$fieldID]);
							}
							$targ[$fieldID] = array_merge($targ[$fieldID], $diffValues);
						}
						else
						{
							$targ[$fieldID] = $typeID === 'file'
								? self::prepareFileInfos($seedValues) : $seedValues;
						}
					}
					else
					{
						if ($typeID === 'file')
						{
							$fileInfos = self::prepareFileInfos($seedValues);
							$targ[$fieldID] = $fileInfos[0];
						}
						else
						{
							$targ[$fieldID] = $seedValues[0];
						}
					}
				}
				elseif (
					empty($seedValues) &&
					$typeID === 'file'
				)
				{
					// file field value should be empty if it was not changed
					// in other case value will be deleted in \Bitrix\Main\UserField\Types\FileType::onBeforeSave()
					unset($targ[$fieldID]);
				}

				continue;
			}

			if($typeID === 'file')
			{
				$fileOptions = array('ENABLE_ID' => true);
				if(isset($options['ENABLE_UPLOAD']))
				{
					$fileOptions['ENABLE_UPLOAD'] = $options['ENABLE_UPLOAD'];
				}
				if(isset($options['ENABLE_UPLOAD_CHECK']))
				{
					$fileOptions['ENABLE_UPLOAD_CHECK'] = $options['ENABLE_UPLOAD_CHECK'];
				}

				if(!$isMultiple)
				{
					if(!isset($targ[$fieldID]) && !empty($seedValues))
					{
						if($seedValues[0] > 0 && \CCrmFileProxy::TryResolveFile($seedValues[0], $file, $fileOptions))
						{
							$targ[$fieldID] = $file;
						}
					}
				}
				else
				{
					if(!$skipMultipleFields)
					{
						if(!empty($seedValues))
						{
							$previousFileIDs = array();
							if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
							{
								foreach($targ[$fieldID] as $data)
								{
									if(is_array($data))
									{
										$fileID = isset($data['ID']) ? (int)$data['ID'] : 0;
										if($fileID > 0)
										{
											$previousFileIDs[] = $fileID;
										}
									}
									else
									{
										$previousFileIDs[] = (int)$data;
									}
								}
							}

							$targ[$fieldID] = array();
							if(!empty($previousFileIDs))
							{
								foreach($previousFileIDs as $data)
								{
									if(is_array($data))
									{
										$targ[$fieldID][] = $data;
									}
									elseif(is_numeric($data) && $data > 0)
									{
										$file = null;
										if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
										{
											$targ[$fieldID][] = $file;
										}
									}
								}
							}

							foreach($seedValues as $data)
							{
								if(is_array($data))
								{
									$fileID = isset($data['ID']) ? $data['ID'] : 0;

									//Check if already added from previous values
									if(array_search($fileID, $previousFileIDs, true) !== false)
									{
										continue;
									}

									$targ[$fieldID][] = $data;
								}
								else
								{
									$fileID = (int)$data;

									//Check if already added from previous values
									if(array_search($fileID, $previousFileIDs, true) !== false)
									{
										continue;
									}

									$file = null;
									if(\CCrmFileProxy::TryResolveFile($fileID, $file, $fileOptions))
									{
										$targ[$fieldID][] = $file;
									}
								}
							}
						}
						elseif(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							//HACK: Convert file IDs to file info for preventing error during UF check.
							$fileIDs = $targ[$fieldID];
							$targ[$fieldID] = array();
							if(!empty($fileIDs))
							{
								foreach($fileIDs as $data)
								{
									if(is_array($data))
									{
										$targ[$fieldID][] = $data;
									}
									elseif(is_numeric($data) && $data > 0)
									{
										$file = null;
										if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
										{
											$targ[$fieldID][] = $file;
										}
									}
								}
							}
						}
					}
					else
					{
						$fileIDs = null;
						if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$fileIDs = $targ[$fieldID];
						}
						elseif(!empty($seedValues))
						{
							$fileIDs = $seedValues;
						}

						//HACK: Convert file IDs to file info for preventing error during UF check.
						$targ[$fieldID] = array();
						if(!empty($fileIDs))
						{
							foreach($fileIDs as $data)
							{
								if(is_array($data))
								{
									$targ[$fieldID][] = $data;
								}
								elseif(is_numeric($data) && $data > 0)
								{
									$file = null;
									if(\CCrmFileProxy::TryResolveFile($data, $file, array('ENABLE_ID' => true)))
									{
										$targ[$fieldID][] = $file;
									}
								}
							}
						}
					}
				}
			}
			elseif(!empty($seedValues))
			{
				if($isMultiple)
				{
					if(!$skipMultipleFields)
					{
						if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
						{
							$targ[$fieldID] = array_merge(
								$targ[$fieldID],
								array_diff($seedValues, $targ[$fieldID])
							);
						}
						else
						{
							$targ[$fieldID] = $seedValues;
						}
					}
					else if(!isset($targ[$fieldID]))
					{
						$targ[$fieldID] = $seedValues;
					}
				}
				elseif(!isset($targ[$fieldID]))
				{
					$targ[$fieldID] = $seedValues[0];
				}
			}
		}
		unset($fieldInfo);
	}

	protected static function prepareMultiFieldMap($typeID, array $fields)
	{
		$map = array();
		foreach($fields as $field)
		{
			$value = isset($field['VALUE']) ? trim($field['VALUE']) : '';
			if($value === '')
			{
				continue;
			}

			$key = $typeID === \CCrmFieldMulti::PHONE
				? Crm\Integrity\DuplicateCommunicationCriterion::normalizePhone($value)
				: mb_strtolower($value);

			if($key !== '' && !isset($map[$key]))
			{
				$map[$key] = array(
					'ID' => $field['ID'],
					'VALUE' => $value,
					'VALUE_TYPE' => $field['VALUE_TYPE']
				);
			}
		}
		return $map;
	}
	/**
	 * Merge multi fields.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
 	 * @param bool $skipEmpty Skip empty fields flag. If is enabled then empty fields of "seed" will not be replaced by fields from "targ"
	 * @return void
	 */
	public static function mergeMultiFields(array &$seed, array &$targ, $skipEmpty = false, array $options = array())
	{
		if(empty($seed))
		{
			return;
		}

		$map = null;
		if(isset($options['map']) && is_array($options['map']))
		{
			$map = $options['map'];
		}

		$targID = 0;
		if(isset($options['targID']) && $options['targID'] > 0)
		{
			$targID = (int)$options['targID'];
		}

		$targMap = array();
		foreach($targ as $typeID => &$fields)
		{
			if($targID > 0 && $map !== null && isset($map[$typeID]) && is_array($map[$typeID]))
			{
				$sourceIDs = isset($map[$typeID]['SOURCE_ENTITY_IDS']) && is_array($map[$typeID]['SOURCE_ENTITY_IDS'])
					? $map[$typeID]['SOURCE_ENTITY_IDS'] : array();
				if(!in_array($targID, $sourceIDs))
				{
					foreach ($fields as $fieldId => $field)
					{
						if (!preg_match('/n\d+/', (string)$fieldId)) // if not a new value
						{
							$fields[$fieldId]['VALUE'] = ''; // empty value will be removed from DB
						}
					}

					continue;
				}
			}

			$typeMap = array();
			foreach($fields as &$field)
			{
				$value = isset($field['VALUE']) ? trim($field['VALUE']) : '';
				if($value === '')
				{
					continue;
				}

				$key = $typeID === \CCrmFieldMulti::PHONE
					? Crm\Integrity\DuplicateCommunicationCriterion::normalizePhone($value)
					: mb_strtolower($value);

				if($key !== '' && !isset($typeMap[$key]))
				{
					$typeMap[$key] = true;
				}
			}
			unset($field);

			if(!empty($typeMap))
			{
				$targMap[$typeID] = &$typeMap;
			}
			unset($typeMap);
		}
		unset($fields);

		$seedID = 0;
		if(isset($options['seedID']) && $options['seedID'] > 0)
		{
			$seedID = (int)$options['seedID'];
		}

		foreach($seed as $typeID => &$fields)
		{
			if($skipEmpty && isset($targ[$typeID]))
			{
				continue;
			}

			if($seedID > 0 && $map !== null)
			{
				if(!(isset($map[$typeID]) && is_array($map[$typeID])))
				{
					//Skip merging of type that not defined in map
					continue;
				}

				$sourceIDs = isset($map[$typeID]['SOURCE_ENTITY_IDS']) && is_array($map[$typeID]['SOURCE_ENTITY_IDS'])
					? $map[$typeID]['SOURCE_ENTITY_IDS'] : array();
				if(!in_array($seedID, $sourceIDs))
				{
					//Skip merging of entity that not defined in map
					continue;
				}
			}

			$fieldNum = 1;
			foreach($fields as $field)
			{
				$value = isset($field['VALUE']) ? trim($field['VALUE']) : '';
				if($value === '')
				{
					continue;
				}

				$key = $typeID === \CCrmFieldMulti::PHONE
					? Crm\Integrity\DuplicateCommunicationCriterion::normalizePhone($value)
					: mb_strtolower($value);

				if($key !== '' && (!isset($targMap[$typeID]) || !isset($targMap[$typeID][$key])))
				{
					if(!isset($targ[$typeID]))
					{
						$targ[$typeID] = array();
					}

					while(isset($targ[$typeID]["n{$fieldNum}"]))
					{
						$fieldNum++;
					}

					$targ[$typeID]["n{$fieldNum}"] = $field;
				}
			}
		}
		unset($fields);
	}
	public static function mergeEntityBindings($entityTypeID, array &$seedBindings, array &$targBindings)
	{
		if(empty($seedBindings) && empty($targBindings))
		{
			return;
		}

		if(empty($targBindings))
		{
			$targBindings = $seedBindings;
		}
		else
		{
			$seedBindingIDs = EntityBinding::prepareEntityIDs($entityTypeID, $seedBindings);
			$targBindingIDs = EntityBinding::prepareEntityIDs($entityTypeID, $targBindings);
			$targBindingIDs = array_merge($targBindingIDs, array_diff($seedBindingIDs, $targBindingIDs));

			$targBindings = EntityBinding::prepareEntityBindings($entityTypeID, $targBindingIDs);
			EntityBinding::markFirstAsPrimary($targBindings);
		}
	}

	/**
	 * Prepare recovery data.
	 * @param array &$fields Entity fields.
	 * @param array &$entityFieldInfos Entity field infos.
	 * @param array &$userFieldInfos User field infos.
	 * @return Recovery\EntityRecoveryData
	 */
	protected static function prepareRecoveryData(array &$fields, array &$entityFieldInfos, array &$userFieldInfos)
	{
		$item = new Recovery\EntityRecoveryData();
		$itemFields = array();
		foreach($entityFieldInfos as $fieldID => &$fieldInfo)
		{
			if(isset($fields[$fieldID]))
			{
				$itemFields[$fieldID] = $fields[$fieldID];
			}
		}
		unset($fieldInfo);

		foreach($userFieldInfos as $fieldID => &$userFieldInfo)
		{
			if(isset($fields[$fieldID]))
			{
				$itemFields[$fieldID] = $fields[$fieldID];
			}
		}
		unset($userFieldInfo);

		$item->setDataItem('FIELDS', $itemFields);
		return $item;
	}
	/**
	 * Get user permissions.
	 * @return \CCrmPerms
	 */
	protected function getUserPermissions()
	{
		if($this->userPermissions === null)
		{
			$this->userPermissions = \CCrmPerms::GetUserPermissions($this->userID);
		}
		return $this->userPermissions;
	}

	/**
	 * Get registered duplicate matches for entity.
	 * @param int $entityTypeID Entity tyoe ID.
	 * @param int $entityID Entity ID.
	 * @return array
	 */
	protected function getRegisteredEntityMatches($entityTypeID, $entityID)
	{
		$results = array();

		$existedTypeScopeMap = Integrity\DuplicateManager::getExistedTypeScopeMap(
			(int)$entityTypeID,
			(int)$this->userID,
			$this->isAutomatic()
		);
		foreach($existedTypeScopeMap as $typeID => $scopes)
		{
			if($typeID === Integrity\DuplicateIndexType::PERSON)
			{
				$matches = Integrity\DuplicatePersonCriterion::getRegisteredEntityMatches($entityTypeID, $entityID);
				foreach ($scopes as $scope)
					$results[$typeID][$scope] = $matches;
			}
			elseif($typeID === Integrity\DuplicateIndexType::ORGANIZATION)
			{
				$matches = Integrity\DuplicateOrganizationCriterion::getRegisteredEntityMatches($entityTypeID, $entityID);
				foreach ($scopes as $scope)
					$results[$typeID][$scope] = $matches;
			}
			elseif($typeID === Integrity\DuplicateIndexType::COMMUNICATION_EMAIL
				|| $typeID === Integrity\DuplicateIndexType::COMMUNICATION_PHONE)
			{
				$matches = Integrity\DuplicateCommunicationCriterion::getRegisteredEntityMatches(
					$entityTypeID,
					$entityID,
					Integrity\DuplicateCommunicationCriterion::resolveTypeByIndexTypeID($typeID)
				);
				foreach ($scopes as $scope)
					$results[$typeID][$scope] = $matches;
			}
			elseif(($typeID & Integrity\DuplicateIndexType::REQUISITE) === $typeID)
			{
				foreach ($scopes as $scope)
				{
					$countryId = Crm\EntityRequisite::getCountryIdByDuplicateCriterionScope($scope);
					$fieldName = Integrity\DuplicateIndexType::resolveName($typeID);
					$results[$typeID][$scope] = Integrity\DuplicateRequisiteCriterion::getRegisteredEntityMatches($entityTypeID, $entityID, $countryId, $fieldName);
				}
			}
			elseif(($typeID & Integrity\DuplicateIndexType::BANK_DETAIL) === $typeID)
			{
				foreach ($scopes as $scope)
				{
					$countryId = Crm\EntityRequisite::getCountryIdByDuplicateCriterionScope($scope);
					$fieldName = Integrity\DuplicateIndexType::resolveName($typeID);
					$results[$typeID][$scope] = Integrity\DuplicateBankDetailCriterion::getRegisteredEntityMatches($entityTypeID, $entityID, $countryId, $fieldName);
				}
			}
		}

		return $results;
	}
	/**
	 * Process entity deletion.
	 * @param int $entityTypeID Entity tyoe ID.
	 * @param int $entityID Entity ID.
	 * @param array &$matchByType Duplicate matches grouped by type ID.
	 * @param array $params Additional params.
	 * @return void
	 * @throws Main\NotSupportedException
	 */
	protected function processEntityDeletion($entityTypeID, $entityID, array &$matchByType, array $params = array())
	{
		foreach($matchByType as $typeID => $scopeMatches)
		{
			foreach ($scopeMatches as $scope => $matchesByHash)
			{
				foreach ($matchesByHash as $matches)
				{
					$criterion = Integrity\DuplicateManager::createCriterion($typeID, $matches);
					if ($this->isAutomatic())
					{
						$builder = Integrity\DuplicateManager::createAutomaticIndexBuilder(
							$typeID,
							$entityTypeID,
							$this->userID,
							$this->enablePermissionCheck,
							array('SCOPE' => $scope)
						);
						$criterion->setLimitByAssignedUser(true);
					}
					else
					{
						$builder = Integrity\DuplicateManager::createIndexBuilder(
							$typeID,
							$entityTypeID,
							$this->userID,
							$this->enablePermissionCheck,
							array('SCOPE' => $scope)
						);
					}
					$builder->processEntityDeletion($criterion, $entityID, $params);
				}
			}
		}
		unset($typeMatches);
	}
	/**
	 * Get Entity field infos.
	 * @return array
	 */
	abstract protected function getEntityFieldsInfo();
	/**
	 * Get entity user field infos
	 * @return array
	 */
	abstract protected function getEntityUserFieldsInfo();
	/**
	 * Get entity responsible ID.
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return int
	 * @throws EntityMergerException
	 * @throws Main\NotImplementedException
	 */
	abstract protected function getEntityResponsibleID($entityID, $roleID);
	/**
	 * Get entity fields.
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @throws Main\NotImplementedException
	 * @throws EntityMergerException
	 * @return array
	 */
	abstract protected function getEntityFields($entityID, $roleID);
	/**
	 * Get entity multiple fields
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @return array
	 */
	protected function getEntityMultiFields($entityID, $roleID)
	{
		if($entityID <= 0)
		{
			return array();
		}

		$results = array();
		$dbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => $this->getEntityTypeName(),
				'ELEMENT_ID' => $entityID
			)
		);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$results[$fields['TYPE_ID']][$fields['ID']] = array(
					'ID' => $fields['ID'],
					'VALUE' => $fields['VALUE'],
					'VALUE_TYPE' => $fields['VALUE_TYPE']
				);
			}
		}
		return $results;
	}
	/**
	 * Unbind dependencies from old entity (seed) and bind them to new entity (tagget).
	 * @param int $seedID Seed entity ID.
	 * @param int $targID Target entity ID.
	 * @return void
	 */
	protected function rebind($seedID, $targID)
	{
	}
	/**
	 * Prepare collision messages.
	 * @param array &$collisions Collisions.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @return array|null
	 * @throws Main\NotImplementedException
	 */
	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ): array|null
	{
		throw new Main\NotImplementedException('Method prepareCollisionMessageFields must be overridden');
	}
	/**
	 * Setup recovery data from fields.
	 * @param Recovery\EntityRecoveryData $recoveryData Recovery Data.
	 * @param array $fields Entity fields.
	 * @return void
	 * @throws Main\NotImplementedException
	 */
	protected function setupRecoveryData(Recovery\EntityRecoveryData $recoveryData, array &$fields)
	{
		throw new Main\NotImplementedException('Method setupRecoveryData must be overridden');
	}

	/**
	 * Get field caption
	 * @param string $fieldId
	 * @throws Main\NotImplementedException
	 */
	protected function getFieldCaption(string $fieldId):string
	{
		throw new Main\NotImplementedException('Method getFieldCaption must be overridden');
	}

	/**
	 * Save history items
	 */
	protected function saveHistoryItems(int $entityId, array $historyItems)
	{
		$event = new \CCrmEvent();
		foreach ($historyItems as $fieldId => $item)
		{
			$fieldId = (string)$fieldId;
			$eventParams = [
				'ENTITY_FIELD' => $fieldId,
				'EVENT_NAME' => Main\Localization\Loc::getMessage('CRM_ENTITY_MERGER_HISTORY_ITEM_TITLE'),
				'EVENT_TEXT_1' => Main\Localization\Loc::getMessage(
					'CRM_ENTITY_MERGER_HISTORY_ITEM_VALUE', [
						'#FIELD#' => $this->getFieldCaption($fieldId),
						'#VALUE#' => implode(', ', array_unique($item))
					]),
				'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($this->entityTypeID),
				'ENTITY_ID' => $entityId,
				'EVENT_TYPE' => \CCrmEvent::TYPE_MERGER
			];
			$event->Add($eventParams, false);
		}
	}

	/**
	 * Check entity read permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	abstract protected function checkEntityReadPermission($entityID, $userPermissions);
	/**
	 * Check entity update permission for user.
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	abstract protected function checkEntityUpdatePermission($entityID, $userPermissions);
	/**
	 * Check entity delete permission for user
	 * @param int $entityID Entity ID.
	 * @param \CCrmPerms $userPermissions User permissions.
	 * @return bool
	 */
	abstract protected function checkEntityDeletePermission($entityID, $userPermissions);
	/**
	 * Update entity.
	 * @param int $entityID Entity ID.
	 * @param array &$fields Entity fields.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 * @throws EntityMergerException
	 */
	abstract protected function updateEntity($entityID, array &$fields, $roleID, array $options = array());
	/**
	 * Delete entity.
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 * @throws EntityMergerException
	 */
	abstract protected function deleteEntity($entityID, $roleID, array $options = array());
}
