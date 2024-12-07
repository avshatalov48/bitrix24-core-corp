<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;

/**
 * Class SectionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Section_Query query()
 * @method static EO_Section_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Section_Result getById($id)
 * @method static EO_Section_Result getList(array $parameters = [])
 * @method static EO_Section_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_Section createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Section_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_Section wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Section_Collection wakeUpCollection($rows)
 */
class SectionTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_section';
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
			(new ArrayField('TRANSLATE', [
				'default_value' => '',
			]))->configureSerializationJson(),
			new Entity\DatetimeField('DATE_MODIFY'),
		];
	}
}
