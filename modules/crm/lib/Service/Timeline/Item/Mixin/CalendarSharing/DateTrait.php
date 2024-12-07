<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;

use Bitrix\Main\Type\DateTime;

trait DateTrait
{
	public function getDateContent(?int $timestamp): DateTime
	{
		return ($timestamp && !\CCrmDateTimeHelper::IsMaxDatabaseDate($timestamp))
			? DateTime::createFromTimestamp($timestamp)
			: new DateTime()
		;
	}
}