<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

interface EntityInterface extends \Bitrix\Main\Entity\EntityInterface, Arrayable
{
	public function getId(): mixed;

	public static function mapFromArray(array $props): static;
}
