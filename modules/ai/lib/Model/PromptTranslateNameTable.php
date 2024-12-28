<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;


/**
 * Class PromptTranslateNameTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PromptTranslateName_Query query()
 * @method static EO_PromptTranslateName_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PromptTranslateName_Result getById($id)
 * @method static EO_PromptTranslateName_Result getList(array $parameters = [])
 * @method static EO_PromptTranslateName_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_PromptTranslateName createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_PromptTranslateName_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_PromptTranslateName wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_PromptTranslateName_Collection wakeUpCollection($rows)
 */
class PromptTranslateNameTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public const DEFAULT_LANG = 'en';

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_prompt_translate_name';
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

			(new Entity\IntegerField('PROMPT_ID'))
				->configureRequired(),

			(new Entity\StringField('LANG'))
				->configureRequired(),

			(new Entity\StringField('TEXT'))
				->configureRequired(),
		];
	}
}
