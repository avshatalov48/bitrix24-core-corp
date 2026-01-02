<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Feature;
use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Model\RolePermissionTable;
use Bitrix\Crm\Security\Role\Model\RoleRelationTable;
use Bitrix\Crm\Security\Role\RolePermission;
use Bitrix\Main;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;

class CCrmRole
{
	protected $cdb = null;

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
		$logger = \Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions');
		global $DB;

		$sSql = $ignoreSystem
			? 'DELETE FROM b_crm_role_relation WHERE ROLE_ID IN (SELECT ID FROM b_crm_role WHERE IS_SYSTEM != \'Y\')'
			: 'DELETE FROM b_crm_role_relation'
		;

		$DB->Query($sSql);
		$logger->info(
			"Removed all relations",
			RolePermissionLogContext::getInstance()->appendTo([
				'ignoreSystem' => $ignoreSystem ? 'Y' : 'N',
			])
		);
		foreach ($arRelation as $sRel => $arRole)
		{
			foreach ($arRole as $iRoleID)
			{
				$arFields = array(
					'ROLE_ID' => (int)$iRoleID,
					'RELATION' => $DB->ForSql($sRel)
				);
				$DB->Add('b_crm_role_relation', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

				$logger->info(
					"Add relation {RELATION} for role #{ROLE_ID}",
					RolePermissionLogContext::getInstance()->appendTo([
						'ROLE_ID' => $iRoleID,
						'RELATION' => $sRel,
					])
				);
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

	/**
	 * @deprecated Do not use permission attributes directly!
	 */
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
		$roles = \Bitrix\Crm\Security\Role\PermissionsManager::getInstance($userId)->getUserRoles();

		$result = RolePermission::getPermissionsByRoles($roles);
		$memoryCache[$userId] = $result;

		return $result;
	}

	public static function ClearCache(): void
	{
		// Clean up cached permissions
		RoleRelationTable::cleanCache();
		RolePermissionTable::cleanCache();

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
			$arFields['PERMISSIONS'] = $arFields['PERMISSIONS'] ?? $arFields['RELATION'] ?? []; // RELATION key for backward compatibility only!
			$arFields['PERMISSIONS'] = (array)$arFields['PERMISSIONS'];

			$ID = (int)$DB->Add('b_crm_role', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
				"Created role #{roleId} ({roleName})\nPermissions:\n".print_r($arFields['PERMISSIONS'], true),
				RolePermissionLogContext::getInstance()->appendTo([
					'roleId' => $ID,
					'roleName' => $arFields['NAME'],
				])
			);
			$this->setRolePermissions($ID, $arFields['PERMISSIONS']);
			$result = $arFields['ID'] = $ID;
		}
		return $result;
	}

	protected function setRolePermissions(int $roleId, array $permissions): void
	{
		$existedPermissions = RolePermissionTable::query()->where('ROLE_ID', $roleId)->setSelect(['*'])->exec()->fetchAll();
		$permissionsComparer = new \Bitrix\Crm\Security\Role\RolePermissionComparer($existedPermissions, $permissions);

		RolePermissionLogContext::getInstance()->disableOrmEventsLog();

		\Bitrix\Crm\Security\Role\Repositories\PermissionRepository::getInstance()->applyRolePermissionData(
			$roleId,
			$permissionsComparer->getValuesToDelete(),
			$permissionsComparer->getValuesToAdd()
		);
		RolePermissionLogContext::getInstance()->enableOrmEventsLog();

		$this->logRolePermissionsChange(
			$roleId,
			$permissionsComparer->getValuesToDelete(),
			$permissionsComparer->getValuesToAdd()
		);

		$this->log('SetRolePermissions', ['ID' => $roleId, 'PERMISSIONS' => $permissions]);

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
			$sUpdate = $DB->PrepareUpdate('b_crm_role', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if ($sUpdate <> '')
			{
				$DB->Query("UPDATE b_crm_role SET $sUpdate WHERE ID = $ID");

				$fieldsToLog = $arFields;
				unset($fieldsToLog['RELATION']);
				unset($fieldsToLog['PERMISSIONS']);
				$fieldsToLog['ID'] = $ID;
				\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
					"Updated role #{ID}",
					RolePermissionLogContext::getInstance()->appendTo($fieldsToLog)
				);
			}

			$arFields['PERMISSIONS'] = $arFields['PERMISSIONS'] ?? $arFields['RELATION'] ?? []; // RELATION key for backward compatibility only!
			$arFields['PERMISSIONS'] = (array)$arFields['PERMISSIONS'];

			$this->setRolePermissions($ID, $arFields['PERMISSIONS']);
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

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Deleted role #{ID}",
			RolePermissionLogContext::getInstance()->appendTo([
				'ID' => $ID,
			])
		);

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

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Deleted all permissions for entity {entity}",
			RolePermissionLogContext::getInstance()->appendTo([
				'entity' => $entity,
			])
		);

		self::ClearCache();
	}

	public static function EraseEntityPermissionsForNotAdminRoles(string $entity): void
	{
		$entityTypeId = PermissionEntityTypeHelper::extractEntityAndCategoryFromPermissionEntityType($entity)?->getEntityTypeId();
		$adminRoleIds = \Bitrix\Crm\Security\Role\RolePermission::getAdminRolesIds($entityTypeId);

		if (empty($adminRoleIds))
		{
			$adminRoleIds = [0];
		}
		$adminRoleIds = array_map( 'intval', $adminRoleIds);
		$adminRoleIds = implode(',', $adminRoleIds);

		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();
		$entity = $helper->forSql($entity);
		(new self())->log('EraseEntityPermissons for non admin roles', ['Entity' => $entity, 'adminRoleIds' => $adminRoleIds]);

		$connection->queryExecute("DELETE FROM b_crm_role_perms WHERE ENTITY = '{$entity}' AND ROLE_ID NOT IN ($adminRoleIds)");

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Deleted all not admin roles permissions for entity {entity}",
			RolePermissionLogContext::getInstance()->appendTo([
				'adminRoles' => $adminRoleIds,
				'entity' => $entity,
			])
		);

		self::ClearCache();
	}

	/**
	 * @deprecated Method doesn't contain complete data. To avoid losing some default permissions use CCrmRole::getDefaultPermissionSetForEntity
	 * @see \Bitrix\Crm\Security\Role\RolePreset::getDefaultPermissionSetForEntity
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

	/**
	 * @param int $roleId
	 * @param PermissionModel[] $removedPermissions
	 * @param PermissionModel[] $addedPermissions
	 * @return void
	 */
	private function logRolePermissionsChange(int $roleId, array $removedPermissions, array $addedPermissions): void
	{
		array_walk($removedPermissions, fn (PermissionModel $item) => $item->toArray());
		array_walk($addedPermissions, fn (PermissionModel $item) => $item->toArray());

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Permissions changed in role #{roleId}",
			RolePermissionLogContext::getInstance()->appendTo([
				'roleId' => $roleId,
				'removedItems' => $removedPermissions,
				'addedItems' => $addedPermissions,
			])
		);
	}
}
