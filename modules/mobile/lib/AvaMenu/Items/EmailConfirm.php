<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Mobile\AvaMenu\AbstractMenuItem;

class EmailConfirm extends AbstractMenuItem
{
	public function isAvailable(): bool
	{
		return false;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'counter' => '1',
		];
	}

	public function getId(): string
	{
		return 'email_confirm';
	}

	public function getIconId(): string
	{
		return 'mail';
	}

	public function separatorAfter(): bool
	{
		return true;
	}
}