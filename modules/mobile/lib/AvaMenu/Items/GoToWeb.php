<?php

namespace Bitrix\Mobile\AvaMenu\Items;

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

	public function getId(): string
	{
		return 'go_to_web';
	}

	public function getIconId(): string
	{
		return 'go_to';
	}

	public function getMessageCode(): string
	{
		return 'AVA_MENU_NAME_GO_TO_WEB_MSGVER_1';
	}

	private function getEntryParams(): array
	{
		return [
			'type' => 'qrauth',
			'analyticsSection' => 'profile',
			'showHint' => false,
		];
	}
}
