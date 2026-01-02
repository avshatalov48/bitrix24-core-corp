<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Config\Option;

class OptionRepository implements OptionRepositoryInterface
{
	public function get(string $moduleId, string $name, mixed $default = ''): mixed
	{
		return Option::get($moduleId, $name, $default);
	}

	public function set(string $moduleId, string $name, mixed $value): void
	{
		Option::set($moduleId, $name, $value);
	}
}
