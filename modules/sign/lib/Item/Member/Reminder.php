<?php

namespace Bitrix\Sign\Item\Member;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\Member\Notification\ReminderType;

class Reminder implements Contract\Item
{
	public function __construct(
		public ?DateTime $lastSendDate,
		public ?DateTime $plannedNextSendDate,
		public bool $completed = false,
		public ReminderType $type = ReminderType::NONE,
		public ?DateTime $startDate = null,
	)
	{
	}
}