<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;

Loc::loadMessages(__FILE__);

/**
 * Class ActivityBindingTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ActivityBinding_Query query()
 * @method static EO_ActivityBinding_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ActivityBinding_Result getById($id)
 * @method static EO_ActivityBinding_Result getList(array $parameters = [])
 * @method static EO_ActivityBinding_Entity getEntity()
 * @method static \Bitrix\Crm\EO_ActivityBinding createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_ActivityBinding_Collection createCollection()
 * @method static \Bitrix\Crm\EO_ActivityBinding wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_ActivityBinding_Collection wakeUpCollection($rows)
 */
class ActivityBindingTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_bind';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ACTIVITY_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_ID' => array(
				'data_type' => 'integer'
			),
			'OWNER_TYPE_ID' => array(
				'data_type' => 'integer'
			)
		);
	}
}
