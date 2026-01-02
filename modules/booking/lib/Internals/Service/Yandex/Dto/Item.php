<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto;

use Bitrix\Main\Type\Contract\Arrayable;

abstract class Item implements Arrayable
{
	public function toArray()
	{
		return array_filter($this->__toArray(), static fn ($value) => $value !== null);
	}

	abstract protected function __toArray(): array;
}
