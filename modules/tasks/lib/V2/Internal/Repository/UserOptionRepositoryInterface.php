<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;
use Bitrix\Tasks\V2\Internal\Service\OptionDictionary;

interface UserOptionRepositoryInterface
{
	public function isSet(OptionDictionary $optionDictionary, int $userId): bool;

	public function add(OptionDictionary $optionDictionary, int $userId, mixed $value): void;

	public function delete(OptionDictionary $optionDictionary, int $userId): void;
}
