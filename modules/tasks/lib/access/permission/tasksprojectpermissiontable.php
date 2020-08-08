<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Permission;

use Bitrix\Main\Access\Permission\AccessPermissionTable;
use Bitrix\Main\Entity;

class TasksProjectPermissionTable extends AccessPermissionTable
{
	public static function getTableName()
	{
		return 'b_tasks_project_permission';
	}

	public static function getObjectClass()
	{
		return TasksProjectPermission::class;
	}

	public static function getMap()
	{
		$map = [
			new Entity\IntegerField('GROUP_ID', [
				'required' => true
			])
		];

		return array_merge(parent::getMap(), $map);
	}

	protected static function updateChildPermission($primary, array $data)
	{
		$data = self::loadUpdateRow($primary, $data);
		if ((int) $data['VALUE'] === PermissionDictionary::VALUE_YES)
		{
			return;
		}
		$sql = "
			UPDATE `". static::getTableName() ."` 
			SET VALUE = ". PermissionDictionary::VALUE_NO ."
			WHERE 
				ROLE_ID = ". $data['ROLE_ID'] ."
				AND GROUP_ID = ". $data['GROUP_ID'] ."
				AND PERMISSION_ID LIKE '". $data['PERMISSION_ID'] .".%' 
		";
		static::getEntity()->getConnection()->query($sql);
	}

	protected static function validateRow(array $data): bool
	{
		$parentPermissions = PermissionDictionary::getParentsPath($data['PERMISSION_ID']);
		if (!$parentPermissions)
		{
			return true;
		}

		$res = self::getList([
			'select' => ['VALUE'],
			'filter' => [
				'=GROUP_ID'			=> (int) $data['GROUP_ID'],
				'=ROLE_ID' 			=> (int) $data['ROLE_ID'],
				'%=PERMISSION_ID' 	=> $parentPermissions,
				'=VALUE' 			=> PermissionDictionary::VALUE_NO
			],
			'limit' => 1
		])->fetchAll();

		if (is_array($res) && count($res) > 0)
		{
			return false;
		}

		return true;
	}
}