<?php

declare(strict_types=1);

namespace Bitrix\Booking\Rest\V1\View;

use Bitrix\Main\Type\Contract\Arrayable;

abstract class Field implements Arrayable
{
	private array $attributes;

	public function __construct(array $attributes = [])
	{
		$this->attributes = $attributes;
	}

	protected function getAttributes(): array
	{
		return $this->attributes;
	}
}
