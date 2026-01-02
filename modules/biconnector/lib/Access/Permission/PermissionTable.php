<?php

namespace Bitrix\BIConnector\Access\Permission;

use Bitrix\BIConnector\Access\Role\RoleTable;
use Bitrix\Main\Access\Permission\AccessPermissionTable;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class PermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Permission_Query query()
 * @method static EO_Permission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Permission_Result getById($id)
 * @method static EO_Permission_Result getList(array $parameters = [])
 * @method static EO_Permission_Entity getEntity()
 * @method static \Bitrix\BIConnector\Access\Permission\Permission createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Access\Permission\PermissionCollection createCollection()
 * @method static \Bitrix\BIConnector\Access\Permission\Permission wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Access\Permission\PermissionCollection wakeUpCollection($rows)
 */
final class PermissionTable extends AccessPermissionTable
{
	public static function getTableName(): string
	{
		return 'b_biconnector_permission';
	}

	public static function getObjectClass(): string
	{
		return Permission::class;
	}

	public static function getCollectionClass(): string
	{
		return PermissionCollection::class;
	}

	public static function getMap(): array
	{
		$map = parent::getMap();

		$map['ROLE'] = new Reference(
			'ROLE',
			RoleTable::class,
			Join::on('this.ROLE_ID', 'ref.ID')
		);

		return $map;
	}
}
