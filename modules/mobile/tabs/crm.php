<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;

class Crm implements Tabable
{
	private const INITIAL_COMPONENT = 'crm:crm.tabs';
	private const MINIMAL_API_VERSION = 45;

	/** @var Context $context */
	private $context;

	public function isAvailable(): bool
	{
		if (
			!Loader::includeModule('crm')
			|| !Loader::includeModule('crmmobile')
		)
		{
			return false;
		}

		if (Mobile::getApiVersion() < self::MINIMAL_API_VERSION)
		{
			return false;
		}

		if (!\Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager()->checkCrmAvailability())
		{
			return false;
		}

		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return false;
		}

		return \CCrmPerms::IsAccessEnabled();
	}

	public function getData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return [
			'id' => 'crm',
			'sort' => $this->defaultSortValue(),
			'imageName' => 'crm',
			'badgeCode' => 'crm_all_no_orders',
			'component' => $this->getComponentParams(),
		];
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => Loc::getMessage('TAB_NAME_CRM'),
			'componentCode' => self::INITIAL_COMPONENT,
			'scriptPath' => Manager::getComponentPath(self::INITIAL_COMPONENT),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
					'useLargeTitleMode' => true,
				],
			],
			'params' => [],
		];
	}

	public function shouldShowInMenu(): bool
	{
		return $this->isAvailable();
	}

	public function getMenuData(): ?array
	{
		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'min_api_version' => self::MINIMAL_API_VERSION,
			'color' => '#00ace3',
			'imageUrl' => 'favorite/icon-crm.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'id' => 'crm_tabs',
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => 'crm_all_no_orders',
			],
		];
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
		return Loc::getMessage('TAB_NAME_CRM');
	}

	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getShortTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_CRM');
	}

	public function getId(): string
	{
		return 'crm';
	}

	public function getIconId(): string
	{
		return $this->getId();
	}
}
