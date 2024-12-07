<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;

/**
 * Class RolePermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RolePermission_Query query()
 * @method static EO_RolePermission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RolePermission_Result getById($id)
 * @method static EO_RolePermission_Result getList(array $parameters = [])
 * @method static EO_RolePermission_Entity getEntity()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection createCollection()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission wakeUpObject($row)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection wakeUpCollection($rows)
 */
class RolePermissionTable extends DataManager
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
			(new ArrayField('SETTINGS', ["default_value" => ""]))->configureSerializationJson(),
		];
	}

	/**
	 * @param int $roleId
	 * @param PermissionModel[] $permissionModels
	 */
	public static function appendPermissions(int $roleId, array $permissionModels): void
	{
		if (empty($permissionModels))
		{
			return;
		}

		self::removePermissions($roleId, $permissionModels);

		foreach ($permissionModels as $model)
		{
			if (!$model->isValidIdentifier())
			{
				continue;
			}

			RolePermissionTable::add([
				'ROLE_ID' => $roleId,
				'ENTITY' => $model->entity(),
				'FIELD' => $model->field(),
				'FIELD_VALUE' => $model->filedValue(),
				'PERM_TYPE' => $model->permissionCode(),
				'ATTR' => $model->attribute(),
				'SETTINGS' => $model->settings(),
			]);
		}
	}

	/**
	 * @param int $roleId
	 * @param PermissionModel[] $permissionModels
	 * @return void
	 */
	public static function removePermissions(int $roleId, array $permissionModels): void
	{
		if (empty($permissionModels))
		{
			return;
		}

		$entity = RolePermissionTable::getEntity();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		foreach ($permissionModels as $model)
		{
			if (!$model->isValidIdentifier())
			{
				continue;
			}

			$ct = new ConditionTree();
			$ct
				->where('ROLE_ID', $roleId)
				->where('ENTITY', $model->entity())
				->where('FIELD_VALUE', $model->filedValue())
				->where('FIELD', $model->field())
				->where('PERM_TYPE', $model->permissionCode());

			$sql = sprintf(
				'DELETE FROM %s WHERE %s;',
				$sqlHelper->quote(self::getTableName()),
				Query::buildFilterSql($entity, $ct)
			);

			static::cleanCache();

			$connection->queryExecute($sql);
		}
	}
}
