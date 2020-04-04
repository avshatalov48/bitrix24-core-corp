<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
//Loc::loadMessages(__FILE__);

class CounterLogTable extends Main\Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_tasks_counters';
	}

	public static function getClass()
	{
		return get_called_class();
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'DATE' => array(
				'data_type' => 'datetime',
				'required' => true,
			),

			'KPI' => array(
				'data_type' => 'integer'
			),
			'OPENED' => array(
				'data_type' => 'integer'
			),
			'CLOSED' => array(
				'data_type' => 'integer'
			),

			'MY_EXPIRED' => array(
				'data_type' => 'integer'
			),
			'MY_EXPIRED_SOON' => array(
				'data_type' => 'integer'
			),
			'MY_NOT_VIEWED' => array(
				'data_type' => 'integer'
			),
			'MY_WITHOUT_DEADLINE' => array(
				'data_type' => 'integer'
			),

			'ORIGINATOR_EXPIRED' => array(
				'data_type' => 'integer'
			),
			'ORIGINATOR_WITHOUT_DEADLINE' => array(
				'data_type' => 'integer'
			),
			'ORIGINATOR_WAIT_CTRL' => array(
				'data_type' => 'integer'
			),

			'AUDITOR_EXPIRED' => array(
				'data_type' => 'integer'
			),

			'ACCOMPLICES_EXPIRED' => array(
				'data_type' => 'integer'
			),
			'ACCOMPLICES_EXPIRED_SOON' => array(
				'data_type' => 'integer'
			),
			'ACCOMPLICES_NOT_VIEWED' => array(
				'data_type' => 'integer'
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