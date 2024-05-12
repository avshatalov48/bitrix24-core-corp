<?php

namespace Bitrix\Intranet\Settings\Controls;

use Bitrix\Intranet\Settings\SettingsPermission;

class Field extends Control
{
	public function __construct(
		string $id,
		private string $name,
		private string $label,
		private string $type,
		private ?SettingsPermission $permission = null,
		private bool $isEnable = true,
		private ?string $value = null,
		private ?array $hints = null,
		private ?string $helpDesk = null
	)
	{
		parent::__construct($id);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): void
	{
		$this->type = $type;
	}

	public function isEnable(): bool
	{
		return $this->isEnable;
	}

	public function setIsEnable(bool $isEnable): void
	{
		$this->isEnable = $isEnable;
	}

	public function getValue(): ?string
	{
		return $this->value;
	}

	public function setValue(?string $value): void
	{
		$this->value = $value;
	}

	public function getHints(): ?array
	{
		return $this->hints;
	}

	public function setHints(?array $hints): void
	{
		$this->hints = $hints;
	}

	public function getHelpDesk(): ?string
	{
		return $this->helpDesk;
	}

	public function setHelpDesk(?string $helpDesk): void
	{
		$this->helpDesk = $helpDesk;
	}

	public function getPermission(): ?SettingsPermission
	{
		return $this->permission;
	}

	public function setPermission(?SettingsPermission $permission): void
	{
		$this->permission = $permission;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->name,
			'label' => $this->label,
			'type' => $this->type,
			'isEnable' => $this->isEnable,
			'current' => $this->value,
			'hints' => $this->hints,
			'helpDesk' => $this->helpDesk,
		];
	}
}