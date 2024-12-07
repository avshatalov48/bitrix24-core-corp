<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

class ShareTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getTableName(): string
	{
		return 'b_ai_prompt_share';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),

			(new Entity\IntegerField('PROMPT_ID'))
				->configureRequired(),

			(new Entity\StringField('ACCESS_CODE'))
				->configureRequired(),

			new Entity\DatetimeField('DATE_CREATE'),

			new Entity\IntegerField('CREATED_BY'),
		];
	}
}
