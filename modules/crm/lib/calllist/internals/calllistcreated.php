<?php

namespace Bitrix\Crm\CallList\Internals;

use Bitrix\Crm\CallList\CallList;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class CallListCreatedTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallListCreated_Query query()
 * @method static EO_CallListCreated_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallListCreated_Result getById($id)
 * @method static EO_CallListCreated_Result getList(array $parameters = [])
 * @method static EO_CallListCreated_Entity getEntity()
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListCreated createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListCreated_Collection createCollection()
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListCreated wakeUpObject($row)
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListCreated_Collection wakeUpCollection($rows)
 */
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