<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Voximplant\StatisticTable;

/**
 * Class CallCrmEntityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CallCrmEntity_Query query()
 * @method static EO_CallCrmEntity_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_CallCrmEntity_Result getById($id)
 * @method static EO_CallCrmEntity_Result getList(array $parameters = array())
 * @method static EO_CallCrmEntity_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_CallCrmEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_CallCrmEntity wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_CallCrmEntity_Collection wakeUpCollection($rows)
 */
class CallCrmEntityTable extends Base
{
	public static function getTableName()
	{
		return 'b_voximplant_call_crm_entity';
	}

	public static function getMap()
	{
		return [
			new Fields\StringField('CALL_ID', [
				'primary' => true
			]),
			new Fields\StringField('ENTITY_TYPE', [
				'primary' => true
			]),
			new Fields\IntegerField('ENTITY_ID', [
				'primary' => true
			]),
			new Fields\BooleanField('IS_PRIMARY', [
				'values' => ['N', 'Y']
			]),
			new Fields\BooleanField('IS_CREATED', [
				'values' => ['N', 'Y']
			]),

			new Fields\Relations\Reference('CALL', StatisticTable::class, Join::on('this.CALL_ID', 'ref.CALL_ID'))
		];
	}

	protected static function getMergeFields()
	{
		return ['CALL_ID', 'ENTITY_TYPE', 'ENTITY_ID'];
	}
}