<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Booking;

use Bitrix\Booking\Entity\EntityInterface;

class ExternalDataItem implements EntityInterface
{
	private int|null $id = null;
	private string|null $moduleId = null;
	private string|null $entityTypeId = null;
	private string|null $value = null;
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

	public function getModuleId(): ?string
	{
		return $this->moduleId;
	}

	public function setModuleId(?string $moduleId): self
	{
		$this->moduleId = $moduleId;

		return $this;
	}

	public function getEntityTypeId(): ?string
	{
		return $this->entityTypeId;
	}

	public function setEntityTypeId(?string $entityTypeId): self
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	public function getValue(): ?string
	{
		return $this->value;
	}

	public function setValue(?string $value): self
	{
		$this->value = $value;

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
			'moduleId' => $this->getModuleId(),
			'entityTypeId' => $this->getEntityTypeId(),
			'value' => $this->getValue(),
			'data' => $this->getData(),
		];
	}

	public static function mapFromArray(array $props): self
	{
		return (new self())
			->setModuleId(isset($props['moduleId']) ? (string)$props['moduleId'] : null)
			->setEntityTypeId(isset($props['entityTypeId']) ? (string)$props['entityTypeId'] : null)
			->setValue(isset($props['value']) ? (string)$props['value'] : null)
			->setData(isset($props['data']) ? (array)$props['data'] : [])
		;
	}
}
