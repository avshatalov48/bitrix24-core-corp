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

/**
 * Class TasksTemplatePermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TasksTemplatePermission_Query query()
 * @method static EO_TasksTemplatePermission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TasksTemplatePermission_Result getById($id)
 * @method static EO_TasksTemplatePermission_Result getList(array $parameters = [])
 * @method static EO_TasksTemplatePermission_Entity getEntity()
 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermission createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection createCollection()
 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermission wakeUpObject($row)
 * @method static \Bitrix\Tasks\Access\Permission\TasksTemplatePermissionCollection wakeUpCollection($rows)
 */
class TasksTemplatePermissionTable extends AccessPermissionTable
{
	public static function getTableName(): string
	{
		return 'b_tasks_template_permission';
	}

	public static function getObjectClass(): string
	{
		return TasksTemplatePermission::class;
	}

	public static function getCollectionClass(): string
	{
		return TasksTemplatePermissionCollection::class;
	}

	public static function getMap()
	{
		return [
			new Entity\IntegerField('ID', [
				'autocomplete' => true,
				'primary' => true,
			]),
			new Entity\IntegerField('TEMPLATE_ID', [
				'required' => true,
			]),
			new Entity\StringField('ACCESS_CODE', [
				'required' => true,
			]),
			new Entity\StringField('PERMISSION_ID', [
				'required' => true,
			]),
			new Entity\IntegerField('VALUE', [
				'required' => true,
			]),
		];
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
				TEMPLATE_ID = ". $data['TEMPLATE_ID'] ."
				AND ACCESS_CODE = ". $data['ACCESS_CODE'] ."
				AND PERMISSION_ID LIKE '". $data['PERMISSION_ID'] .".%' 
		";
		static::getEntity()->getConnection()->query($sql);
	}

	public static function validateRow(array $data): bool
	{
		$parentPermissions = PermissionDictionary::getParentsPath($data['PERMISSION_ID']);
		if (!$parentPermissions)
		{
			return true;
		}

		$res = self::getList([
			'select' => ['VALUE'],
			'filter' => [
				'=TEMPLATE_ID'		=> (int) $data['TEMPLATE_ID'],
				'=ACCESS_CODE' 		=> $data['ACCESS_CODE'],
				'%=PERMISSION_ID' 	=> $parentPermissions,
				'=VALUE' 			=> PermissionDictionary::VALUE_NO,
			],
			'limit' => 1,
		])->fetchAll();

		if (is_array($res) && count($res) > 0)
		{
			return false;
		}

		return true;
	}
}