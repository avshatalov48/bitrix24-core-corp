<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\CalendarMobile\JSComponent;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Component\SocNetFeatures;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;

class Calendar implements Tabable
{
	private const INITIAL_COMPONENT = 'calendar:calendar.events';

	/** @var Context $context */
	private $context;

	public function isAvailable(): bool
	{
		if (
			ModuleManager::isModuleInstalled('socialnetwork')
			&& (new SocNetFeatures($this->context->userId))->isEnabledForGroup('calendar')
			&& $this->isCalendarMobileEnabled()
			&& !$this->context->extranet
		)
		{
			return true;
		}

		return false;
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'sort' => $this->defaultSortValue(),
			'imageName' => 'calendar',
			'badgeCode' => 'calendar_all_no_events',
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData(): array
	{
		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'color' => '#00ace3',
			'imageUrl' => 'favorite/icon-calendar.png',
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => 'calendar_all_no_events',
			]
		];
	}

	public function shouldShowInMenu(): bool
	{
		return false;
	}

	public function canBeRemoved(): bool
	{
		return true;
	}

	public function defaultSortValue(): int
	{
		return 500;
	}

	public function canChangeSort(): bool
	{
		return true;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_CALENDAR');
	}

	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getShortTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_CALENDAR_SHORT');
	}

	public function getId(): string
	{
		return 'calandar';
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => $this->getId(),
			'scriptPath' => Manager::getComponentPath(self::INITIAL_COMPONENT),
			'rootWidget' => [
				'name' => 'list',
				'settings' => [
					'title' => $this->getTitle(),
					'useLargeTitleMode' => true,
					'objectName' => 'list',
				],
			],
			'params' => [],
		];
	}

	private function isCalendarMobileEnabled(): bool
	{
		if (Loader::includeModule('calendarmobile'))
		{
			return JSComponent::isUsed();
		}

		return false;
	}
}
