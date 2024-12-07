<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;

/**
 * Class UsageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Usage_Query query()
 * @method static EO_Usage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Usage_Result getById($id)
 * @method static EO_Usage_Result getList(array $parameters = [])
 * @method static EO_Usage_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_Usage createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Usage_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_Usage wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Usage_Collection wakeUpCollection($rows)
 */
class UsageTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_usage';
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
			new Entity\IntegerField('USER_ID', [
				'required' => true,
			]),
			new Entity\StringField('USAGE_PERIOD', [
				'required' => true,
			]),
			new Entity\IntegerField('USAGE_COUNT', [
				'required' => true,
				'default_value' => 1,
			]),
			new Entity\DatetimeField('DATE_MODIFY'),
		];
	}
}
