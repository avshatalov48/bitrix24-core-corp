<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Binding\EntityBinding;

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
			($by='id'),
			($order='asc'),
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
		EntityMerger::mergeUserFields($seed, $targ, $userFieldInfos, $options);

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

		$this->innerMergeEntityFields($seed, $targ, $entityFieldInfos);
		EntityMerger::mergeUserFields($seed, $targ, $userFieldInfos);

		$seedMultiFields = $this->getEntityMultiFields($seedID, self::ROLE_SEED);
		$targMultiFields = $this->getEntityMultiFields($targID, self::ROLE_TARG);

		EntityMerger::mergeMultiFields($seedMultiFields, $targMultiFields);

		if(!empty($targMultiFields))
		{
			$targ['FM'] = $targMultiFields;
		}

		//region Recovery
		//$recoveryData = self::prepareRecoveryData($seed, $entityFieldInfos, $userFieldInfos);
		//$recoveryData->setEntityTypeID($entityTypeID);
		//$recoveryData->setEntityID($seedID);
		//$this->setupRecoveryData($recoveryData, $seed);

		//if(!empty($seedMultiFields))
		//{
		//	$recoveryData->setDataItem('MULTI_FIELDS', $seedMultiFields);
		//}

		//$activityIDs = \CCrmActivity::GetBoundIDs($entityTypeID, $seedID);
		//if(!empty($activityIDs))
		//{
		//	$recoveryData->setDataItem('ACTIVITY_IDS', $activityIDs);
		//}

		//$eventIDs = array();
		//$result = \CCrmEvent::GetListEx(
		//	array('EVENT_REL_ID' => 'ASC'),
		//	array(
		//		'ENTITY_TYPE' => $entityTypeName,
		//		'ENTITY_ID' => $seedID,
		//		'EVENT_TYPE' => 0,
		//		'CHECK_PERMISSIONS' => 'N'
		//	),
		//	false,
		//	false,
		//	array('EVENT_REL_ID')
		//);

		//if(is_object($result))
		//{
		//	while($eventFields = $result->Fetch())
		//	{
		//		$eventIDs[] = (int)$eventFields['EVENT_REL_ID'];
		//	}
		//}
		//if(!empty($eventIDs))
		//{
		//	$recoveryData->setDataItem('EVENT_IDS', $eventIDs);
		//}

		//$recoveryData->setUserID($this->userID);
		//$recoveryData->save();
		//endregion

		$matches = $this->getRegisteredEntityMatches($entityTypeID, $seedID);

		//region Merge requisites
		if ($entityTypeID === \CCrmOwnerType::Company || $entityTypeID === \CCrmOwnerType::Contact)
		{
			$requsiitedMergingHelper = new RequisiteMergingHelper($entityTypeID, $seedID, $targID);
			$requsiitedMergingHelper->merge();
		}
		//endregion Merge requisites

		$this->updateEntity($targID, $targ, self::ROLE_TARG);

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
		Integrity\DuplicateIndexBuilder::markAsJunk($entityTypeID, $seedID);

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
			array('DISABLE_USER_FIELD_CHECK' => true)
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
	/**
	 * Merge entity fields.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @param array &$fieldInfos Entity field infos.
	 * @param bool $skipEmpty Skip empty fields flag.
	 * @return void
	 */
	protected static function mergeEntityFields(array &$seed, array &$targ, array &$fieldInfos, $skipEmpty = false)
	{
		if(empty($seed))
		{
			return;
		}

		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			// Skip PK
			if($fieldID === 'ID')
			{
				continue;
			}

			// Skip READONLY fields
			if(isset($fieldInfo['ATTRIBUTES'])
				&& in_array(\CCrmFieldInfoAttr::ReadOnly, $fieldInfo['ATTRIBUTES'], true))
			{
				continue;
			}

			$targFlg = isset($targ[$fieldID]);
			$seedFlg = isset($seed[$fieldID]);

			if(!$skipEmpty)
			{
				$type = isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : 'string';
				if($type === 'string'
					|| $type === 'char'
					|| $type === 'datetime'
					|| $type === 'crm_status'
					|| $type === 'crm_currency')
				{
					$targFlg = $targFlg && $targ[$fieldID] !== '';
					$seedFlg = $seedFlg && $seed[$fieldID] !== '';
				}
				elseif($type === 'double')
				{
					$targFlg = $targFlg && doubleval($targ[$fieldID]) !== 0.0;
					$seedFlg = $seedFlg && doubleval($seed[$fieldID]) !== 0.0;
				}
				elseif($type === 'integer' || $type === 'user')
				{
					$targFlg = $targFlg && intval($targ[$fieldID]) !== 0;
					$seedFlg = $seedFlg && intval($seed[$fieldID]) !== 0;
				}
			}

			// Skip if target entity field is defined
			// Skip if seed entity field is not defined
			if(!$targFlg && $seedFlg)
			{
				$targ[$fieldID] = $seed[$fieldID];
			}
		}
		unset($fieldInfo);
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
		self::mergeEntityFields($seed, $targ, $fieldInfos, $skipEmpty);
	}
	/**
	 * Merge user fields.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
	 * @param array &$fieldInfos Entity field infos.
	 * @return void
	 */
	protected static function mergeUserFields(array &$seed, array &$targ, array &$fieldInfos, array $options = array())
	{
		if(empty($seed))
		{
			return;
		}

		$skipMultipleFields = isset($options['SKIP_MULTIPLE_USER_FIELDS']) && $options['SKIP_MULTIPLE_USER_FIELDS'];
		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			$isMultiple = $fieldInfo['MULTIPLE'] === 'Y';
			$typeID = $fieldInfo['USER_TYPE_ID'];

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
	/**
	 * Merge multi fields.
	 * @param array &$seed Seed entity fields.
	 * @param array &$targ Target entity fields.
 	 * @param bool $skipEmpty Skip empty fields flag. If is enabled then empty fields of "seed" will not be replaced by fields from "targ"
	 * @return void
	 */
	public static function mergeMultiFields(array &$seed, array &$targ, $skipEmpty = false)
	{
		if(empty($seed))
		{
			return;
		}

		$targMap = array();
		foreach($targ as $typeID => &$fields)
		{
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
					: strtolower($value);

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

		foreach($seed as $typeID => &$fields)
		{
			if($skipEmpty && isset($targ[$typeID]))
			{
				continue;
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
					: strtolower($value);

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

		foreach(Integrity\DuplicateIndexBuilder::getExistedTypeScopeMap($entityTypeID, $this->userID) as $typeID => $scopes)
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
	 * @return void
	 * @throws Main\NotSupportedException
	 */
	protected function processEntityDeletion($entityTypeID, $entityID, array &$matchByType)
	{
		foreach($matchByType as $typeID => $scopeMatches)
		{
			foreach ($scopeMatches as $scope => $matchesByHash)
			{
				foreach ($matchesByHash as $matches)
				{
					$builder = Integrity\DuplicateManager::createIndexBuilder(
						$typeID,
						$entityTypeID,
						$this->userID,
						$this->enablePermissionCheck,
						array('SCOPE' => $scope)
					);

					$builder->processEntityDeletion(
						Integrity\DuplicateManager::createCriterion($typeID, $matches),
						$entityID
					);
				}
			}
		}
		unset($typeMatches);
	}
	/**
	 * Get Enity field infos.
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
	protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ)
	{
		throw new Main\NotImplementedException('Method setupRecoveryData must be overridden');
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
	 */
	abstract protected function updateEntity($entityID, array &$fields, $roleID, array $options = array());
	/**
	 * Delete entity.
	 * @param int $entityID Entity ID.
	 * @param int $roleID Entity Role ID (is not required).
	 * @param array $options Options.
	 * @return void
	 * @throws Main\NotImplementedException
	 */
	abstract protected function deleteEntity($entityID, $roleID, array $options = array());
}