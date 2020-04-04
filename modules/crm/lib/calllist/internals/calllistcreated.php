<?php

namespace Bitrix\Crm\CallList\Internals;

use Bitrix\Crm\CallList\CallList;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

class CallListCreatedTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_crm_call_list_created';
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
			'LIST_ID' => new Entity\IntegerField('LIST_ID', array(
				'required' => true
			)),
			'ELEMENT_ID' => new Entity\IntegerField('ELEMENT_ID', array(
				'required' => true
			)),
			'ENTITY_TYPE' => new Entity\StringField('ENTITY_TYPE'),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID'),
		);
	}
}