<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;

class Timeman extends AbstractMenuItem
{
	public function isAvailable(): bool
	{
		$enabled = Loader::includeModule('intranet') && ToolsManager::getInstance()->checkAvailabilityByToolId('worktime');

		return $enabled
			&& Loader::includeModule('timeman')
			&& \CTimeMan::CanUse()
			&& !$this->context->extranet
		;
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
			'type' => 'page',
			'url' => SITE_DIR . 'mobile/timeman/',
			'useSearchBar' => false,
			'titleParams' => [
				'text' => Loc::getMessage('MENU_WORK_DAY_MANAGE'),
				'type' => 'section',
			],
			'cache' => false,
			'backdrop' => [
				'onlyMediumPosition' => false,
				'mediumPositionPercent' => 80,
			],
		];
	}

	public function getId(): string
	{
		return 'timeman';
	}

	public function getIconId(): string
	{
		return 'timer';
	}

	public function getMessageCode(): string
	{
		return 'AVA_MENU_NAME_TIMEMAN_MSGVER_1';
	}
}
