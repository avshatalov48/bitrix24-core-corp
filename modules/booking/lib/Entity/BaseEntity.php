<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity;

use Bitrix\Main\Type\Contract\Arrayable;;

abstract class BaseEntity implements Arrayable
{
	abstract public function getId(): ?int;
	abstract public function toArray(): array;
	abstract public static function mapFromArray(array $props): self;

	public function isNew(): bool
	{
		return $this->getId() === null;
	}
}
