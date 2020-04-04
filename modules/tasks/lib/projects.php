<?php
namespace Bitrix\Tasks;

use \Bitrix\Main\Entity;

class ProjectsTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_projects';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'ORDER_NEW_TASK' => new Entity\StringField('ORDER_NEW_TASK', array(
				'required' => true
			))
		);
	}

	/**
	 * Set project settings.
	 * @param int $id Project id.
	 * @param array $fields Settings array.
	 * @return void
	 */
	public static function set($id, $fields)
	{
		if (self::getById($id)->fetch())
		{
			self::update($id, $fields);
		}
		else
		{
			$fields['ID'] = $id;
			self::add($fields);
		}
	}

	/**
	 * Delete all rows after group delete.
	 * @param int $groupId Group id.
	 * @return void
	 */
	public static function onSocNetGroupDelete($groupId)
	{
		self::delete($groupId);
	}
}