<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\DelayedTask;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Internals\Service\DelayedTask\Data\DataInterface;

class DelayedTask implements EntityInterface
{
	private int|null $id = null;
	private string|null $code = null;
	private DelayedTaskType|null $type = null;
	private DataInterface|null $data = null;
	private DelayedTaskStatus|null $status = null;
	private int|null $createdAt = null;
	private int|null $updatedAt = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getCode(): ?string
	{
		return $this->code;
	}

	public function setCode(?string $code): self
	{
		$this->code = $code;

		return $this;
	}

	public function getType(): ?DelayedTaskType
	{
		return $this->type;
	}

	public function setType(?DelayedTaskType $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getData(): ?DataInterface
	{
		return $this->data;
	}

	public function setData(?DataInterface $data): self
	{
		$this->data = $data;

		return $this;
	}

	public function getStatus(): ?DelayedTaskStatus
	{
		return $this->status;
	}

	public function setStatus(?DelayedTaskStatus $status): self
	{
		$this->status = $status;

		return $this;
	}

	public function getCreatedAt(): ?int
	{
		return $this->createdAt;
	}

	public function setCreatedAt(?int $createdAt): self
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): ?int
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?int $updatedAt): self
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public static function mapFromArray(array $props): EntityInterface
	{
		// TODO: Implement mapFromArray() method.
	}

	public function toArray(): array
	{
		// TODO: Implement toArray() method.
	}
}
