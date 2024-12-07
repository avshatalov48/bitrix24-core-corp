<?php
namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;

/**
 * Class EngineTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Engine_Query query()
 * @method static EO_Engine_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Engine_Result getById($id)
 * @method static EO_Engine_Result getList(array $parameters = [])
 * @method static EO_Engine_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_Engine createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Engine_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_Engine wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Engine_Collection wakeUpCollection($rows)
 */
class EngineTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_engine';
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
			new Entity\StringField('APP_CODE'),
			new Entity\StringField('NAME', [
				'required' => true,
			]),
			new Entity\StringField('CODE', [
				'required' => true,
			]),
			new Entity\StringField('CATEGORY', [
				'required' => true,
			]),
			new Entity\StringField('COMPLETIONS_URL', [
				'required' => true,
			]),
			(new ArrayField('SETTINGS'))
				->configureSerializationJson(),
			new Entity\DatetimeField('DATE_CREATE'),
		];
	}
}
