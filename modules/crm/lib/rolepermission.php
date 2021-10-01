<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

Loc::loadMessages(__FILE__);

/**
 * Class RolePermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RolePermission_Query query()
 * @method static EO_RolePermission_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_RolePermission_Result getById($id)
 * @method static EO_RolePermission_Result getList(array $parameters = array())
 * @method static EO_RolePermission_Entity getEntity()
 * @method static \Bitrix\Crm\EO_RolePermission createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_RolePermission_Collection createCollection()
 * @method static \Bitrix\Crm\EO_RolePermission wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_RolePermission_Collection wakeUpCollection($rows)
 */
class RolePermissionTable extends Main\ORM\Data\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_role_perms';
	}

	public static function getMap()
	{
		return [
			(new IntegerField("ID", ["primary" => true, "autocomplete" => true])),
			(new IntegerField("ROLE_ID", ["required" => true])),
			(new StringField("ENTITY", ["required" => true, "size" => 20])),
			(new StringField("FIELD", ["size" => 30, "default" => "-"])),
			(new StringField("FIELD_VALUE", ["size" => 255])),
			(new StringField("PERM_TYPE", ["required" => true, "size" => 20])),
			(new StringField("ATTR", ["size" => 1, "default" => ""])),
		];
	}
}

class RolePermission {
	private static $cache = null;

	public static function getAll()
	{
		if (static::$cache !== null)
		{
			return static::$cache;
		}

		$dbRes = RolePermissionTable::getList([
			"select" => ["*"],
			"filter" => [],
			"cache" => [
//				"ttl" => 84600
			]
		]);
		$result = [];
		while ($res = $dbRes->fetch())
		{
			if (!array_key_exists($res["ROLE_ID"], $result))
			{
				$result[$res["ROLE_ID"]] = [];
			}
			$role = &$result[$res["ROLE_ID"]];
			if (!array_key_exists($res["ENTITY"], $role))
			{
				$role[$res["ENTITY"]] = [];
			}
			if (!array_key_exists($res["PERM_TYPE"], $role[$res["ENTITY"]]))
			{
				$role[$res["ENTITY"]][$res["PERM_TYPE"]] = [
					$res["FIELD"] => (
					$res["FIELD"] != '-' ?
						[$res["FIELD_VALUE"] => trim($res["ATTR"])] :
						trim($res["ATTR"])
					)
				];
			}
		}
		unset($role);
		return $result;
	}

	/**
	 * @param string $entityId
	 * @return array it is an array like [roleId => ["READ" => ["-" => "X"], ...]]]
	 */
	public static function getByEntityId(string $entityId)
	{
		$result = [];

		foreach (self::getAll() as $roleId => $entities)
		{
			$result[$roleId] =
				array_key_exists($entityId, $entities) ?
					$entities[$entityId] :
					\CCrmRole::GetDefaultPermissionSet()
			;
		}
		return $result;
	}
	/**
	 * Sets a permission from the set for certain roles but one entity
	 *
	 * @param string $entityId
	 * @param array $permissionSet it is an array like [roleId => ["READ" => ["-" => "X"], ...]]]
	 * @return Main\Result
	 */
	public static function setByEntityId(string $entityId, array $permissionSet)
	{
		static::$cache = null;

		$result = new Main\Result();

		$role = new \CCrmRole();
		foreach (self::getAll() as $roleId => $entities)
		{
			if (array_key_exists("CONFIG", $entities) && array_key_exists("WRITE", $entities["CONFIG"]))
			{
				$perms = reset($entities["CONFIG"]["WRITE"]);
				if ($perms >= BX_CRM_PERM_ALL)
				{
					continue;
				}
			}
			if (array_key_exists($roleId, $permissionSet))
			{
				$entities[$entityId] = $permissionSet[$roleId];

				$fields = ["RELATION" => $entities];
				if (!$role->Update($roleId, $fields))
				{
					$result->addError(new Main\Error($fields["RESULT_MESSAGE"]));
				}
			}
		}
		return $result;
	}

	/**
	 * Sets the same permission for all roles but one entity
	 *
	 * @param string $entityId
	 * @param array $permissionSet it is an array like ["READ" => ["-" => "X"], ...]]
	 * @return Main\Result
	 */
	public static function setByEntityIdForAllNotAdminRoles(string $entityId, array $permissionSet)
	{
		static::$cache = null;

		$result = new Main\Result();

		$role = new \CCrmRole();
		foreach (self::getAll() as $roleId => $entities)
		{
			if (array_key_exists("CONFIG", $entities) && array_key_exists("WRITE", $entities["CONFIG"]))
			{
				$perms = reset($entities["CONFIG"]["WRITE"]);
				if ($perms >= BX_CRM_PERM_ALL)
				{
					continue;
				}
			}
			$entities[$entityId] = $permissionSet;

			$fields = ["RELATION" => $entities];
			if (!$role->Update($roleId, $fields))
			{
				$result->addError(new Main\Error($fields["RESULT_MESSAGE"]));
			}
		}
		return $result;
	}
}