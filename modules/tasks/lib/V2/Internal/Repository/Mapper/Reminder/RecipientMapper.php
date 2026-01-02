<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder;

use Bitrix\Tasks\Internals\Task\ReminderTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\Recipient;

class RecipientMapper
{
	public function mapFromEnum(Recipient $recipient): ?string
	{
		return match($recipient)
		{
			Recipient::Myself => ReminderTable::RECIPIENT_TYPE_SELF,
			Recipient::Creator => ReminderTable::RECIPIENT_TYPE_ORIGINATOR,
			Recipient::Responsible => ReminderTable::RECIPIENT_TYPE_RESPONSIBLE,
			Recipient::Accomplice => ReminderTable::RECIPIENT_TYPE_ACCOMPLICE,
			default => null,
		};
	}

	public function mapToEnum(string $recipient): Recipient
	{
		return match($recipient)
		{
			ReminderTable::RECIPIENT_TYPE_SELF => Recipient::Myself,
			ReminderTable::RECIPIENT_TYPE_ORIGINATOR => Recipient::Creator,
			ReminderTable::RECIPIENT_TYPE_RESPONSIBLE => Recipient::Responsible,
			ReminderTable::RECIPIENT_TYPE_ACCOMPLICE => Recipient::Accomplice,
			default => null,
		};
	}
}