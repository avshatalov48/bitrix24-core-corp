<?php

namespace Bitrix\Crm\RepeatSale\Job;

use Bitrix\Crm\RepeatSale\Job\Entity\RepeatSaleJob;

final class JobItem
{
	private ?int $id;
	private int $segmentId;
	private int $scheduleType;

	public static function createFromEntity(RepeatSaleJob $jobItem): self
	{
		$instance = new self();

		$instance->id = $jobItem->getId();
		$instance->segmentId = $jobItem->getSegmentId();
		$instance->scheduleType = $jobItem->getScheduleType();

		return $instance;
	}

	public static function createFromArray(array $data): self
	{
		$instance = new self();

		$instance->id = $data['id'] ?? null;
		$instance->segmentId = $data['segmentId'];
		$instance->scheduleType = $data['scheduleType'];

		return $instance;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'segmentId' => $this->segmentId,
			'scheduleType' => $this->scheduleType,
		];
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getSegmentId(): int
	{
		return $this->segmentId;
	}

	public function setSegmentId(int $segmentId): self
	{
		$this->segmentId = $segmentId;

		return $this;
	}

	public function getScheduleType(): int
	{
		return $this->scheduleType;
	}

	public function setScheduleType(int $scheduleType): self
	{
		$this->scheduleType = $scheduleType;

		return $this;
	}
}
