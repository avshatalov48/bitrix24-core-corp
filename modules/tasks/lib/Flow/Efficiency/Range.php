<?php

namespace Bitrix\Tasks\Flow\Efficiency;

use Bitrix\Main\Type\DateTime;

class Range
{
	public function from(): DateTime
	{
		return new DateTime();
	}

	public function to(): DateTime
	{
		return new DateTime();
	}
}