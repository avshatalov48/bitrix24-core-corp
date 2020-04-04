<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

/**
 * Class ViolationTable
 *
 * @package Bitrix\Tasks
 **/

class ViolationTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_violation';
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
			'DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'IN_PROGRESS' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'EXPIRED' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'KPI' => array(
				'data_type' => 'integer',
				'required' => true,
			),


			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),

			'GROUP' => array(
				'data_type' => 'Bitrix\Socialnetwork\Workgroup',
				'reference' => array('=this.GROUP_ID' => 'ref.ID')
			),
		);
	}
}