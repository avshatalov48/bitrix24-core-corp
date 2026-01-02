<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\CalendarMobile\JSComponent;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Mobile\Menu\Analytics;

class Calendar implements Tabable
{
	private const INITIAL_COMPONENT = 'calendar:calendar.event.list';

	/** @var Context $context */
	private $context;

	public function isAvailable(): bool
	{
		$enabled = Loader::includeModule('intranet') && ToolsManager::getInstance()->checkAvailabilityByToolId('calendar');

		if (
			!$enabled
			|| !Loader::includeModule('calendar')
			|| !$this->isCalendarMobileEnabled()
		)
		{
			return false;
		}

		return !$this->context->extranet || $this->context->isCollaber;
	}

	public function getData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return [
			'id' => $this->getId(),
			'sort' => $this->defaultSortValue(),
			'imageName' => "calendar_with_slots",
			'badgeCode' => $this->getId(),
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData(): array
	{
		return [
			'id' => $this->getId(),
			'section_code' => 'teamwork',
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'color' => '#F5A200',
			'imageUrl' => 'favorite/icon-calendar.png',
			'imageName' => $this->getIconId(),
			'sort' => 100,
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => $this->getId(),
				'analytics' => Analytics::calendar(),
			],
		];
	}

	public function shouldShowInMenu(): bool
	{
		return $this->isAvailable();
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
		return 'calendar';
	}

	public function getComponentParams(): array
	{
		return [
			'title' => $this->getTitle(),
			'type' => 'component',
			'name' => 'JSStackComponent',
			'componentCode' => self::INITIAL_COMPONENT,
			'scriptPath' => Manager::getComponentPath(self::INITIAL_COMPONENT),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'titleParams' => [
						'text' => $this->getTitle(),
						'type' => 'section',
					],
					'useLargeTitleMode' => true,
					'objectName' => 'layout',
				],
			],
			'params' => [
				'CAL_TYPE' => 'user',
				'OWNER_ID' => $this->context->userId,
			],
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

	public function getIconId(): string
	{
		return "calendar_with_slots";
	}
}
