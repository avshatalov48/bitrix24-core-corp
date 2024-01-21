<?php

namespace Bitrix\Crm\Activity\UncompletedActivity;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Type\DateTime;

class UpsertDto
{
	public function __construct(
		private int $activityId,
		private DateTime $minDeadline,
		private bool $isIncomingChannel,
		private bool $hasAnyIncomingChannel,
		private DateTime $minLightTime,
		private ItemIdentifier $itemIdentifier,
		private int $responsibleId
	)
	{
	}

	public function activityId(): int
	{
		return $this->activityId;
	}

	public function minDeadline(): DateTime
	{
		return $this->minDeadline;
	}

	public function isIncomingChannel(): bool
	{
		return $this->isIncomingChannel;
	}

	public function hasAnyIncomingChannel(): bool
	{
		return $this->hasAnyIncomingChannel;
	}

	public function minLightTime(): DateTime
	{
		return $this->minLightTime;
	}

	public function itemIdentifier(): ItemIdentifier
	{
		return $this->itemIdentifier;
	}

	public function responsibleId(): int
	{
		return $this->responsibleId;
	}

}