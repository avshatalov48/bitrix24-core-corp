<?php

namespace Bitrix\ImOpenLines\V2\Queue;

use Bitrix\Im\V2\Rest\RestEntity;

class QueueItem implements RestEntity
{
	protected ?int $id = null;
	protected ?string $name = null;
	protected ?string $type = null;
	protected ?bool $isActive = null;


	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): self
	{
		$this->name = $name;

		return $this;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function isActive(): ?bool
	{
		return $this->isActive;
	}

	public function setIsActive(bool $isActive): self
	{
		$this->isActive = $isActive;

		return $this;
	}

	public static function getRestEntityName(): string
	{
		return 'queue';
	}

	public function toRestFormat(array $option = []): ?array
	{
		return [
			'id' => $this->id,
			'lineName' => $this->name,
			'type' => $this->type,
			'isActive' => $this->isActive,
		];
	}
}