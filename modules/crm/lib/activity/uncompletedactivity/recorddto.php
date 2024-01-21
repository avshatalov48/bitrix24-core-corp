<?php

namespace Bitrix\Crm\Activity\UncompletedActivity;

use Bitrix\Main\Type\DateTime;

class RecordDto
{
	public function __construct(
		private ?DateTime $minDeadline,
		private ?DateTime $minLightTime,
		private bool $isIncomingChannel,
		private ?bool $anyIncomingChannel
	)
	{
	}

	public function minDeadline(): ?DateTime
	{
		return $this->minDeadline;
	}

	public function minLightTime(): ?DateTime
	{
		return $this->minLightTime;
	}

	public function isIncomingChannel(): bool
	{
		return $this->isIncomingChannel;
	}

	public function isAnyIncomingChannel(): ?bool
	{
		return $this->anyIncomingChannel;
	}

	public static function fromOrmArray(array $existedRecord): self
	{
		$existedDeadline = $existedRecord['MIN_DEADLINE'] && \CCrmDateTimeHelper::IsMaxDatabaseDate($existedRecord['MIN_DEADLINE']->toString())
			? null
			: $existedRecord['MIN_DEADLINE']
		;
		$existedLightTime = $existedRecord['MIN_LIGHT_COUNTER_AT'];
		$existedIsIncomingChannel = ($existedRecord['IS_INCOMING_CHANNEL'] === 'Y');
		$existedAnyIncomingChannel = ($existedRecord['HAS_ANY_INCOMING_CHANEL'] === 'Y');

		return new self(
			$existedDeadline,
			$existedLightTime,
			$existedIsIncomingChannel,
			$existedAnyIncomingChannel
		);
	}

}