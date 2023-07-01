<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Action;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Menu;

class ShowMenu extends Action
{
	protected Menu $menu;

	public function __construct(Menu $menu)
	{
		$this->menu = $menu;
	}

	public function getMenu(): Menu
	{
		return $this->menu;
	}

	public function toArray(): array
	{
		return [
			'type' => 'showMenu',
			'value' => $this->getMenu(),
			'actionParams' => $this->getActionParams(),
			'animation' => $this->getAnimation(),
			'analytics' => $this->getAnalytics(),
		];
	}
}
