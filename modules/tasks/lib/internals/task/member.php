<?
/**
 * Class MemberTable
 *
 * @package Bitrix\Tasks
 **/

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class MemberTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_member';
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
			'TYPE' => array(
				'data_type' => 'string',
				'primary' => true,
				'validation' => array(__CLASS__, 'validateType'),
			),

			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\Internals\Task',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
			'TASK_FOLLOWED' => array(
				'data_type' => 'Task',
				'reference' => array(
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => array('?', 'U')
				)
			),
			'TASK_COWORKED' => array(
				'data_type' => 'Task',
				'reference' => array(
					'=this.TASK_ID' => 'ref.ID',
					'=this.TYPE' => array('?', 'A')
				)
			),
		);
	}
	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
}