<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Model;

use Bitrix\Main\Entity;

class OwnerTable extends Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_ai_prompt_owner';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\IntegerField('USER_ID'))
				->configureRequired(),

			(new Entity\IntegerField('PROMPT_ID'))
				->configureRequired(),

			(new Entity\BooleanField('IS_FAVORITE'))
				->configureValues(0, 1)
				->configureDefaultValue(false),

			(new Entity\BooleanField('IS_DELETED'))
				->configureValues(0, 1)
				->configureDefaultValue(false)
		];
	}
}
