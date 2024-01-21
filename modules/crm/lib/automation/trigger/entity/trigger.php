<?php
namespace Bitrix\Crm\Automation\Trigger\Entity;

use Bitrix\Main;

/**
 * Class TriggerTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Trigger_Query query()
 * @method static EO_Trigger_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Trigger_Result getById($id)
 * @method static EO_Trigger_Result getList(array $parameters = [])
 * @method static EO_Trigger_Entity getEntity()
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\TriggerObject createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\EO_Trigger_Collection createCollection()
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\TriggerObject wakeUpObject($row)
 * @method static \Bitrix\Crm\Automation\Trigger\Entity\EO_Trigger_Collection wakeUpCollection($rows)
 */
class TriggerTable extends Main\Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_automation_trigger';
	}

	public static function getObjectClass()
	{
		return TriggerObject::class;
	}

	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('primary' => true, 'data_type' => 'integer', 'autocomplete' => true,),
			'NAME' => array('data_type' => 'string'),
			'CODE' => array('data_type' => 'string'),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer'),
			'ENTITY_STATUS' => array('data_type' => 'string'),
			'APPLY_RULES' => array(
				'data_type' => 'string',
				'serialized' => true
			)
		);
	}

	public static function deleteByEntityTypeId(int $entityTypeId)
	{
		$iterator = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId
			]
		]);

		if ($iterator)
		{
			while ($trigger = $iterator->fetch())
			{
				static::delete($trigger['ID']);
			}
		}
	}
}
