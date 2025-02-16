<?php

namespace Bitrix\Booking\Internals\Query;

interface SelectInterface
{
	public function prepareSelect(): array;
}
