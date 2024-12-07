<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;

class Calendar extends AbstractMenuItem
{
	public function isAvailable(): bool
	{
		$enabled = Loader::includeModule('intranet')
			&& ToolsManager::getInstance()->checkAvailabilityByToolId('calendar')
		;

		if (
			!$enabled
			|| !Loader::includeModule('calendar')
			|| !Loader::includeModule('calendarmobile')
		)
		{
			return false;
		}

		return !$this->context->extranet;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'iconName' => $this->getIconId(),
			'counter' => $this->getCounter(),
			'customData' => $this->getEntryParams(),
		];
	}

	private function getEntryParams(): array
	{
		return (new \Bitrix\Mobile\AppTabs\Calendar())->getComponentParams();
	}

	public function getId(): string
	{
		return 'calendar';
	}

	public function getIconId(): string
	{
		return 'calendar_with_slots';
	}

	private function getCounter(): string
	{
		$userId = CurrentUser::get()->getId();

		$value = \CUserCounter::GetValue($userId, 'calendar') ?: 0;

		return "$value";
	}
}