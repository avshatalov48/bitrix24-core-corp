<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Mobile\AvaMenu\AbstractMenuItem;
use Bitrix\MobileApp\Mobile;

class SwitchAccount extends AbstractMenuItem
{
	public function isAvailable(): bool
	{
		return Mobile::getApiVersion() < 56;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'customData' => $this->getEntryParams(),
		];
	}

	public function getId(): string
	{
		return 'switch_account';
	}

	public function getIconId(): string
	{
		return 'log_out';
	}

	private function getEntryParams(): array
	{
		return [
			'type' => 'switch_account',
			'showHint' => false,
		];
	}
}
