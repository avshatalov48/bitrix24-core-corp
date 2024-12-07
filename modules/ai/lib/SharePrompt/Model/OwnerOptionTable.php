<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Model;

use Bitrix\Main\Entity;

class OwnerOptionTable extends Entity\DataManager
{
	public static function getTableName(): string
	{
		return 'b_ai_prompt_owner_option';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\IntegerField('USER_ID'))
				->configureRequired(),

			(new Entity\StringField('SORTING_IN_FAVORITE_LIST'))
				->configureRequired(),
		];
	}
}
