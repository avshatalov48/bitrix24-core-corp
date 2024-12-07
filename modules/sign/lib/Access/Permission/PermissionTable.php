<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Sign\Access\Permission;

use Bitrix\Main\Access\Permission\AccessPermissionTable;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\MergeTrait;
use Bitrix\Main\SystemException;

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
 * @method static \Bitrix\Sign\Access\Permission\Permission createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Access\Permission\EO_Permission_Collection createCollection()
 * @method static \Bitrix\Sign\Access\Permission\Permission wakeUpObject($row)
 * @method static \Bitrix\Sign\Access\Permission\EO_Permission_Collection wakeUpCollection($rows)
 */
class PermissionTable extends AccessPermissionTable
{
	use MergeTrait;

	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_permission';
	}

	/**
	 * @return array
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true
			]),
			new Entity\IntegerField('ROLE_ID', [
				'required' => true
			]),
			new Entity\StringField('PERMISSION_ID', [
				'required' => true
			]),
			new Entity\StringField('VALUE', [
				'required' => true
			])
		];
	}
	public static function getObjectClass(): string
	{
		return Permission::class;
	}
}