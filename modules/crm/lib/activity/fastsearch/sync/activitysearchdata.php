<?php

namespace Bitrix\Crm\Activity\FastSearch\Sync;

use Bitrix\Main\Type\DateTime;

final class ActivitySearchData
{
	public const KIND_COMMON = 1;
	public const KIND_INCOMING = 2;
	public const KIND_UNSUPPORTED = -1;

	public const TYPE_UNSUPPORTED = 'TYPE_IS_UNSUPPORTED';

	public function __construct(
		private int $id,
		private DateTime $created,
		private DateTime $deadline,
		private int $responsibleId,
		private bool $completed,
		private string $type,
		private int $kind,
		private ?int $authorId = null
	)
	{
	}

	public function id(): int
	{
		return $this->id;
	}

	public function created(): DateTime
	{
		return $this->created;
	}

	public function deadline(): DateTime
	{
		return $this->deadline;
	}

	public function responsibleId(): int
	{
		return $this->responsibleId;
	}

	public function isCompleted(): bool
	{
		return $this->completed;
	}

	public function type(): string
	{
		return $this->type;
	}

	public function authorId(): ?int
	{
		return $this->authorId ?? 0;
	}

	public function kind(): int
	{
		return $this->kind;
	}

	public function toORMArray(): array
	{
		return [
			'ACTIVITY_ID' => $this->id,
			'CREATED' => $this->created(),
			'DEADLINE' => $this->deadline(),
			'RESPONSIBLE_ID' => $this->responsibleId(),
			'COMPLETED' => $this->isCompleted() ? 'Y' : 'N',
			'ACTIVITY_TYPE' => $this->type(),
			'ACTIVITY_KIND' => $this->kind(),
			'AUTHOR_ID' => $this->authorId()
		];
	}

}