<?php

IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Service\Container;

class CCrmPerms
{
	const PERM_NONE = BX_CRM_PERM_NONE;
	const PERM_SELF = BX_CRM_PERM_SELF;
	const PERM_DEPARTMENT = BX_CRM_PERM_DEPARTMENT;
	const PERM_SUBDEPARTMENT = BX_CRM_PERM_SUBDEPARTMENT;
	const PERM_OPEN = BX_CRM_PERM_OPEN;
	const PERM_ALL = BX_CRM_PERM_ALL;
	const PERM_CONFIG = BX_CRM_PERM_CONFIG;

	const ATTR_READ_ALL = 'RA';

	private static $ENTITY_ATTRS = array();
	private static $INSTANCES = array();
	private static $USER_ADMIN_FLAGS = array();
	protected $cdb = null;
	protected $userId = 0;
	protected $arUserPerms = array();

	function __construct($userId)
	{
		global $DB;
		$this->cdb = $DB;

		$this->userId = intval($userId);
		$this->arUserPerms = CCrmRole::GetUserPerms($this->userId);
	}

	/**
	 * Get current user permissions
	 * @return \CCrmPerms
	 */
	public static function GetCurrentUserPermissions()
	{
		$userID = CCrmSecurityHelper::GetCurrentUserID();
		if(!isset(self::$INSTANCES[$userID]))
		{
			self::$INSTANCES[$userID] = new CCrmPerms($userID);
		}
		return self::$INSTANCES[$userID];
	}

