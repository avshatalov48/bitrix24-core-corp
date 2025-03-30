<?php

namespace Bitrix\Sign\Item\Member;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Helper\CloneHelper;
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

	public function __clone(): void
	{
		$this->lastSendDate = CloneHelper::cloneIfNotNull($this->lastSendDate);
		$this->plannedNextSendDate = CloneHelper::cloneIfNotNull($this->plannedNextSendDate);
		$this->startDate = CloneHelper::cloneIfNotNull($this->startDate);
	}
}