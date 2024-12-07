<?php

namespace Bitrix\Tasks\Flow\Efficiency;


use Bitrix\Main\Type\DateTime;

class LastMonth extends Range
{
	public function from(): DateTime
	{
		return DateTime::createFromPhp(new \DateTime('30 days ago'))
			->setTime(0, 0, 1);
	}

	public function to(): DateTime
	{
		return DateTime::createFromPhp(new \DateTime('now'))
			->setTime(23, 59, 59);
	}
}