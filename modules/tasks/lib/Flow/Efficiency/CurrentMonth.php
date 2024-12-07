<?php

namespace Bitrix\Tasks\Flow\Efficiency;


use Bitrix\Main\Type\DateTime;

class CurrentMonth extends Range
{
	public function from(): DateTime
	{
		return DateTime::createFromPhp(new \DateTime('first day of this month'))
			->setTime(0, 0, 1);
	}

	public function to(): DateTime
	{
		return DateTime::createFromPhp(new \DateTime('last day of this month'))
			->setTime(23, 59, 59);
	}
}