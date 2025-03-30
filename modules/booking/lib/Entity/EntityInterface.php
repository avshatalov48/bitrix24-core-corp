<?php

namespace Bitrix\Booking\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

interface EntityInterface extends Arrayable
{
	public function getId(): int|string|null;

	public static function mapFromArray(array $props): self;
}
