<?
/**
 * Class ChecklistItemsTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class CheckListTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_checklist_items';
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
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TASK_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TOGGLED_BY' => array(
				'data_type' => 'integer',
			),
			'TOGGLED_DATE' => array(
				'data_type' => 'datetime',
			),
			'TITLE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTitle'),
			),
			'IS_COMPLETE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SORT_INDEX' => array(
				'data_type' => 'integer',
				'required' => true,
			),
		);
	}

	/**
	 * Returns validators for TITLE field.
	 *
	 * @return array
	 * @throws Main\ArgumentTypeException
	 */
	public static function validateTitle()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}