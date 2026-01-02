<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class AvailableTimeSlot extends Item
{
	private string $datetime;

	public function __construct(string $datetime)
	{
		//@todo validate against Y-m-d (or ISO 8601 ?) and throw exception

		$this->datetime = $datetime;
	}

	protected function __toArray(): array
	{
		return [
			'datetime' => $this->datetime,
		];
	}
}