	/**
	 * Get specified user permissions
	 * @param int $userID User ID.
	 * @return \CCrmPerms
	 */
	public static function GetUserPermissions($userID)
	{
		if(!is_int($userID))
		{
			$userID = intval($userID);
		}

		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!isset(self::$INSTANCES[$userID]))
		{
			self::$INSTANCES[$userID] = new CCrmPerms($userID);
		}
		return self::$INSTANCES[$userID];
	}

	public static function GetCurrentUserID()
	{
		return CCrmSecurityHelper::GetCurrentUserID();
	}

	public static function IsAdmin($userID = 0)
	{
		if(!is_int($userID))
		{
			$userID = is_numeric($userID) ? (int)$userID : 0;
		}

		$result = false;
		if($userID <= 0)
		{
			$user = CCrmSecurityHelper::GetCurrentUser();
			$userID =  $user->GetID();

			if($userID <= 0)
			{
				false;
			}

			if(isset(self::$USER_ADMIN_FLAGS[$userID]))
			{
				return self::$USER_ADMIN_FLAGS[$userID];
			}

			$result = $user->IsAdmin();
			if($result)
			{
				self::$USER_ADMIN_FLAGS[$userID] = true;
				return true;
			}

			try
			{
				if(\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
					&& CModule::IncludeModule('bitrix24'))
				{
					if(class_exists('CBitrix24')
						&& method_exists('CBitrix24', 'IsPortalAdmin'))
					{
						// New style check
						$result = CBitrix24::IsPortalAdmin($userID);
					}
					else
					{
						// Check user group 1 ('Portal admins')
						$arGroups = $user->GetUserGroup($userID);
						$result = in_array(1, $arGroups);
					}
				}
			}
			catch(Exception $e)
			{
			}
		}
		else
		{
			if(isset(self::$USER_ADMIN_FLAGS[$userID]))
			{
				return self::$USER_ADMIN_FLAGS[$userID];
			}

			try
			{
				if(IsModuleInstalled('bitrix24')
					&& CModule::IncludeModule('bitrix24')
					&& class_exists('CBitrix24')
					&& method_exists('CBitrix24', 'IsPortalAdmin'))
				{
					// Bitrix24 context new style check
					$result = CBitrix24::IsPortalAdmin($userID);
				}
				else
				{
					//Check user group 1 ('Admins')
					$user = new CUser();
					$arGroups = $user->GetUserGroup($userID);
					$result = in_array(1, $arGroups);
				}
			}
			catch(Exception $e)
			{
			}
		}
		self::$USER_ADMIN_FLAGS[$userID] = $result;
		return $result;
	}

	public static function IsAuthorized()
	{
		return CCrmSecurityHelper::GetCurrentUser()->IsAuthorized();
	}

	static public function GetUserAttr($iUserID)
	{
		static $arResult = array();
		if (!empty($arResult[$iUserID]))
		{
			return $arResult[$iUserID];
		}

		$iUserID = (int) $iUserID;

		$arResult[$iUserID] = array();

		$obRes = CAccess::GetUserCodes($iUserID);
		while($arCode = $obRes->Fetch())
			if (mb_strpos($arCode['ACCESS_CODE'], 'DR') !== 0)
				$arResult[$iUserID][mb_strtoupper($arCode['PROVIDER_ID'])][] = $arCode['ACCESS_CODE'];

		if (!empty($arResult[$iUserID]['INTRANET']) && Bitrix\Main\Loader::includeModule('intranet'))
		{
			foreach ($arResult[$iUserID]['INTRANET'] as $iDepartment)
			{
				if(mb_substr($iDepartment, 0, 1) === 'D')
				{
					$arTree = CIntranetUtils::GetDeparmentsTree(mb_substr($iDepartment, 1), true);
					foreach ($arTree as $iSubDepartment)
					{
						$arResult[$iUserID]['SUBINTRANET'][] = 'D'.$iSubDepartment;
					}
				}
			}
		}

		return $arResult[$iUserID];
	}

	static public function BuildUserEntityAttr($userID)
	{
		$result = array('INTRANET' => array());
		$userID = intval($userID);
		$arUserAttrs = $userID > 0 ? self::GetUserAttr($userID) : array();
		if(!empty($arUserAttrs['INTRANET']))
		{
			//HACK: Removing intranet subordination relations, otherwise staff will get access to boss's entities
			foreach($arUserAttrs['INTRANET'] as $code)
			{
				if(mb_strpos($code, 'IU') !== 0)
				{
					$result['INTRANET'][] = $code;
				}
			}
			$result['INTRANET'][] = "IU{$userID}";
		}
		return $result;
	}

	static public function GetCurrentUserAttr()
	{
		return self::GetUserAttr(CCrmSecurityHelper::GetCurrentUserID());
	}

	public function GetUserID()
	{
		return $this->userId;
	}

	public function GetUserPerms()
	{
		return $this->arUserPerms;
	}

	public function HavePerm($permEntity, $permAttr, $permType = 'READ')
	{
		// HACK: only for product and currency support
		$permType = mb_strtoupper($permType);
		if ($permEntity == 'CONFIG' && $permAttr == self::PERM_CONFIG && $permType == 'READ')
		{
			return true;
		}

		// HACK: Compatibility with CONFIG rights
		if ($permEntity == 'CONFIG')
			$permType = 'WRITE';

		if(self::IsAdmin($this->userId))
		{
			return $permAttr != self::PERM_NONE;
		}

		if (!isset($this->arUserPerms[$permEntity][$permType]))
			return $permAttr == self::PERM_NONE;

		$icnt = count($this->arUserPerms[$permEntity][$permType]);
		if ($icnt > 1 && $this->arUserPerms[$permEntity][$permType]['-'] == self::PERM_NONE)
		{
			foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
			{
				if ($sField == '-')
					continue ;
				$sPrevPerm = $permAttr;
				foreach ($arFieldValue as $fieldValue => $sAttr)
					if ($sAttr > $permAttr)
						return $sAttr == self::PERM_NONE;
				return $permAttr == self::PERM_NONE;
			}
		}

		if ($permAttr == self::PERM_NONE)
			return $this->arUserPerms[$permEntity][$permType]['-'] == self::PERM_NONE;

		if ($this->arUserPerms[$permEntity][$permType]['-'] >= $permAttr)
			return true;

		return false;
	}

	public function GetPermType($permEntity, $permType = 'READ', $arEntityAttr = array())
	{
		if (self::IsAdmin($this->userId))
			return self::PERM_ALL;

		if (!isset($this->arUserPerms[$permEntity][$permType]))
			return self::PERM_NONE;

		if($permType === 'READ'
			&& (in_array(self::ATTR_READ_ALL, $arEntityAttr, true)
				|| in_array('CU'.$this->userId, $arEntityAttr, true)
			)
		)
		{
			return self::PERM_ALL;
		}

		$icnt = count($this->arUserPerms[$permEntity][$permType]);

		if ($icnt == 1 && isset($this->arUserPerms[$permEntity][$permType]['-']))
			return $this->arUserPerms[$permEntity][$permType]['-'];
		else if ($icnt > 1)
		{
			foreach ($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
			{
				if ($sField == '-')
					continue ;
				foreach ($arFieldValue as $fieldValue => $sAttr)
				{
					if (in_array($sField.$fieldValue, $arEntityAttr))
						return $sAttr;
				}
			}
			return $this->arUserPerms[$permEntity][$permType]['-'];
		}
		else
			return self::PERM_NONE;
	}

	public static function GetEntityGroup($permEntity, $permAttr = self::PERM_NONE, $permType = 'READ')
	{
		global $DB;

		$arResult = array();
		$arRole = CCrmRole::GetRoleByAttr($permEntity, $permAttr, $permType);

		if (!empty($arRole))
		{
			$sSql = 'SELECT RELATION FROM b_crm_role_relation WHERE RELATION LIKE \'G%\' AND ROLE_ID IN ('.implode(',', $arRole).')';
			$res = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while($row = $res->Fetch())
				$arResult[] = mb_substr($row['RELATION'], 1);
		}
		return $arResult;
	}

	public static function GetEntityRelations($permEntity, $permAttr = self::PERM_NONE, $permType = 'READ')
	{
		global $DB;

		$arResult = array();
		$arRole = CCrmRole::GetRoleByAttr($permEntity, $permAttr, $permType);

		if (!empty($arRole))
		{
			$sSql = 'SELECT RELATION FROM b_crm_role_relation WHERE ROLE_ID IN ('.implode(',', $arRole).')';
			$res = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while($row = $res->Fetch())
				$arResult[] = $row['RELATION'];
		}
		return $arResult;
	}

	static public function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = self::GetCurrentUserPermissions();
		}

		$result = (
			CCrmLead::IsAccessEnabled($userPermissions)
			|| CCrmContact::IsAccessEnabled($userPermissions)
			|| CCrmCompany::IsAccessEnabled($userPermissions)
			|| CCrmDeal::IsAccessEnabled($userPermissions)
			|| CCrmQuote::IsAccessEnabled($userPermissions)
			|| CCrmInvoice::IsAccessEnabled($userPermissions)
		);

		if (!$result)
		{
			$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
			// avoiding exceptions as this method has usages across the product.
			try
			{
				$dynamicTypesMap->load([
					'isLoadStages' => false,
					'isLoadCategories' => false,
				]);
			}
			catch (Exception $exception)
			{
			}
			catch (Error $error)
			{
			}
			foreach ($dynamicTypesMap->getTypes() as $type)
			{
				if (
					Container::getInstance()->getUserPermissions($userPermissions->GetUserID())
						->canReadType($type->getEntityTypeId())
				)
				{
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	public function CheckEnityAccess($permEntity, $permType, $arEntityAttr)
	{
		if (!is_array($arEntityAttr))
			$arEntityAttr = array();

		$enableCummulativeMode = COption::GetOptionString('crm', 'enable_permission_cumulative_mode', 'Y') === 'Y';

		$permAttr = $this->GetPermType($permEntity, $permType, $arEntityAttr);
		if ($permAttr == self::PERM_NONE)
		{
			return false;
		}
		if ($permAttr == self::PERM_ALL)
		{
			return true;
		}
		if ($permAttr == self::PERM_OPEN)
		{
			if((in_array('O', $arEntityAttr) || in_array('U'.$this->userId, $arEntityAttr)))
			{
				return true;
			}

			//For backward compatibility (is not comulative mode)
			if(!$enableCummulativeMode)
			{
				return false;
			}
		}
		if ($permAttr >= self::PERM_SELF && in_array('U'.$this->userId, $arEntityAttr))
		{
			return true;
		}

		$arAttr = self::GetUserAttr($this->userId);

		if ($permAttr >= self::PERM_DEPARTMENT && is_array($arAttr['INTRANET']))
		{
			// PERM_OPEN: user may access to not opened entities in his department
			foreach ($arAttr['INTRANET'] as $iDepartment)
			{
				if (in_array($iDepartment, $arEntityAttr))
				{
					return true;
				}
			}
		}
		if ($permAttr >= self::PERM_SUBDEPARTMENT && is_array($arAttr['SUBINTRANET']))
		{
			// PERM_OPEN: user may access to not opened entities in his intranet
			foreach ($arAttr['SUBINTRANET'] as $iDepartment)
			{
				if (in_array($iDepartment, $arEntityAttr))
				{
					return true;
				}
			}
		}
		return false;
	}

	public function GetUserAttrForSelectEntity($permEntity, $permType, $bForcePermAll = false)
	{
		$arResult = array();
		if (!isset($this->arUserPerms[$permEntity][$permType]))
			return $arResult;

		$entityTypeName = self::ResolveEntityTypeName($permEntity);
		$arAttr = self::GetUserAttr($this->userId);
		$sDefAttr = $this->arUserPerms[$permEntity][$permType]['-'];
		foreach($this->arUserPerms[$permEntity][$permType] as $sField => $arFieldValue)
		{
			if($sField === '-' && count($this->arUserPerms[$permEntity][$permType]) == 1)
			{
				$_arResult = array();
				$sAttr = $sDefAttr;
				if ($sAttr == self::PERM_NONE)
				{
					continue;
				}
				if ($sAttr == self::PERM_OPEN)
				{
					$_arResult[] = 'O';
					foreach ($arAttr['USER'] as $iUser)
					{
						$arResult[] = array($iUser);
					}
				}
				else if ($sAttr != self::PERM_ALL || $bForcePermAll)
				{
					if ($sAttr >= self::PERM_SELF)
					{
						foreach ($arAttr['USER'] as $iUser)
						{
							$arResult[] = array($iUser);
						}
					}
					if ($sAttr >= self::PERM_DEPARTMENT && isset($arAttr['INTRANET']))
					{
						foreach ($arAttr['INTRANET'] as $iDepartment)
						{
							//HACK: SKIP IU code it is not required for this method
							if($iDepartment <> '' && mb_substr($iDepartment, 0, 2) === 'IU')
							{
								continue;
							}

							if(!in_array($iDepartment, $_arResult))
							{
								$_arResult[] = $iDepartment;
							}
						}
					}
					if ($sAttr >= self::PERM_SUBDEPARTMENT && isset($arAttr['SUBINTRANET']))
					{
						foreach ($arAttr['SUBINTRANET'] as $iDepartment)
						{
							if($iDepartment <> '' && mb_substr($iDepartment, 0, 2) === 'IU')
							{
								continue;
							}

							if(!in_array($iDepartment, $_arResult))
							{
								$_arResult[] = $iDepartment;
							}
						}
					}
				}
				else //self::PERM_ALL
				{
					$arResult[] = array();
				}

				if(!empty($_arResult))
				{
					$arResult[] = $_arResult;
				}
			}
			else
			{
				$arStatus = array();
				if($entityTypeName == CCrmOwnerType::LeadName && $sField == 'STATUS_ID')
				{
					$arStatus = CCrmStatus::GetStatusList('STATUS');
				}
				else if($entityTypeName == CCrmOwnerType::QuoteName && $sField == 'STATUS_ID')
				{
					$arStatus = CCrmStatus::GetStatusList('QUOTE_STATUS');
				}
				else if($entityTypeName == CCrmOwnerType::DealName && $sField == 'STAGE_ID')
				{
					$arStatus = DealCategory::getStageList(
						DealCategory::convertFromPermissionEntityType($permEntity)
					);
				}
				else
				{
					// it's a very big crutch
					$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
					if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
					{
						$factory = Container::getInstance()->getFactory($entityTypeId);
						if ($factory && $factory->isStagesSupported())
						{
							[, , $categoryId] = explode('_', $permEntity);
							$categoryId = (int)mb_substr($categoryId, 1);
							$stages = $factory->getStages($categoryId);
							foreach ($stages->getAll() as $stage)
							{
								$arStatus[$stage->getStatusId()] = $stage->collectValues();
							}
						}
					}
				}

				foreach ($arStatus as $fieldValue => $sTitle)
				{
					$_arResult = array();
					$sAttr = $sDefAttr;
					if (isset($this->arUserPerms[$permEntity][$permType][$sField][$fieldValue]))
					{
						$sAttr = $this->arUserPerms[$permEntity][$permType][$sField][$fieldValue];
					}
					if ($sAttr == self::PERM_NONE)
					{
						continue;
					}
					//$_arResult[] = $sField.$fieldValue;
					if ($sAttr == self::PERM_OPEN)
					{
						$_arResult[] = 'O';
						foreach ($arAttr['USER'] as $iUser)
						{
							$arResult[] = array($sField.$fieldValue, $iUser);
						}
					}
					else if ($sAttr != self::PERM_ALL)
					{
						if ($sAttr >= self::PERM_SELF)
						{
							foreach ($arAttr['USER'] as $iUser)
							{
								$arResult[] = array($sField.$fieldValue, $iUser);
							}
						}
						if ($sAttr >= self::PERM_DEPARTMENT && isset($arAttr['INTRANET']))
						{
							foreach ($arAttr['INTRANET'] as $iDepartment)
							{
								if(mb_strlen($iDepartment) > 2 && mb_substr($iDepartment, 0, 2) === 'IU')
								{
									continue;
								}

								if(!in_array($iDepartment, $_arResult))
								{
									$_arResult[] = $iDepartment;
								}
							}
						}
						if ($sAttr >= self::PERM_SUBDEPARTMENT && isset($arAttr['SUBINTRANET']))
						{
							foreach ($arAttr['SUBINTRANET'] as $iDepartment)
							{
								if(mb_strlen($iDepartment) > 2 && mb_substr($iDepartment, 0, 2) === 'IU')
								{
									continue;
								}

								if(!in_array($iDepartment, $_arResult))
								{
									$_arResult[] = $iDepartment;
								}
							}
						}
					}
					else //self::PERM_ALL
					{
						$arResult[] = array($sField.$fieldValue);
					}
					
					if(!empty($_arResult))
					{
						$arResult[] = array_merge(array($sField.$fieldValue), $_arResult);
					}
				}
			}
		}

		return $arResult;
	}

	static private function RegisterPermissionSet(&$items, $newItem)
	{
		$qty = count($items);
		if($qty === 0)
		{
			$items[] = $newItem;
			return $newItem;
		}

		$user = $newItem['USER'];
		$openedOnly = $newItem['OPENED_ONLY'];
		$departments = $newItem['DEPARTMENTS'];
		$departmentQty = count($departments);
		for($i = 0; $i < $qty; $i++)
		{
			if($user === $items[$i]['USER']
				&& $openedOnly === $items[$i]['OPENED_ONLY']
				&& $departmentQty === count($items[$i]['DEPARTMENTS'])
				&& ($departmentQty === 0 || count(array_diff($departments, $items[$i]['DEPARTMENTS'])) === 0))
			{
				$items[$i]['SCOPES'] = array_merge($items[$i]['SCOPES'], $newItem['SCOPES']);
				return $items[$i];
			}
		}

		$items[] = $newItem;
		return $newItem;
	}

	static public function BuildSqlForEntitySet(array $entityTypes, $aliasPrefix, $permType, $options = array())
	{
		$total = count($entityTypes);
		if($total === 0)
		{
			return false;
		}

		if($total === 1)
		{
			return self::BuildSql($entityTypes[0], $aliasPrefix, $permType, $options);
		}

		$restrictedQueries = array();
		$unrestrictedQueries = array();

		//Original RAW_QUERY param will be processed after latter
		$rawQueryParam = isset($options['RAW_QUERY']) ? $options['RAW_QUERY'] : false;
		$entityOptions = array_merge($options, array('RAW_QUERY' => true));
		$sqlType = isset($options['PERMISSION_SQL_TYPE']) && $options['PERMISSION_SQL_TYPE'] === 'FROM' ? 'FROM' : 'WHERE';

		$effectiveEntityIDs = array();
		if(isset($entityOptions['RESTRICT_BY_IDS']) && is_array($entityOptions['RESTRICT_BY_IDS']))
		{
			$restrictByIDs = $entityOptions['RESTRICT_BY_IDS'];
			foreach($restrictByIDs as $entityID)
			{
				if($entityID > 0)
				{
					$effectiveEntityIDs[] = (int)$entityID;
				}
			}
		}

		for($i = 0; $i < $total; $i++)
		{
			$entityType = $entityTypes[$i];
			$sql = self::BuildSql($entityType, $aliasPrefix, $permType, $entityOptions);

			if($sql === false)
			{
				continue;
			}

			if($sql === '')
			{
				$subQuery = "SELECT {$aliasPrefix}P.ENTITY_ID FROM b_crm_entity_perms {$aliasPrefix}P WHERE {$aliasPrefix}P.ENTITY = '{$entityType}'";
				if(!empty($effectiveEntityIDs))
				{
					$subQuery .= " AND {$aliasPrefix}P.ENTITY_ID IN (".implode(', ', $effectiveEntityIDs).")";
				}
				if($sqlType !== 'WHERE')
				{
					$subQuery .= " GROUP BY {$aliasPrefix}P.ENTITY_ID";
				}

				$unrestrictedQueries[] = $subQuery;
			}
			else
			{
				$restrictedQueries[] = $sql;
			}
		}

		if(!empty($restrictedQueries))
		{
			$queries = array_merge($unrestrictedQueries, $restrictedQueries);
		}
		else
		{
			$unrestricted = count($unrestrictedQueries);
			if($unrestricted === 0)
			{
				return false;
			}

			if($unrestricted === $total)
			{
				return '';
			}

			$queries = $unrestrictedQueries;
		}


		$sqlUnion = isset($options['PERMISSION_SQL_UNION']) && $options['PERMISSION_SQL_UNION'] === 'DISTINCT' ? 'DISTINCT' : 'ALL';
		$querySql = implode($sqlUnion === 'DISTINCT' ? ' UNION ' : ' UNION ALL ', $queries);
		if($rawQueryParam === true || is_array($rawQueryParam))
		{
			if(is_array($rawQueryParam) && isset($rawQueryParam['TOP']) && $rawQueryParam['TOP'] > 0)
			{
				$order = isset($rawQueryParam['SORT_TYPE'])
					&& mb_strtoupper($rawQueryParam['SORT_TYPE']) === 'DESC'
					? 'DESC' : 'ASC';;

				$querySql = \Bitrix\Main\Application::getConnection()->getSqlHelper()->getTopSql(
					"{$querySql} ORDER BY ENTITY_ID {$order}",
					$rawQueryParam['TOP']
				);
			}
			return $querySql;
		}

		$identityCol = 'ID';
		if(is_array($options)
			&& isset($options['IDENTITY_COLUMN'])
			&& is_string($options['IDENTITY_COLUMN'])
			&& $options['IDENTITY_COLUMN'] !== '')
		{
			$identityCol = $options['IDENTITY_COLUMN'];
		}

		if($sqlType === 'WHERE')
		{
			return "{$aliasPrefix}.{$identityCol} IN ({$querySql})";
		}

		return "INNER JOIN ({$querySql}) {$aliasPrefix}GP ON {$aliasPrefix}.{$identityCol} = {$aliasPrefix}GP.ENTITY_ID";
	}

	static public function BuildSql($permEntity, $sAliasPrefix, $mPermType, $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$perms = null;
		if(isset($arOptions['PERMS']))
		{
			$perms = $arOptions['PERMS'];
		}

		if(!is_object($perms))
		{
			// Process current user permissions
			if (self::IsAdmin(0))
			{
				return '';
			}

			$perms = self::GetCurrentUserPermissions();
		}
		elseif(self::IsAdmin($perms->GetUserID()))
		{
			return '';
		}

		$arUserAttr = array();
		$arPermType = is_array($mPermType) ? $mPermType : array($mPermType);
		foreach ($arPermType as $sPermType)
		{
			$arUserAttr = array_merge($arUserAttr, $perms->GetUserAttrForSelectEntity($permEntity, $sPermType));
		}

		if (empty($arUserAttr))
		{
			// Access denied
			return false;
		}

		$restrictByIDs = null;
		if(isset($arOptions['RESTRICT_BY_IDS']) && is_array($arOptions['RESTRICT_BY_IDS']))
		{
			$restrictByIDs = $arOptions['RESTRICT_BY_IDS'];
		}

		$scopeRegex = '';
		switch(self::ResolveEntityTypeName($permEntity))
		{
			case CCrmOwnerType::LeadName:
			{
				$scopeRegex = '/^STATUS_ID[0-9A-Z\:\_\-]+$/i';
				break;
			}
			case CCrmOwnerType::DealName:
			{
				$scopeRegex = '/^STAGE_ID[0-9A-Z\:\_\-]+$/i';
				break;
			}
			case CCrmOwnerType::QuoteName:
			{
				$scopeRegex = '/^QUOTE_ID[0-9A-Z\:\_\-]+$/i';
				break;
			}
		}
		if ($scopeRegex === '')
		{
			$entityName = static::ResolveEntityTypeName($permEntity);
			if (\CCrmOwnerType::isPossibleDynamicTypeId(\CCrmOwnerType::ResolveID($entityName)))
			{
				$scopeRegex = '/^STAGE_ID[0-9A-Z\:\_\-]+$/i';
			}
		}

		$enableCummulativeMode = COption::GetOptionString('crm', 'enable_permission_cumulative_mode', 'Y') === 'Y';
		$allAttrs = self::GetUserAttr($perms->GetUserID());
		$intranetAttrs = array();
		$allIntranetAttrs = isset($allAttrs['INTRANET']) && is_array($allAttrs['INTRANET'])
			? $allAttrs['INTRANET'] : array();
		if(!empty($allIntranetAttrs))
		{
			foreach($allIntranetAttrs as $attr)
			{
				if(preg_match('/^D\d+$/', $attr))
				{
					$intranetAttrs[] = "'{$attr}'";
				}
			}
		}

		$subIntranetAttrs = array();
		$allSubIntranetAttrs = isset($allAttrs['SUBINTRANET']) && is_array($allAttrs['SUBINTRANET'])
			? $allAttrs['SUBINTRANET'] : array();
		if(!empty($allSubIntranetAttrs))
		{
			foreach($allSubIntranetAttrs as $attr)
			{
				if(preg_match('/^D\d+$/', $attr))
				{
					$subIntranetAttrs[] = "'{$attr}'";
				}
			}
		}

		$permissionSets = array();
		foreach ($arUserAttr as &$attrs)
		{
			if (empty($attrs))
			{
				continue;
			}

			$permissionSet = array(
				'USER' => '',
				'CONCERNED_USER' => '',
				'DEPARTMENTS' => array(),
				'OPENED_ONLY' => '',
				'SCOPES' => array()
			);

			$qty = count($attrs);
			for($i = 0; $i < $qty; $i++)
			{
				$attr = $attrs[$i];

				if($scopeRegex !== '' && preg_match($scopeRegex, $attr))
				{
					$permissionSet['SCOPES'][] = "'{$attr}'";
				}
				elseif($attr === 'O')
				{
					$permissionSet['OPENED_ONLY'] = "'{$attr}'";
				}
				elseif(preg_match('/^U\d+$/', $attr))
				{
					$permissionSet['USER'] = "'{$attr}'";
					$permissionSet['CONCERNED_USER'] = "'C{$attr}'";
				}
				elseif(preg_match('/^D\d+$/', $attr))
				{
					$permissionSet['DEPARTMENTS'][] = "'{$attr}'";
				}
			}

			if(empty($permissionSet['SCOPES']))
			{
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					//HACK: for OPENED ONLY mode - allow user own entities too.
					$userAttr = isset($allAttrs['USER']) && is_array($allAttrs['USER']) && !empty($allAttrs['USER']) ? $allAttrs['USER'][0] : '';
					if($userAttr !== '')
					{
						$permissionSets[] = array(
							'USER' => "'{$userAttr}'",
							'CONCERNED_USER' => "'C{$userAttr}'",
							'DEPARTMENTS' => array(),
							'OPENED_ONLY' => '',
							'SCOPES' => array()
						);
					}

					if($enableCummulativeMode && !empty($intranetAttrs))
					{
						//OPENED ONLY mode - allow user department entities too.
						$permissionSets[] = array(
							'USER' => '',
							'CONCERNED_USER' => '',
							'DEPARTMENTS' => array_unique(array_merge($intranetAttrs, $subIntranetAttrs)),
							'OPENED_ONLY' => '',
							'SCOPES' => array()
						);
					}
				}

				$permissionSets[] = &$permissionSet;
				unset($permissionSet);
			}
			else
			{
				$permissionSet = self::RegisterPermissionSet($permissionSets, $permissionSet);
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					//HACK: for OPENED ONLY mode - allow user own entities too.
					$userAttr = isset($allAttrs['USER']) && is_array($allAttrs['USER']) && !empty($allAttrs['USER']) ? $allAttrs['USER'][0] : '';
					if($userAttr !== '')
					{
						self::RegisterPermissionSet(
							$permissionSets,
							array(
								'USER' => "'{$userAttr}'",
								'CONCERNED_USER' => "'C{$userAttr}'",
								'DEPARTMENTS' => array(),
								'OPENED_ONLY' => '',
								'SCOPES' => $permissionSet['SCOPES']
							)
						);
					}
				}
			}
		}
		unset($attrs);

		$isRestricted = false;
		$subQueries = array();

		$effectiveEntityIDs = array();
		if(is_array($restrictByIDs))
		{
			foreach($restrictByIDs as $entityID)
			{
				if($entityID > 0)
				{
					$effectiveEntityIDs[] = (int)$entityID;
				}
			}
		}

		foreach($permissionSets as &$permissionSet)
		{
			$scopes = $permissionSet['SCOPES'];
			$scopeQty = count($scopes);
			if($scopeQty === 0)
			{
				$restrictions = array();
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					$attr = $permissionSet['OPENED_ONLY'];
					$restrictions[] = "{$sAliasPrefix}P.ATTR = {$attr}";
				}
				elseif($permissionSet['USER'] !== '')
				{
					$restrictions[] = $sAliasPrefix.'P.ATTR = '.$permissionSet['USER'];
					if($permissionSet['CONCERNED_USER'] !== '')
					{
						$restrictions[] = $sAliasPrefix.'P.ATTR = '.$permissionSet['CONCERNED_USER'];
					}
				}
				elseif(!empty($permissionSet['DEPARTMENTS']))
				{
					$departments = $permissionSet['DEPARTMENTS'];
					$restrictions[] = count($departments) > 1
						? $sAliasPrefix.'P.ATTR IN('.implode(', ', $departments).')'
						: $sAliasPrefix.'P.ATTR = '.$departments[0];
				}

				if(!empty($restrictions))
				{
					foreach($restrictions as $restriction)
					{
						$subQuery = "SELECT {$sAliasPrefix}P.ENTITY_ID FROM b_crm_entity_perms {$sAliasPrefix}P WHERE {$sAliasPrefix}P.ENTITY = '{$permEntity}' AND {$restriction}";
						if(!empty($effectiveEntityIDs))
						{
							$subQuery .= " AND {$sAliasPrefix}P.ENTITY_ID IN (".implode(', ', $effectiveEntityIDs).")";
						}
						$subQueries[] = $subQuery;
					}

					if(!$isRestricted)
					{
						$isRestricted = true;
					}
				}
			}
			else
			{
				$scopeSql = $scopeQty > 1
					? $sAliasPrefix.'P2.ATTR IN ('.implode(', ', $scopes).')'
					: $sAliasPrefix.'P2.ATTR = '.$scopes[0];

				$restrictions = array();
				if($permissionSet['OPENED_ONLY'] !== '')
				{
					$attr = $permissionSet['OPENED_ONLY'];
					$restrictions[] = "{$sAliasPrefix}P1.ATTR = {$attr}";
				}
				elseif($permissionSet['USER'] !== '')
				{
					$restrictions[] = $sAliasPrefix.'P1.ATTR = '.$permissionSet['USER'];
					if($permissionSet['CONCERNED_USER'] !== '')
					{
						$restrictions[] = $sAliasPrefix.'P1.ATTR = '.$permissionSet['CONCERNED_USER'];
					}
				}
				elseif(!empty($permissionSet['DEPARTMENTS']))
				{
					$departments = $permissionSet['DEPARTMENTS'];
					$restrictions[] = count($departments) > 1
						? $sAliasPrefix.'P1.ATTR IN('.implode(', ', $departments).')'
						: $sAliasPrefix.'P1.ATTR = '.$departments[0];
				}

				if(!empty($restrictions))
				{
					foreach($restrictions as $restriction)
					{
						$subQuery = "SELECT {$sAliasPrefix}P2.ENTITY_ID FROM b_crm_entity_perms {$sAliasPrefix}P1 INNER JOIN b_crm_entity_perms {$sAliasPrefix}P2 ON {$sAliasPrefix}P1.ENTITY = '{$permEntity}' AND {$sAliasPrefix}P2.ENTITY = '{$permEntity}' AND {$sAliasPrefix}P1.ENTITY_ID = {$sAliasPrefix}P2.ENTITY_ID AND {$restriction} AND {$scopeSql}";
						if(!empty($effectiveEntityIDs))
						{
							$subQuery .= " AND {$sAliasPrefix}P2.ENTITY_ID IN (".implode(',', $effectiveEntityIDs).")";
						}
						$subQueries[] = $subQuery;
					}
				}
				else
				{
					$subQuery = "SELECT {$sAliasPrefix}P2.ENTITY_ID FROM b_crm_entity_perms {$sAliasPrefix}P2 WHERE {$sAliasPrefix}P2.ENTITY = '{$permEntity}' AND {$scopeSql}";
					if(!empty($effectiveEntityIDs))
					{
						$subQuery .= " AND {$sAliasPrefix}P2.ENTITY_ID IN (".implode(',', $effectiveEntityIDs).")";
					}
					$subQueries[] = $subQuery;
				}

				if(!$isRestricted)
				{
					$isRestricted = true;
				}
			}
		}
		unset($permissionSet);

		if(!$isRestricted)
		{
			return '';
		}

		if(isset($arOptions['READ_ALL']) && $arOptions['READ_ALL'] === true)
		{
			//Add permission 'Read allowed to Everyone' permission
			$readAll = self::ATTR_READ_ALL;
			$subQuery = "SELECT {$sAliasPrefix}P.ENTITY_ID FROM b_crm_entity_perms {$sAliasPrefix}P WHERE {$sAliasPrefix}P.ENTITY = '{$permEntity}' AND {$sAliasPrefix}P.ATTR = '{$readAll}'";
			if(!empty($effectiveEntityIDs))
			{
				$subQuery .= " AND {$sAliasPrefix}P.ENTITY_ID IN (".implode(',', $effectiveEntityIDs).")";
			}
			$subQueries[] = $subQuery;
		}

		$sqlUnion = isset($arOptions['PERMISSION_SQL_UNION']) && $arOptions['PERMISSION_SQL_UNION'] === 'DISTINCT' ? 'DISTINCT' : 'ALL';
		$subQuerySql = implode($sqlUnion === 'DISTINCT' ? ' UNION ' : ' UNION ALL ', $subQueries);
		//BAD SOLUTION IF USER HAVE A LOT OF RECORDS IN B_CRM_ENTITY_PERMS TABLE.
		//$subQuerySql = "SELECT {$sAliasPrefix}PX.ENTITY_ID FROM({$subQuerySql}) {$sAliasPrefix}PX ORDER BY {$sAliasPrefix}PX.ENTITY_ID ASC";

		if(isset($arOptions['RAW_QUERY']) && ($arOptions['RAW_QUERY'] === true || is_array($arOptions['RAW_QUERY'])))
		{
			if(is_array($arOptions['RAW_QUERY']) && isset($arOptions['RAW_QUERY']['TOP']) && $arOptions['RAW_QUERY']['TOP'] > 0)
			{
				$order = isset($arOptions['RAW_QUERY']['SORT_TYPE'])
					&& mb_strtoupper($arOptions['RAW_QUERY']['SORT_TYPE']) === 'DESC'
					? 'DESC' : 'ASC';;

				$subQuerySql = \Bitrix\Main\Application::getConnection()->getSqlHelper()->getTopSql(
					"{$subQuerySql} ORDER BY ENTITY_ID {$order}",
					$arOptions['RAW_QUERY']['TOP']
				);
			}
			return $subQuerySql;
		}

		$identityCol = 'ID';
		if(is_array($arOptions)
			&& isset($arOptions['IDENTITY_COLUMN'])
			&& is_string($arOptions['IDENTITY_COLUMN'])
			&& $arOptions['IDENTITY_COLUMN'] !== '')
		{
			$identityCol = $arOptions['IDENTITY_COLUMN'];
		}

		$sqlType = isset($arOptions['PERMISSION_SQL_TYPE']) && $arOptions['PERMISSION_SQL_TYPE'] === 'FROM' ? 'FROM' : 'WHERE';
		if($sqlType === 'WHERE')
		{
			return "{$sAliasPrefix}.{$identityCol} IN ({$subQuerySql})";
		}

		return "INNER JOIN ({$subQuerySql}) {$sAliasPrefix}GP ON {$sAliasPrefix}.{$identityCol} = {$sAliasPrefix}GP.ENTITY_ID";
	}

	static public function DeleteEntityAttr($entityType, $entityID)
	{
		global $DB;

		$entityType = mb_strtoupper($entityType);
		$entityID = intval($entityID);

		$entityType = $DB->ForSql($entityType);
		$query = "DELETE FROM b_crm_entity_perms WHERE ENTITY = '{$entityType}' AND ENTITY_ID = {$entityID}";
		$DB->Query($query, false, $query.'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}

	static public function GetEntityAttr($permEntity, $arIDs)
	{
		if (!is_array($arIDs))
		{
			$arIDs = array($arIDs);
		}

		$effectiveEntityIDs = array();
		foreach ($arIDs as $entityID)
		{
			if($entityID > 0)
			{
				$effectiveEntityIDs[] = (int)$entityID;
			}
		}

		$arResult = array();
		$entityPrefix = mb_strtoupper($permEntity);
		$missedEntityIDs = array();
		foreach($effectiveEntityIDs as $entityID)
		{
			$entityKey = "{$entityPrefix}_{$entityID}";
			if(isset(self::$ENTITY_ATTRS[$entityKey]))
			{
				$arResult[$entityID] = self::$ENTITY_ATTRS[$entityKey];
			}
			else
			{
				$missedEntityIDs[] = $entityID;
			}
		}

		if(empty($missedEntityIDs))
		{
			return $arResult;
		}

		global $DB;
		$sqlIDs = implode(',', $missedEntityIDs);
		$obRes = $DB->Query(
			"SELECT ENTITY_ID, ATTR FROM b_crm_entity_perms WHERE ENTITY = '{$DB->ForSql($permEntity)}' AND ENTITY_ID IN({$sqlIDs})",
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRow = $obRes->Fetch())
		{
			$entityID = $arRow['ENTITY_ID'];
			$entityAttr = $arRow['ATTR'];
			$arResult[$entityID][] = $entityAttr;

			$entityKey = "{$entityPrefix}_{$entityID}";
			if(!isset(self::$ENTITY_ATTRS[$entityKey]))
			{
				self::$ENTITY_ATTRS[$entityKey] = array();
			}
			self::$ENTITY_ATTRS[$entityKey][] = $entityAttr;
		}
		return $arResult;
	}
	static public function UpdateEntityAttr($entityType, $entityID, $arAttrs = array())
	{
		global $DB;
		$entityID = intval($entityID);
		$entityType = mb_strtoupper($entityType);

		if(!is_array($arAttrs))
		{
			$arAttrs = array();
		}

		/*if(!is_array($arOptions))
		{
			$arOptions = array();
		}*/

		$key = "{$entityType}_{$entityID}";
		if(isset(self::$ENTITY_ATTRS[$key]))
		{
			unset(self::$ENTITY_ATTRS[$key]);
		}

		$entityType = $DB->ForSql($entityType);
		$sQuery = "DELETE FROM b_crm_entity_perms WHERE ENTITY = '{$entityType}' AND ENTITY_ID = {$entityID}";
		$DB->Query($sQuery, false, $sQuery.'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		if (!empty($arAttrs))
		{
			foreach ($arAttrs as $sAttr)
			{
				$sQuery = "INSERT INTO b_crm_entity_perms(ENTITY, ENTITY_ID, ATTR) VALUES ('{$entityType}', {$entityID}, '".$DB->ForSql($sAttr)."')";
				$DB->Query($sQuery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			}
		}
	}

	public static function ResolvePermissionEntityType($entityType, $entityID, array $parameters = null)
	{
		if(!is_integer($entityID))
		{
			$entityID = (int)$entityID;
		}

		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !$factory->isCategoriesSupported())
		{
			return $entityType;
		}

		$categoryID = is_array($parameters) && isset($parameters['CATEGORY_ID'])
			? (int)$parameters['CATEGORY_ID'] : -1;

		if($categoryID < 0 && $entityID > 0)
		{
			if ($factory instanceof \Bitrix\Crm\Service\Factory\Deal)
			{
				//todo temporary decision while Deal Factory does not support work with items.
				$categoryID = CCrmDeal::GetCategoryID($entityID);
			}
			else
			{
				$items = $factory->getItems([
					'select' => [\Bitrix\Crm\Item::FIELD_NAME_CATEGORY_ID],
					'filter' => [
						'=ID' => $entityID
					],
					'limit' => 1,
				]);
				if (isset($items[0]))
				{
					$categoryID = $items[0]->getCategoryId();
				}
			}
		}

		return \Bitrix\Crm\Service\UserPermissions::getPermissionEntityType($entityTypeId, $categoryID);
	}

	public static function HasPermissionEntityType($permissionEntityType)
	{
		if(DealCategory::hasPermissionEntity($permissionEntityType))
		{
			return true;
		}

		$entityTypeID = CCrmOwnerType::ResolveID($permissionEntityType);
		return ($entityTypeID !== CCrmOwnerType::Undefined && $entityTypeID !== CCrmOwnerType::System);
	}

	public static function ResolveEntityTypeName($permissionEntityType)
	{
		if(DealCategory::hasPermissionEntity($permissionEntityType))
		{
			return CCrmOwnerType::DealName;
		}

		return \Bitrix\Crm\Service\UserPermissions::getEntityNameByPermissionEntityType($permissionEntityType)
			?? $permissionEntityType
		;
	}
}
