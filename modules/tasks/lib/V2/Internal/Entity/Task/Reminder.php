<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\Recipient;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\RemindBy;
use Bitrix\Tasks\V2\Internal\Entity\Task\Reminder\RemindVia;
use Bitrix\Tasks\V2\Internal\Entity\Trait\MapTypeTrait;

class Reminder extends AbstractEntity
{
	use MapTypeTrait;

	public function __construct(
		public readonly ?int $id = null,
		#[PositiveNumber]
		public readonly ?int $userId = null,
		#[PositiveNumber]
		public readonly ?int $taskId = null,
		// #[PositiveNumber]
		public readonly ?int $nextRemindTs = null,
		public readonly ?RemindBy $remindBy = null,
		public readonly ?RemindVia $remindVia = null,
		public readonly ?Recipient $recipient = null,
		public readonly ?array $rrule = null,
		public readonly ?int $before = null,
	)
	{

	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public static function mapFromArray(array $props): static
	{
		return new static(
			id: static::mapInteger($props, 'id'),
			userId: static::mapInteger($props, 'userId'),
			taskId: static::mapInteger($props, 'taskId'),
			nextRemindTs: static::mapInteger($props, 'nextRemindTs'),
			remindBy: static::mapBackedEnum($props, 'remindBy', RemindBy::class),
			remindVia: static::mapBackedEnum($props, 'remindVia', RemindVia::class),
			recipient: static::mapBackedEnum($props, 'recipient', Recipient::class),
			rrule: static::mapArray($props, 'rrule'),
			before: static::mapInteger($props, 'before'),
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'userId' => $this->userId,
			'taskId' => $this->taskId,
			'nextRemindTs' => $this->nextRemindTs,
			'remindBy' => $this->remindBy?->value,
			'remindVia' => $this->remindVia?->value,
			'recipient' => $this->recipient?->value,
			'rrule' => $this->rrule,
			'before' => $this->before,
		];
	}
}
