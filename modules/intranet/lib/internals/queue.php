<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Intranet\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class QueueTable
 *
 * @package Bitrix\Intranet\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = array())
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\Intranet\Internals\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\Intranet\Internals\EO_Queue_Collection createCollection()
 * @method static \Bitrix\Intranet\Internals\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\Intranet\Internals\EO_Queue_Collection wakeUpCollection($rows)
 */
class QueueTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_intranet_queue';
	}

	/**
	 * Get map.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ENTITY_TYPE' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'ENTITY_ID' => array(
				'data_type' => 'string',
				'primary' => true,
			),
			'LAST_ITEM' => array(
				'data_type' => 'string',
				'required' => true,
			),
		);
	}
}
