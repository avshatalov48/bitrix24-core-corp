<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Main;
use Bitrix\Crm\CategoryIdentifier;

class CCrmRole
{
	protected $cdb = null;

	private const CACHE_TIME = 8640000; // 100 days
	private const CACHE_PATH = '/crm/user_permission_roles/';

	function __construct()
	{
		global $DB;

		$this->cdb = $DB;
	}

	static public function GetList($arOrder = Array('ID' => 'DESC'), $arFilter = Array())
	{
		global $DB;

		// where
		$arWhereFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'NAME' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'IS_SYSTEM' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.IS_SYSTEM',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CODE' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.CODE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'GROUP_CODE' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.GROUP_CODE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
		);

		$obQueryWhere = new CSQLWhere();
		$obQueryWhere->SetFields($arWhereFields);
		if(!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		if(!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('ID' => 'DESC');
		foreach ($arOrder as $by => $order)
		{
			$by = mb_strtoupper($by);
			$order = mb_strtolower($order);
			if($order != 'asc')
				$order = 'desc';

			if(isset($arWhereFields[$by]))
				$arSqlOrder[$by] = " R.$by $order ";
			else
			{
				$by = 'id';
				$arSqlOrder[$by] = " R.ID $order ";
			}
		}

		if (count($arSqlOrder) > 0)
			$sSqlOrder = "\n\t\t\t\tORDER BY ".implode(', ', $arSqlOrder);
		else
			$sSqlOrder = '';

		$sSql = "
			SELECT
				ID, NAME, IS_SYSTEM, CODE, GROUP_CODE
			FROM
				b_crm_role R
			WHERE
				1=1 $sSqlSearch
			$sSqlOrder";

		$obRes = $DB->Query($sSql);
		return $obRes;
	}

	static public function GetRelation()
	{
		global $DB;
		$sSql = '
			SELECT RR.* FROM b_crm_role R, b_crm_role_relation RR
			WHERE R.ID = RR.ROLE_ID
			ORDER BY R.ID asc';
		$obRes = $DB->Query($sSql);
		return $obRes;
	}

	public function SetRelation($arRelation, $ignoreSystem = true)
	{
		$this->log('SetRelation', $arRelation);
		global $DB;

		$sSql = $ignoreSystem
			? 'DELETE FROM b_crm_role_relation WHERE ROLE_ID IN (SELECT ID FROM b_crm_role WHERE IS_SYSTEM != \'Y\')'
			: 'DELETE FROM b_crm_role_relation'
		;

		$DB->Query($sSql);
		foreach ($arRelation as $sRel => $arRole)
		{
			foreach ($arRole as $iRoleID)
			{
				$arFields = array(
					'ROLE_ID' => (int)$iRoleID,
					'RELATION' => $DB->ForSql($sRel)
				);
				$DB->Add('b_crm_role_relation', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			}
		}

		self::ClearCache();
	}

	/**
	 * @deprecated Currently forbidden to save role with relations, retrieved with this method due to data loss in SETTINGS field
	 * @see \CCrmRole::getRolePermissionsAndSettings
	 *
	 * @param $ID
	 * @return array
	 */
	public static function GetRolePerms($ID)
	{
		global $DB;
		$ID = (int)$ID;
		$sSql = 'SELECT * FROM b_crm_role_perms WHERE role_id = '.$ID;
		$obRes = $DB->Query($sSql);
		$_arResult = array();
		while ($arRow = $obRes->Fetch())
		{
			if (!isset($arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']]))
				if ($arRow['FIELD'] != '-')
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']] = trim($arRow['ATTR']);
				else
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']] = trim($arRow['ATTR']);
		}
		return $_arResult;
	}

	public static function getRolePermissionsAndSettings(int $id): array
	{
		$itemsIterator = RolePermissionTable::query()
			->setSelect(['*'])
			->where('ROLE_ID', $id)
			->exec()
		;

		$result = [];
		while ($item = $itemsIterator->fetch())
		{
			$attr = ($item['ATTR'] == '') ? null : trim($item['ATTR']);
			$settings = empty($item['SETTINGS']) ? null : $item['SETTINGS'];

			$value = [
				'ATTR' => $attr,
				'SETTINGS' => $settings,
			];

			if ($item['FIELD'] != '-')
			{
				$result[$item['ENTITY']][$item['PERM_TYPE']][$item['FIELD']][$item['FIELD_VALUE']] = $value;
			}
			else
			{
				$result[$item['ENTITY']][$item['PERM_TYPE']][$item['FIELD']] = $value;
			}
		}
		return $result;
	}

	// BX_CRM_PERM_NONE  - not supported
	static public function GetRoleByAttr($permEntity, $permAttr = CCrmPerms::PERM_SELF, $permType = 'READ')
	{
		$dbRes = RolePermissionTable::getList([
			'select' => [
				'ROLE_ID',
			],
			'filter' => [
				'=ENTITY' => (string)$permEntity,
				'=PERM_TYPE' => (string)$permType,
				'>=ATTR' => (string)$permAttr,
			],
			'cache' => [
				'ttl' => 84600,
			],
		]);
		$result = [];
		while ($row = $dbRes->fetch())
		{
			$result[] = $row['ROLE_ID'];
		}

		return $result;
	}

	static public function GetCalculateRolePermsByRelation($arRel)
	{
		global $DB;
		static $arResult = array();

		if (empty($arRel))
			return $arRel;

		foreach ($arRel as &$sRel)
			$sRel = $DB->ForSql(mb_strtoupper($sRel));
		$sin = implode("','", $arRel);

		if (isset($arResult[$sin]))
			return $arResult[$sin];

		$sSql = "
			SELECT RP.*
			FROM b_crm_role_perms RP, b_crm_role_relation RR
			WHERE RP.ROLE_ID = RR.ROLE_ID AND RR.RELATION IN('$sin')";
		$obRes = $DB->Query($sSql);
		$_arResult = array();
		while ($arRow = $obRes->Fetch())
		{
			$arRow['ATTR'] = trim($arRow['ATTR']);
			if ($arRow['FIELD'] == '-')
			{
				if (!isset($_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']])
					|| $arRow['ATTR'] > $_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']])
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']] = $arRow['ATTR'];
			}
			else
				if (!isset($_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']])
					|| $arRow['ATTR'] > $_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']])
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']] = $arRow['ATTR'];
		}
		$arResult[$sin] = $_arResult;
		return $_arResult;
	}

	static public function GetUserPerms($userId)
	{
		$userId = intval($userId);
		if($userId <= 0)
		{
			return [];
		}

		static $memoryCache = [];
		if (isset($memoryCache[$userId]))
		{
			return $memoryCache[$userId];
		}

		$userAccessCodes = \Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->getAttributesProvider()
			->getUserAttributesCodes()
		;

		$cache = Main\Application::getInstance()->getCache();
		$cacheId = 'crm_user_permission_roles_' . $userId . '_' . md5(serialize($userAccessCodes));

		if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_PATH))
		{
			$roles = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$roles = [];

			if (!empty($userAccessCodes))
			{
				$rolesRelations = RoleRelationTable::getList([
					'filter' => [
						'@RELATION' => $userAccessCodes,
					],
					'select' => [
						'ROLE_ID'
					]
				]);
				while ($roleRelation = $rolesRelations->fetch())
				{
					$roles[] = $roleRelation['ROLE_ID'];
				}
			}
			$cache->endDataCache($roles);
		}

		$result = RolePermission::getPermissionsByRoles($roles);
		$memoryCache[$userId] = $result;

		return $result;
	}

	public static function ClearCache()
	{
		// Clean up cached permissions
		Main\Application::getInstance()->getCache()->cleanDir(self::CACHE_PATH);
		RolePermissionTable::getEntity()->cleanCache();

		CrmClearMenuCache();
	}

	public function Add(&$arFields)
	{
		global $DB;

		$this->LAST_ERROR = '';
		$result = true;
		if(!$this->CheckFields($arFields))
		{
			$result = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if (!isset($arFields['RELATION']) || !is_array($arFields['RELATION']))
				$arFields['RELATION'] = array();
			$ID = (int)$DB->Add('b_crm_role', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$this->SetRoleRelation($ID, $arFields['RELATION']);
			$result = $arFields['ID'] = $ID;
		}
		return $result;
	}

	protected function SetRoleRelation($ID, $arRelation)
	{
		global $DB;
		$ID = (int)$ID;

		$existedRelations = RolePermissionTable::query()->where('ROLE_ID', $ID)->setSelect(['*'])->exec()->fetchAll();
		$relationComparer = new \Bitrix\Crm\Security\Role\RolePermissionComparer($existedRelations, $arRelation);

		\Bitrix\Crm\Security\Role\Repositories\PermissionRepository::getInstance()->applyRolePermissionData(
			$ID,
			$relationComparer->getValuesToDelete(),
			$relationComparer->getValuesToAdd()
		);

		$this->log('SetRoleRelation', ['ID' => $ID, 'RELATION' => $arRelation]);

		self::ClearCache();
	}

	public function Update($ID, &$arFields)
	{
		global $DB;

		$ID = (int)$ID;
		$this->LAST_ERROR = '';
		$bResult = true;
		if(!$this->CheckFields($arFields, $ID))
		{
			$bResult = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if (!isset($arFields['RELATION']) || !is_array($arFields['RELATION']))
				$arFields['RELATION'] = array();
			$sUpdate = $DB->PrepareUpdate('b_crm_role', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if ($sUpdate <> '')
				$DB->Query("UPDATE b_crm_role SET $sUpdate WHERE ID = $ID");

			$this->SetRoleRelation($ID, $arFields['RELATION']);
			$arFields['ID'] = $ID;
		}

		return $bResult;
	}

	public function Delete($ID)
	{
		$this->log('Delete', ['ID' => $ID]);
		global $DB;
		$ID = (int)$ID;
		$sSql = 'DELETE FROM b_crm_role_relation WHERE ROLE_ID = '.$ID;
		$DB->Query($sSql);
		$sSql = 'DELETE FROM b_crm_role_perms WHERE ROLE_ID = '.$ID;
		$DB->Query($sSql);
		$sSql = 'DELETE FROM b_crm_role WHERE ID = '.$ID;
		$DB->Query($sSql);

		self::ClearCache();
	}

	public function CheckFields(&$arFields, $ID = false)
	{
		$this->LAST_ERROR = '';
		if (($ID == false || isset($arFields['NAME'])) && empty($arFields['NAME']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_NAME')))."<br />";

		if($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	public static function EraseEntityPermissons($entity)
	{
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$entity = $helper->forSql($entity);
		(new self())->log('EraseEntityPermissons', ['Entity' => $entity]);
		$connection->queryExecute("DELETE FROM b_crm_role_perms WHERE ENTITY = '{$entity}'");
		self::ClearCache();
	}

	/**
	 * @deprecated Method doesn't contain complete data. To avoid losing some default permissions use CCrmRole::getDefaultPermissionSetForEntity
	 * @see CCrmRole::getDefaultPermissionSetForEntity
	 */
	public static function GetDefaultPermissionSet(): array
	{
		return [
			'READ' => ['-' => 'X'],
			'EXPORT' => ['-' => 'X'],
			'IMPORT' => ['-' => 'X'],
			'ADD' => ['-' => 'X'],
			'WRITE' => ['-' => 'X'],
			'DELETE' => ['-' => 'X'],
		];
	}

	/**
	 * Permissions that must be set for new ($entityTypeId + $categoryId) by default
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getBasePermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			fn(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission) => $permission->getDefaultAttribute(),
			fn(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission) => $permission->getDefaultSettings()
		);
	}

	/**
	 * Maximal permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getMaxPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			fn(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission) => $permission->getMaxAttributeValue(),
			fn(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission) => $permission->getMaxSettingsValue()
		);
	}

	/**
	 * Minimal permissions that can be set for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getMinPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return self::getPermissionSetForEntityByCondition(
			$categoryIdentifier->getPermissionEntityCode(),
			fn(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission) => $permission->getMinAttributeValue(),
			fn(\Bitrix\Crm\Security\Role\Manage\Permissions\Permission $permission) => $permission->getMinSettingsValue()
		);
	}

	/**
	 * Typical default permissions for ($entityTypeId + $categoryId)
	 *
	 * @param CategoryIdentifier $categoryIdentifier
	 * @return array
	 */
	public static function getDefaultPermissionSetForEntity(CategoryIdentifier $categoryIdentifier): array
	{
		return array_merge(
			self::GetDefaultPermissionSet(),
			self::getBasePermissionSetForEntity($categoryIdentifier)
		);
	}

	public static function normalizePermissions(array $permissions): array
	{
		foreach ($permissions as $entityTypeName => $entityPermissions)
		{
			if (!is_array($entityPermissions))
			{
				$entityPermissions = [];
				$permissions[$entityTypeName] = [];
			}

			foreach ($entityPermissions as $permissionType => $permissionsForType)
			{
				if (!is_array($permissionsForType))
				{
					$permissionsForType = [];
					$permissions[$entityTypeName][$permissionType] = [];
				}

				$defaultPermissionValue = '-';
				foreach ($permissionsForType as $fieldName => $permissionValue)
				{
					if ($fieldName === '-') // default permission
					{
						$defaultPermissionValue = trim($permissionValue);
					}
				}
				foreach ($permissionsForType as $fieldName => $permissionValues)
				{
					if ($fieldName !== '-')
					{
						if (!is_array($permissionValues))
						{
							$permissionValues = [];
							$permissions[$entityTypeName][$permissionType][$fieldName] = [];
						}
						foreach ($permissionValues as $fieldValue => $permissionValue)
						{
							if (trim($permissionValue) === $defaultPermissionValue)
							{
								// if permission for this field value equals to default permission, use inheritance:
								$permissions[$entityTypeName][$permissionType][$fieldName][$fieldValue] = '-';
							}
						}
					}
				}
			}
		}
		return $permissions;
	}

	public function GetLastError(): string
	{
		return $this->LAST_ERROR ?? '';
	}

	/**
	 * @internal
	 */
	protected function log(string $event, $extraData): void
	{
		if (Main\Config\Option::get('crm', '~CRM_LOG_PERMISSION_ROLE_CHANGES', 'N') !== 'Y')
		{
			return;
		}
		$logData = 'CRM_LOG_PERMISSION_ROLE_CHANGES: ' . $event . "\n";
		$logData .= 'User: ' . \CCrmSecurityHelper::GetCurrentUserID();
		if (!empty($extraData))
		{
			$logData .= "\n" . print_r($extraData, true);
		}
		AddMessage2Log($logData, 'crm', 10);
	}

	private static function getPermissionSetForEntityByCondition(string $permissionEntityCode, callable $getAttrValueCallback, callable $getSettingsValueCallback): array
	{
		$permissionSet = [];
		$permissionEntities = \Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder::getInstance()->buildModels();
		foreach ($permissionEntities as $permissionEntity)
		{
			if ($permissionEntityCode === $permissionEntity->code())
			{
				foreach ($permissionEntity->permissions() as $permission)
				{
					$defaultAttr = $getAttrValueCallback($permission);
					$defaultSettings = $getSettingsValueCallback($permission);
					$permissionCode = $permission->code();
					if (!is_null($defaultAttr) || !empty($defaultSettings))
					{
						if (!isset($permissionSet[$permissionCode]))
						{
							$permissionSet[$permissionCode] = [
								'-' => []
							];
						}
						$permissionSet[$permissionCode]['-']['ATTR'] = $defaultAttr;
						$permissionSet[$permissionCode]['-']['SETTINGS'] = empty($defaultSettings) ? null : $defaultSettings;
					}
				}

				break;
			}
		}

		return $permissionSet;
	}
}
