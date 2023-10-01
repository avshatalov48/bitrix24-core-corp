<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;

Loc::loadMessages(__FILE__);

/**
 * Class QueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = [])
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Queue_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Queue_Collection wakeUpCollection($rows)
 */
class QueueTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_queue';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'WORK_TIME' => array(
				'data_type' => 'boolean',
				'required' => false,
				'default_value' => 'N',
				'values' => array('N','Y')
			),
		);
	}
}
