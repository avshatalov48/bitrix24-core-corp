<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query;

abstract class Sort
{
	protected array $sort;

	public function __construct(array $sort = [])
	{
		$this->sort = $sort;
	}

	public function prepareSort(): array
	{
		return array_intersect_key($this->sort, array_flip($this->getAllowedFields()));
	}

	abstract protected function getAllowedFields(): array;
}
