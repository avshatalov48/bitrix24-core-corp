<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Menu;

class MenuItemDelimiter extends MenuItem
{
	public function __construct(string $title = '')
	{
		parent::__construct($title);
	}

	public function toArray(): array
	{
		return [
			'title' => $this->getTitle(),
			'delimiter' => true,
		];
	}
}
