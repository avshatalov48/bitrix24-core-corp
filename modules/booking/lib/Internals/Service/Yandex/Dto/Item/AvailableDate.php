<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class AvailableDate extends Item
{
	private string $date;

	public function __construct(string $date)
	{
		//@todo validate against Y-m-d (or ISO 8601 ?) and throw exception

		$this->date = $date;
	}

	protected function __toArray(): array
	{
		return [
			'date' => $this->date,
		];
	}
}
