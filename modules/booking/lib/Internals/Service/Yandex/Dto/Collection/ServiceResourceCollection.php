<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Collection;

use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class ServiceResourceCollection extends Collection
{
	public function add(Item $item): self
	{
		if (!$item instanceof Item\ServiceResource)
		{
			throw new InvalidArgumentException();
		}

		$this->items[] = $item;

		return $this;
	}
}
