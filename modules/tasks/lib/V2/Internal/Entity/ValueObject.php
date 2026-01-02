<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

abstract class ValueObject
{
	abstract public function toArray(): array;
	abstract public static function mapFromArray(array $props = []): static;
}
