<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Footer;

class IconButton extends \Bitrix\Crm\Service\Timeline\Layout\Button
{
	public const COLOR_PRIMARY = 'primary';
	public const COLOR_DEFAULT = 'default';

	protected string $icon;
	protected ?string $color = null;

	public function __construct(string $icon, string $title = '')
	{
		parent::__construct($title);
		$this->icon = $icon;
	}

	public function getIcon(): string
	{
		return $this->icon;
	}

	public function setIcon(string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function getColor(): ?string
	{
		return $this->color;
	}

	public function setColor(?string $color): self
	{
		$this->color = $color;

		return $this;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'iconName' => $this->getIcon(),
				'color' => $this->getColor(),
			]
		);
	}
}
