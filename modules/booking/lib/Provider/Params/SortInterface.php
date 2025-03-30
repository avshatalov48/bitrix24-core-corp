<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params;

interface SortInterface
{
	public function prepareSort(): array;
}
