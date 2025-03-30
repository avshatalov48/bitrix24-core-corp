<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\EntityInterface;

class Client implements EntityInterface
{
	private int|null $id = null;
	private bool|null $isReturning = null;
	private ClientType|null $type = null;
	private array $data = [];

	public function getId(): int|null
	{
		return $this->id;
	}

	public function setId(int|null $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getType(): ClientType|null
	{
		return $this->type;
	}

	public function setType(ClientType|null $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getIsReturning(): ?bool
	{
		return $this->isReturning;
	}

	public function setIsReturning(?bool $isReturning): self
	{
		$this->isReturning = $isReturning;

		return $this;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): self
	{
		$this->data = $data;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'isReturning' => $this->getIsReturning(),
			'type' => $this->getType()?->toArray(),
			'data' => $this->getData(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		return (new self())
			->setId(isset($props['id']) ? (int)$props['id'] : null)
			->setType(isset($props['type']) ? ClientType::mapFromArray((array)$props['type']) : null)
			->setData(isset($props['data']) ? (array)$props['data'] : [])
		;
	}
}
