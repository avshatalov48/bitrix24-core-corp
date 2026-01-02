<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Service\OptionDictionary;

class UserOptionRepository implements UserOptionRepositoryInterface
{
	public function isSet(OptionDictionary $optionDictionary, int $userId): bool
	{
		return (bool)\CUserOptions::GetOption(
			category: 'tasks.v2',
			name: $optionDictionary->value,
			default_value: false,
			user_id: $userId
		);
	}

	public function add(OptionDictionary $optionDictionary, int $userId, mixed $value): void
	{
		\CUserOptions::SetOption(
			category: 'tasks.v2',
			name: $optionDictionary->value,
			value: $value,
			user_id: $userId,
		);
	}

	public function delete(OptionDictionary $optionDictionary, int $userId): void
	{
		\CUserOptions::DeleteOption(
			category: 'tasks.v2',
			name: $optionDictionary->value,
			user_id: $userId,
		);
	}
}
