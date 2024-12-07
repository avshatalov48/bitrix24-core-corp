<?php

namespace Bitrix\StaffTrack\Item\Collection;

use Bitrix\Main\Type\Dictionary;

abstract class BaseCollection extends Dictionary
{
	public function toArray(): array
	{
		return array_map(static fn ($item) => $item->toArray(), $this->values);
	}
}