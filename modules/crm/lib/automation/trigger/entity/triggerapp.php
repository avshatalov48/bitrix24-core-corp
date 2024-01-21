<?php
namespace Bitrix\Crm\Automation\Trigger\Entity;

use Bitrix\Main;

/**
 * Class TriggerAppTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_TriggerApp_Query query()
 * @method static EO_TriggerApp_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_TriggerApp_Result getById($id)
 * @method static EO_TriggerApp_Result getList(array $parameters = [])
 * @method static EO_TriggerApp_Entity getEntity()
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\EO_TriggerApp createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\EO_TriggerApp_Collection createCollection()
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\EO_TriggerApp wakeUpObject($row)
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\EO_TriggerApp_Collection wakeUpCollection($rows)
 */
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
			'ID' => array('primary' => true, 'data_type' => 'integer', 'autocomplete' => true,),
			'APP_ID' => array('data_type' => 'integer', 'required' => true),
			'NAME' => array('data_type' => 'string', 'required' => true),
			'CODE' => array('data_type' => 'string', 'required' => true),
			'DATE_CREATE' => array('data_type' => 'datetime', 'required' => true)
		);
	}
}