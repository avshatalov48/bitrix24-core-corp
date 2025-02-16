<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Query;

interface SortInterface
{
	public function prepareSort(): array;
}
