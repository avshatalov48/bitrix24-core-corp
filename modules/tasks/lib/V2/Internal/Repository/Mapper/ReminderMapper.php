<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder;
use Bitrix\Tasks\V2\Internal\Entity\Task\ReminderCollection;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder\RecipientMapper;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder\RemindByMapper;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Reminder\RemindViaMapper;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Trait\CastTrait;

class ReminderMapper
{
	use CastTrait;

	public function __construct(
		private readonly RecipientMapper $recipientMapper,
		private readonly RemindByMapper $remindByMapper,
		private readonly RemindViaMapper $remindViaMapper,
	)
	{

	}

	public function mapToCollection(array $reminders): ReminderCollection
	{
		$entities = [];
		foreach ($reminders as $reminder)
		{
			$entities[] = $this->mapToEntity($reminder);
		}

		return new ReminderCollection(...$entities);
	}

	public function mapFromCollection(ReminderCollection $reminders): array
	{
		return $reminders->map(fn (Reminder $reminder): array => $this->mapFromEntity($reminder));
	}

	public function mapToEntity(array $reminder): Reminder
	{
		return new Reminder(
			id: isset($reminder['ID']) ? (int)$reminder['ID'] : null,
			userId: isset($reminder['USER_ID']) ? (int)$reminder['USER_ID'] : null,
			taskId: isset($reminder['TASK_ID']) ? (int)$reminder['TASK_ID'] : null,
			nextRemindTs: $this->castDateTime($reminder['REMIND_DATE'] ?? null),
			remindBy: $this->remindByMapper->mapToEnum((string)$reminder['TYPE']),
			remindVia: $this->remindViaMapper->mapToEnum((string)$reminder['TRANSPORT']),
			recipient: $this->recipientMapper->mapToEnum((string)$reminder['RECEPIENT_TYPE']),
			rrule: $reminder['RRULE'] ?? null,
			before: isset($reminder['BEFORE_DEADLINE']) ? (int)$reminder['BEFORE_DEADLINE'] : null,
		);
	}

	public function mapFromEntity(Reminder $reminder): array
	{
		$data = [];
		if ($reminder->id)
		{
			$data['ID'] = $reminder->id;
		}

		if ($reminder->userId)
		{
			$data['USER_ID'] = $reminder->userId;
		}

		if ($reminder->taskId)
		{
			$data['TASK_ID'] = $reminder->taskId;
		}

		if ($reminder->nextRemindTs)
		{
			$data['REMIND_DATE'] = $this->castTimestamp($reminder->nextRemindTs, false);
		}

		if ($reminder->remindBy)
		{
			$data['TYPE'] = $this->remindByMapper->mapFromEnum($reminder->remindBy);
		}

		if ($reminder->remindVia)
		{
			$data['TRANSPORT'] = $this->remindViaMapper->mapFromEnum($reminder->remindVia);
		}

		if ($reminder->recipient)
		{
			$data['RECEPIENT_TYPE'] = $this->recipientMapper->mapFromEnum($reminder->recipient);
		}

		if ($reminder->rrule)
		{
			$data['RRULE'] = $reminder->rrule;
		}

		if ($reminder->before)
		{
			$data['BEFORE_DEADLINE'] = $reminder->before;
		}

		return $data;
	}
}