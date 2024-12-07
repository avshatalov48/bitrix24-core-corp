<?php

namespace Bitrix\Sign\Item\DateTime;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\DateTime;

class DateInterval implements Contract\Item
{
	public function __construct(
		public DateTime $start,
		public DateTime $end,
	)
	{
	}

	public function __clone()
	{
		$this->start = clone $this->start;
		$this->end = clone $this->end;
	}

	public function isIncludedInclusively(DateTime $date): bool
	{
		return $this->start <= $date && $date <= $this->end;
	}
}