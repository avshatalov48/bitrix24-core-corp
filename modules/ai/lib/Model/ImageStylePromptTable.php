<?php

namespace Bitrix\AI\Model;

use Bitrix\AI\Entity\ImageStylePrompt;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;

/**
 * Class ImageStyleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ImageStylePrompt_Query query()
 * @method static EO_ImageStylePrompt_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ImageStylePrompt_Result getById($id)
 * @method static EO_ImageStylePrompt_Result getList(array $parameters = [])
 * @method static EO_ImageStylePrompt_Entity getEntity()
 * @method static \Bitrix\AI\Entity\ImageStylePrompt createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_ImageStylePrompt_Collection createCollection()
 * @method static \Bitrix\AI\Entity\ImageStylePrompt wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_ImageStylePrompt_Collection wakeUpCollection($rows)
 */
class ImageStylePromptTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_image_style_prompt';
	}

	public static function getObjectClass(): string
	{
		return ImageStylePrompt::class;
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Entity\StringField('CODE'))
				->configureRequired(),
			(new Entity\StringField('HASH'))
				->configureRequired(),
			(new ArrayField('NAME_TRANSLATES'))
				->configureSerializationJson(),
			(new Entity\StringField('PROMPT'))
				->configureRequired(),
			(new Entity\StringField('PREVIEW'))
				->configureRequired(),
			new Entity\IntegerField('SORT'),
			new Entity\DatetimeField('DATE_MODIFY'),
		];
	}
}
