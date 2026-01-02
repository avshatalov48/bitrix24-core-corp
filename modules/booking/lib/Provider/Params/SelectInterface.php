<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Params;

interface SelectInterface
{
	public function prepareSelect(): array;
}
