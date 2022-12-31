<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;
use CCrmCompany;
use CCrmContact;
use CCrmDeal;
use CCrmPerms;

class Crm implements Tabable
{
	private const INITIAL_COMPONENT = 'crm:crm.tabs';
	private const MINIMAL_API_VERSION = 45;

	/** @var Context $context */
	private $context;

	public function isAvailable(): bool
	{
		if (!Loader::includeModule('crm') || !Loader::includeModule('crmmobile'))
		{
			return false;
		}

		if (
			Mobile::getApiVersion() < self::MINIMAL_API_VERSION
			|| !\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
		)
		{
			return false;
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();

		return (
			CCrmContact::IsAccessEnabled($userPermissions)
			|| CCrmCompany::IsAccessEnabled($userPermissions)
			|| CCrmDeal::IsAccessEnabled($userPermissions)
		);
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

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu(): bool
	{
		return true;
	}

	public function getMenuData(): ?array
	{
		return [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'min_api_version' => self::MINIMAL_API_VERSION,
			'color' => '#00ace3',
			'imageUrl' => 'favorite/icon-crm.png',
			'params' => [
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
}
