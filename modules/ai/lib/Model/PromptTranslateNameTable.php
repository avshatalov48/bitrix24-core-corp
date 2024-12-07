<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;


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
