<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Menu;

use Bitrix\Crm\Service\Timeline\Layout\Menu;

class MenuItemSubmenu extends MenuItem
{
	protected ?Menu $menu = null;

	public function __construct(string $title, Menu $menu)
	{
		parent::__construct($title);
		$this->menu = $menu;
	}

	public function getMenu(): ?Menu
	{
		return $this->menu;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'menu' => $this->getMenu(),
			]
		);
	}
}
