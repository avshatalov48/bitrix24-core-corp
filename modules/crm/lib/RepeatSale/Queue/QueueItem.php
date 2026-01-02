<?php

namespace Bitrix\Crm\RepeatSale\Queue;

use Bitrix\Crm\RepeatSale\Queue\Entity\RepeatSaleQueue;
use Bitrix\Crm\RepeatSale\Service\Handler\HandlerType;
use Bitrix\Main\Web\Json;

final class QueueItem
{
	private ?int $id;
	private int $jobId;
	private bool $isOnlyCalc = false;
	private int $status;
	private ?int $lastEntityTypeId = null;
	private ?int $lastItemId = null;
	private ?int $lastAssignmentId = null;
	private int $itemsCount = 0;
	private int $handlerTypeId;
	private int $retryCount = 0;
	private ?string $hash = null;
	private ?array $params = null;

	public static function createFromEntity(RepeatSaleQueue $queueItem): self
	{
		$instance = new self();

		$instance->id = $queueItem->getId();
		$instance->jobId = $queueItem->getJobId();
		$instance->isOnlyCalc = $queueItem->getIsOnlyCalc();
		$instance->status = $queueItem->getStatus();
		$instance->lastEntityTypeId = $queueItem->getLastEntityTypeId();
		$instance->lastItemId = $queueItem->getLastItemId();
		$instance->lastAssignmentId = $queueItem->getLastAssignmentId();
		$instance->itemsCount = $queueItem->getItemsCount();
		$instance->handlerTypeId = $queueItem->getHandlerTypeId();
		$instance->retryCount = $queueItem->getRetryCount();
		$instance->hash = $queueItem->getHash();
		$instance->params = $queueItem->getParams();

		return $instance;
	}

	public static function createFromArray(array $data): self
	{
		$instance = new self();

		$instance->id = $data['id'] ?? null;
		$instance->jobId = $data['jobId'];
		$instance->isOnlyCalc = $data['isOnlyCalc'] ?? false;
		$instance->status = $data['status'] ?? Status::Waiting->value;
		$instance->lastEntityTypeId = $data['lastEntityTypeId'] ?? null;
		$instance->lastItemId = $data['lastItemId'] ?? null;
		$instance->lastAssignmentId = $data['lastAssignmentId'] ?? null;
		$instance->itemsCount = $data['itemsCount'] ?? 0;
		$instance->handlerTypeId = $data['handlerTypeId'] ?? HandlerType::SystemHandler->value;
		$instance->retryCount = $data['retryCount'] ?? 0;
		$instance->params = $data['params'] ?? null;
		$instance->hash = md5(Json::encode($instance->params));

		return $instance;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'jobId' => $this->jobId,
			'isOnlyCalc' => $this->isOnlyCalc,
			'status' => $this->status,
			'lastEntityTypeId' => $this->lastEntityTypeId,
			'lastItemId' => $this->lastItemId,
			'lastAssignmentId' => $this->lastAssignmentId,
			'itemsCount' => $this->itemsCount,
			'handlerTypeId' => $this->handlerTypeId,
			'retryCount' => $this->retryCount,
			'hash' => $this->hash,
			'params' => $this->params,
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

	public function setJobId(int $jobId): self
	{
		$this->jobId = $jobId;

		return $this;
	}

	public function getHash(): ?string
	{
		return $this->hash;
	}

	public function getParams(): ?array
	{
		return $this->params;
	}

	public function setParams(?array $params): self
	{
		$this->params = $params;
		$this->hash = md5($params);

		return $this;
	}

	public function getStatus(): Status
	{
		return Status::from($this->status);
	}

	public function setStatus(Status $status): self
	{
		$this->status = $status->value;

		return $this;
	}

	public function getLastEntityTypeId(): ?int
	{
		return $this->lastEntityTypeId;
	}

	public function setLastEntityTypeId(?int $lastEntityTypeId): self
	{
		$this->lastEntityTypeId = $lastEntityTypeId;

		return $this;
	}

	public function getLastItemId(): ?int
	{
		return $this->lastItemId;
	}

	public function setLastItemId(?int $lastItemId): self
	{
		$this->lastItemId = $lastItemId;

		return $this;
	}

	public function getLastAssignmentId(): ?int
	{
		return $this->lastAssignmentId;
	}

	public function setLastAssignmentId(?int $lastAssignmentId): self
	{
		$this->lastAssignmentId = $lastAssignmentId;

		return $this;
	}

	public function getHandlerTypeId(): int
	{
		return $this->handlerTypeId;
	}

	public function setHandlerTypeId(int $handlerTypeId): self
	{
		$this->handlerTypeId = $handlerTypeId;

		return $this;
	}

	public function getHandlerType(): HandlerType
	{
		return HandlerType::fromValue($this->handlerTypeId);
	}

	public function getRetryCount(): int
	{
		return $this->retryCount;
	}

	public function setRetryCount(int $retryCount): self
	{
		$this->retryCount = $retryCount;

		return $this;
	}

	public function getItemsCount(): int
	{
		return $this->itemsCount;
	}

	public function setItemsCount(int $itemsCount): self
	{
		$this->itemsCount = $itemsCount;

		return $this;
	}

	public function isOnlyCalc(): bool
	{
		return $this->isOnlyCalc;
	}

	public function incRetryCount(): self
	{
		$this->retryCount++;

		return $this;
	}
}
