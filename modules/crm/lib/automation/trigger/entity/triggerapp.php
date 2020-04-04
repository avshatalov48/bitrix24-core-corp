<?php
namespace Bitrix\Crm\Automation\Trigger\Entity;

use Bitrix\Main;

class TriggerAppTable extends Main\Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_automation_trigger_app';
	}

	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('primary' => true, 'data_type' => 'integer'),
			'APP_ID' => array('data_type' => 'integer', 'required' => true),
			'NAME' => array('data_type' => 'string', 'required' => true),
			'CODE' => array('data_type' => 'string', 'required' => true),
			'DATE_CREATE' => array('data_type' => 'datetime', 'required' => true)
		);
	}
}