<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;

/**
 * Class PlanTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Plan_Query query()
 * @method static EO_Plan_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Plan_Result getById($id)
 * @method static EO_Plan_Result getList(array $parameters = [])
 * @method static EO_Plan_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_Plan createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Plan_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_Plan wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Plan_Collection wakeUpCollection($rows)
 */
class PlanTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_plan';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Entity\StringField('CODE', [
				'required' => true,
			]),
			new Entity\StringField('HASH', [
				'required' => true,
			]),
			new Entity\IntegerField('MAX_USAGE', [
				'required' => true,
			]),
			new Entity\DatetimeField('DATE_MODIFY'),
		];
	}
}
