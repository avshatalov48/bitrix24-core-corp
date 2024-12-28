<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Crm\Integration\Intranet\CustomSection;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;

final class CrmCustomSection implements Tabable
{
	private const INITIAL_COMPONENT = 'crm:crm.tabs';

	private const MINIMAL_API_VERSION = 45;
	private const ID_PREFIX = 'crm_custom_section';
	private const MAX_SHORT_TITLE_LENGTH = 14;

	/** @var Context $context */
	private $context;

	private CustomSection $customSection;
	private array $pages;

	public static function getCode(int $id): string
	{
		return self::ID_PREFIX . '-' . $id;
	}

	public function __construct(CustomSection $customSection)
	{
		$this->customSection = $customSection;
		$this->pages = $customSection->getPages();
	}

	public function isAvailable(): bool
	{
		if (!$this->isModulesIncludedAndCustomSectionsAvailable())
		{
			return false;
		}

		if (!\Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager()->checkExternalDynamicAvailability())
		{
			return false;
		}

		if (Mobile::getApiVersion() < self::MINIMAL_API_VERSION)
		{
			return false;
		}

		$userPermissions = Container::getInstance()->getUserPermissions();

		foreach ($this->pages as $page)
		{
			$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($page->getSettings());
			if ($userPermissions->checkReadPermissions($entityTypeId))
			{
				return true;
			}
		}

		return false;
	}

	private function isModulesIncludedAndCustomSectionsAvailable(): bool
	{
		return (
			Loader::includeModule('crm')
			&& Loader::includeModule('crmmobile')
			&& IntranetManager::isCustomSectionsAvailable()
		);
	}

	public function getData(): array
	{
		return [
			'id' => $this->getId(),
			'sort' => $this->defaultSortValue(),
			'imageName' => $this->getIconId(),
			'badgeCode' => 'crm_custom_section_' . $this->customSection->getId(),
			'component' => $this->getComponentParams(),
		];
	}

	public function getMenuData(): array
	{
		return [
			'title' => $this->getShortTitle(),
			'useLetterImage' => true,
			'min_api_version' => self::MINIMAL_API_VERSION,
			'color' => '#00ace3',
			'imageUrl' => 'favorite/icon-crm_custom_section.png',
			'imageName' => $this->getIconId(),
			'params' => [
				'onclick' => Utils::getComponentJSCode($this->getComponentParams()),
				'counter' => 'crm_custom_section_' . $this->customSection->getId(),
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
		return $this->customSection->getTitle();
	}

	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getShortTitle(): ?string
	{
		return TruncateText($this->customSection->getTitle(), self::MAX_SHORT_TITLE_LENGTH);
	}

	public function getId(): string
	{
		return self::getCode($this->customSection->getId());
	}

	private function getComponentParams(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => $this->getTitle(),
			'componentCode' => self::INITIAL_COMPONENT,
			'scriptPath' => Manager::getComponentPath(self::INITIAL_COMPONENT),
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
					'useLargeTitleMode' => true,
				],
			],
			'params' => [
				'customSectionId' => $this->customSection->getId(),
			],
		];
	}

	public function getIconId(): string
	{
		return Mobile::getApiVersion() < 56 ?  self::ID_PREFIX : 'activity';
	}
}
