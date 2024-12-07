<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;

class GoToWeb extends AbstractMenuItem
{

	public function isAvailable(): bool
	{
		return true;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'customData' => $this->getEntryParams(),
		];
	}

	private function getEntryParams(): array
	{
		return [
			'type' => 'qrauth',
			'showHint' => false,
		];
	}

	public function getId(): string
	{
		return 'go_to_web';
	}

	public function getIconId(): string
	{
		return 'go_to';
	}
}
