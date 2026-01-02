<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class PriceRange extends Item
{
	private string $currencyCode;
	private float $from;
	private float|null $to;

	public function __construct(string $currencyCode, float $from, float|null $to = null)
	{
		$this->currencyCode = $currencyCode;
		$this->from = $from;
		$this->to = $to;
	}

	protected function __toArray(): array
	{
		$range = $this->to === null ? [$this->from] : [$this->from, $this->to];

		return [
			'currencyCode' => $this->currencyCode,
			'range' => $range,
		];
	}
}
