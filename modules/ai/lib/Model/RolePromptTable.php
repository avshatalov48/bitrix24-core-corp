<?php

declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;

class RolePromptTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_role_prompt';
	}

	public static function getMap(): array
	{
		return [
			(new Entity\IntegerField('ROLE_ID'))
				->configurePrimary(),
			(new Entity\IntegerField('PROMPT_ID'))
				->configurePrimary(),
			new Entity\DatetimeField('DATE_CREATE'),
		];
	}
}
