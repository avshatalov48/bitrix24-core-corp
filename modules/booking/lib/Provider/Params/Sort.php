<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params;

abstract class Sort implements SortInterface
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
