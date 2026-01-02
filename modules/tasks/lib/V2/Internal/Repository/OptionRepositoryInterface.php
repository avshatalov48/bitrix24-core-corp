<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

interface OptionRepositoryInterface
{
	public function get(string $moduleId, string $name, mixed $default = ''): mixed;

	public function set(string $moduleId, string $name, mixed $value): void;
}
