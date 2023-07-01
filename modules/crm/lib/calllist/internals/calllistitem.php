<?php

namespace Bitrix\Crm\CallList\Internals;

use Bitrix\Crm\CallList\CallList;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class CallListItemTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallListItem_Query query()
 * @method static EO_CallListItem_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CallListItem_Result getById($id)
 * @method static EO_CallListItem_Result getList(array $parameters = [])
 * @method static EO_CallListItem_Entity getEntity()
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListItem createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListItem_Collection createCollection()
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListItem wakeUpObject($row)
 * @method static \Bitrix\Crm\CallList\Internals\EO_CallListItem_Collection wakeUpCollection($rows)
 */
class CallListItemTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_crm_call_list_item';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			'LIST_ID' => new Entity\IntegerField('LIST_ID', array(
				'primary' => true
			)),
			'ENTITY_TYPE_ID' => new Entity\IntegerField('ENTITY_TYPE_ID', array(
				'primary' => true
			)),
			'ELEMENT_ID' => new Entity\IntegerField('ELEMENT_ID', array(
				'primary' => true
			)),
			'STATUS_ID' => new Entity\StringField('STATUS_ID', array(
				'required' => true,
				'default_value' => CallList::STATUS_IN_WORK
			)),
			'CALL_ID' => new Entity\IntegerField('CALL_ID'),
			'WEBFORM_RESULT_ID' => new Entity\IntegerField('WEBFORM_RESULT_ID'),
			'RANK' => new Entity\IntegerField('RANK'),
			'WEBFORM_ACTIVITY' => new Entity\ReferenceField(
				'WEBFORM_ACTIVITY',
				'Bitrix\Crm\ActivityTable',
				array(
					'=this.WEBFORM_RESULT_ID' => 'ref.ASSOCIATED_ENTITY_ID',
					'=ref.TYPE_ID' =>  new DB\SqlExpression('?i', \CCrmActivityType::Provider),
					'=ref.PROVIDER_ID' => new DB\SqlExpression('?s', \Bitrix\Crm\Activity\Provider\WebForm::PROVIDER_ID)
				),
				array('join_type' => 'LEFT')
			),
			'CALL' => new Entity\ReferenceField(
				'CALL',
				'Bitrix\Voximplant\StatisticTable',
				array(
					'=this.CALL_ID' => 'ref.ID',
				),
				array('join_type' => 'LEFT')
			),
			'CNT' => new Entity\ExpressionField('CNT', 'COUNT(*)')
		);
	}
}