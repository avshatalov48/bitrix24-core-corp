<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

interface ValueObjectInterface extends Arrayable
{
	public static function mapFromArray(array $props): static;
}
