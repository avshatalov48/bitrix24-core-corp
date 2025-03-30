<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository;

use Bitrix\Booking\Internals\Service\OptionDictionary;

interface OptionRepositoryInterface
{
	public function get(int $userId, OptionDictionary $option, string|null $default = null): string|null;
	public function set(int $userId, OptionDictionary $option, string|null $value): void;
}
