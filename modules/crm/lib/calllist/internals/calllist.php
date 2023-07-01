<?php

namespace Bitrix\Crm\CallList\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class CallListTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallList_Query query()
 * @method static EO_CallList_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallList_Result getById($id)
 * @method static EO_CallList_Result getList(array $parameters = [])
 * @method static EO_CallList_Entity getEntity()
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallList createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallList_Collection createCollection()
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallList wakeUpObject($row)
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallList_Collection wakeUpCollection($rows)
 */
class CallListTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_crm_call_list';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true
			)),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', array(
				'default_value' => new Type\DateTime()
			)),
			'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID'),
			'FILTERED' => new Entity\StringField('FILTERED'),
			'GRID_ID' => new Entity\StringField('GRID_ID'),
			'FILTER_PARAMS' => new Entity\TextField('FILTER_PARAMS', array(
				'serialized' => true
			)),
			'WEBFORM_ID' => new Entity\IntegerField('WEBFORM_ID'),
			'ENTITY_TYPE_ID' => new Entity\IntegerField('ENTITY_TYPE_ID')
		);
	}
}