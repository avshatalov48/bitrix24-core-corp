<?php

namespace Bitrix\ImOpenLines\V2\Helper;

use Bitrix\Main\Error;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use DateTimeInterface;

trait DateTimeHelper
{
	public function createFromText(string $datetime): ?DateTime
	{
		try
		{
			$date = new DateTime($datetime, DateTimeInterface::RFC3339);
		}
		catch (ObjectException $exception)
		{
			$this->addError(new Error($exception->getCode(), $exception->getMessage()));
			return null;
		}

		return $date;
	}

	abstract protected function addError(Error $error);
}