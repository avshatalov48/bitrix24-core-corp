<?php

namespace Bitrix\Mobile\AvaMenu\Items;

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Mobile\AvaMenu\AbstractMenuItem;

class Calendar extends AbstractMenuItem
{
	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isAvailable(): bool
	{
		$manager = new \Bitrix\Mobile\Tab\Manager($this->context);
		$activeTabs = $manager->getActiveTabs();

		if (isset($activeTabs['calendar']))
		{
			return false;
		}

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

		return !$this->context->extranet || $this->context->isCollaber;
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
		$tab = new \Bitrix\Mobile\AppTabs\Calendar();
		$tab->setContext($this->context);

		return $tab->getComponentParams();
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

		return (string)$value;
	}
}
