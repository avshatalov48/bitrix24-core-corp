<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

interface EntityInterface extends Arrayable
{
	public function getId(): mixed;

	public static function mapFromArray(array $props): self;
}
