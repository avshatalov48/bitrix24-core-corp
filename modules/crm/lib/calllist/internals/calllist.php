<?php

namespace Bitrix\Crm\CallList\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

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