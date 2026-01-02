<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Collection;

use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Collection;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;
use Bitrix\Main\Result;

class CompanyCollection extends Collection
{
	public function add(Item $item): self
	{
		if (!$item instanceof Item\Company)
		{
			throw new InvalidArgumentException();
		}

		$this->items[] = $item;

		return $this;
	}

	public function validate(): Result
	{
		/** @var Item\Company $item */
		foreach ($this->items as $item)
		{
			$validateResult = $item->validate();
			if (!$validateResult->isSuccess())
			{
				return $validateResult;
			}
		}

		return new Result();
	}
}
