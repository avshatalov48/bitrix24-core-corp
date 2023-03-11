<?php
/**
 * Class TagTable
 * @deprecated since tasks 22.1400.0
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main;
use Bitrix\Tasks\Internals\TaskDataManager;

/**
 * Class TagTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Tag_Query query()
 * @method static EO_Tag_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Tag_Result getById($id)
 * @method static EO_Tag_Result getList(array $parameters = [])
 * @method static EO_Tag_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Tag_Collection wakeUpCollection($rows)
 */
class TagTable extends TaskDataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_tag';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateName'),
			),
			'CONVERTED' => array(
				'data_type' => 'string',
				'default' => 0,
			),

			// references
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\Internals\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}