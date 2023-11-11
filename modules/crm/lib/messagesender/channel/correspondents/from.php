<?php

namespace Bitrix\Crm\MessageSender\Channel\Correspondents;

final class From
{
	private string $id;
	private string $name;
	private ?string $description;
	private bool $isDefault;
	private bool $isAvailable;

	public function __construct(
		string $id,
		string $name,
		?string $description = null,
		bool $isDefault = false,
		bool $isAvailable = true,
	)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
		$this->isDefault = $isDefault;
		$this->isAvailable = $isAvailable;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function isDefault(): bool
	{
		return $this->isDefault;
	}

	public function isAvailable(): bool
	{
		return $this->isAvailable;
	}
}
