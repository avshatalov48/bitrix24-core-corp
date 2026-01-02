<?php

namespace Bitrix\Crm\RepeatSale\Log;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLog;

final class LogItem
{
	private ?int $id;
	private int $jobId;
	private int $segmentId;
	private int $entityTypeId;
	private int $entityId;
	private string $stageSemanticId;

	public static function createFromEntity(RepeatSaleLog $logItem): self
	{
		$instance = new self();

		$instance->id = $logItem->getId();
		$instance->jobId = $logItem->getJobId();
		$instance->segmentId = $logItem->getSegmentId();
		$instance->entityTypeId = $logItem->getEntityTypeId();
		$instance->entityId = $logItem->getEntityId();
		$instance->stageSemanticId = $logItem->getStageSemanticId();

		return $instance;
	}

	public static function createFromArray(array $data): self
	{
		$instance = new self();

		$instance->id = $data['id'] ?? null;
		$instance->jobId = $data['jobId'];
		$instance->segmentId = $data['segmentId'];
		$instance->entityTypeId = $data['entityTypeId'];
		$instance->entityId = $data['entityId'];
		$instance->stageSemanticId = $data['stageSemanticId'] ?? PhaseSemantics::PROCESS;

		return $instance;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'jobId' => $this->jobId,
			'segmentId' => $this->segmentId,
			'entityTypeId' => $this->entityTypeId,
			'entityId' => $this->entityId,
			'stageSemanticId' => $this->stageSemanticId,
		];
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getJobId(): int
	{
		return $this->jobId;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	public function getSegmentId(): int
	{
		return $this->segmentId;
	}

	public function getStageSemanticId(): string
	{
		return $this->stageSemanticId;
	}
}
