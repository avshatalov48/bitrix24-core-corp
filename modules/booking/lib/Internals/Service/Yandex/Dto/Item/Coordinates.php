<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\Service\Yandex\Dto\Item;

class Coordinates extends Item
{
	private float $lat;
	private float $lon;

	public function __construct(float $lat, float $lon)
	{
		if (
			($lat < -90 || $lat > 90)
			|| ($lon < -180 || $lon > 180)
		)
		{
			throw new InvalidArgumentException();
		}

		$this->lat = $lat;
		$this->lon = $lon;
	}

	protected function __toArray(): array
	{
		return [
			'lat' => $this->lat,
			'lon' => $this->lon,
		];
	}
}
