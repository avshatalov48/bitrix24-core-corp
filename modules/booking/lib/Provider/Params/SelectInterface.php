<?php

namespace Bitrix\Booking\Provider\Params;

interface SelectInterface
{
	public function prepareSelect(): array;
}
