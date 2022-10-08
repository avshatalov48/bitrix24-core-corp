<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Menu;

class MenuItem extends \Bitrix\Crm\Service\Timeline\Layout\Button
{
	protected ?string $icon = null;

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon(string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'icon' => $this->getIcon(),
			]
		);
	}
}
