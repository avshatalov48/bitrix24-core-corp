<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Footer;

class Button extends \Bitrix\Crm\Service\Timeline\Layout\Button
{
	public const TYPE_PRIMARY = 'primary';
	public const TYPE_SECONDARY = 'secondary';
	public const TYPE_AI = 'ai';

	protected ?string $icon = null;
	protected string $type;
	protected ?array $menuItems = null;

	public function __construct(string $title, string $type, string $icon = null)
	{
		parent::__construct($title);

		$this->type = $type;
		$this->icon = $icon;
	}

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon(string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function setMenuItems(?array $menuItems): self
	{
		$this->menuItems = $menuItems;

		return $this;
	}

	public function getMenuItems(): ?array
	{
		return $this->menuItems;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'iconName' => $this->getIcon(),
				'type' => $this->getType(),
				'menuItems' => $this->getMenuItems(),
			]
		);
	}
}
