<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Integration\Analytics\Builder\Security\ViewEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Integration\Intranet\SystemPageProvider\ActivityPage;
use Bitrix\Crm\Integration\Intranet\SystemPageProvider\PermissionsPage;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Crm\Service\Router;
use Bitrix\Intranet\CustomSection\DataStructures\CustomSection;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Manager;
use Bitrix\Intranet\CustomSection\Provider;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Web\Uri;

class CustomSectionProvider extends Provider
{
	public const COUNTER_PREFIX = 'crm_custom_page_';

	public static function getSystemPageProviders(): array
	{
		/**
		 * WARNING! Don't access Crm Router here and in `getSystemPages`.
		 *
		 * It will cause infinite recursion since \Bitrix\Crm\Service\Router::__construct gets info about custom
		 * section pages.
		 */

		return [
			ActivityPage::CODE => ActivityPage::class,
			PermissionsPage::CODE => PermissionsPage::class,
		];
	}

	public static function getEntityTypeByPageSetting(string $pageSetting): ?int
	{
		return IntranetManager::getEntityTypeIdByPageSettings($pageSetting);
	}

	public static function getEntityTypeIdByCounterId(string $code): ?int
	{
		return IntranetManager::getEntityTypeIdByPageSettings(self::getPageSettingsByCounterId($code));
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(string $pageSettings, int $userId): bool
	{
		if (self::isSystemPage($pageSettings))
		{
			return self::checkIfsystemPageIsAvailable($pageSettings);
		}

		$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($pageSettings);
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return false;
		}

		$userPermissions = Container::getInstance()->getUserPermissions($userId);

		return
			$userPermissions->checkReadPermissions($entityTypeId)
			|| $userPermissions->canUpdateType($entityTypeId)
		;
	}

	/**
	 * @inheritDoc
	 */
	public function resolveComponent(string $pageSettings, Uri $url): ?Component
	{
		if (self::isSystemPage($pageSettings))
		{
			$systemPageCode = self::getSystemPageCode($pageSettings);
			$pageProviders = static::getSystemPageProviders();
			$dataClass = $pageProviders[$systemPageCode] ?? null;

			if (is_subclass_of($dataClass, SystemPageProvider::class))
			{
				return $dataClass::getComponent($pageSettings, $url);
			}

			return null;
		}

		$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($pageSettings);
		if (is_null($entityTypeId) || !\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return null;
		}

		$customSections = IntranetManager::getCustomSections();
		if (is_null($customSections))
		{
			return null;
		}

		$router = Container::getInstance()->getRouter();
		$componentParameters = [];
		foreach ($customSections as $section)
		{
			foreach ($section->getPages() as $page)
			{
				if ($page->getSettings() !== $pageSettings)
				{
					continue;
				}

				$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($page->getSettings());
				if ($entityTypeId > 0)
				{
					$url = IntranetManager::getUrlForCustomSectionPage($section->getCode(), $page->getCode());
					$componentParameters = [
						'root' => $url?->getPath(),
					];

					$listView = $router->getCurrentListView($entityTypeId);
					$router->setDefaultComponent($listView === Router::LIST_VIEW_LIST
						? 'bitrix:crm.item.list'
						: 'bitrix:crm.item.kanban'
					);
					$router->setDefaultComponentParameters([
						'entityTypeId' => $entityTypeId,
					]);
				}
			}
		}

		return (new Component())
			->setComponentTemplate('')
			->setComponentName('bitrix:crm.router')
			->setComponentParams($componentParameters)
		;
	}

	public function getCounterId(string $pageSettings): ?string
	{
		return self::COUNTER_PREFIX . $pageSettings;
	}

	public function getCounterValue(string $pageSettings): ?int
	{
		return EntityCounterFactory::createNamed($this->getCounterId($pageSettings))?->getValue();
	}

	public static function getPageSettingsByCounterId(string $counterId): string
	{
		return preg_replace(sprintf("#^%s#", self::COUNTER_PREFIX), '', $counterId);
	}

	public static function isCustomSectionPageCounter(string $counterId): bool
	{
		return preg_match(sprintf("#^%s#", self::COUNTER_PREFIX), $counterId);
	}

	public static function isCustomSectionCounter(string $code): bool
	{
		return
			method_exists(Manager::class, 'isCustomSectionCounter')
			&& Manager::isCustomSectionCounter($code, 'crm');
	}

	public static function getCustomSectionIdByCounterId(string $code): int
	{
		return
			method_exists(Manager::class, 'getCustomSectionIdByCounterId')
				? Manager::getCustomSectionIdByCounterId($code, 'crm')
				: 0;
	}

	public static function getPagesSettingsByCustomSectionCounterId(string $code): array
	{
		$customSectionId = self::getCustomSectionIdByCounterId($code);

		return self::customSectionsCache()->getAllSettingsByCustomSectionId($customSectionId);
	}

	public static function hasCustomSection(Dynamic $factory): bool
	{
		$typeId = $factory->getEntityTypeId();
		$settingsName = IntranetManager::preparePageSettingsForItemsList($typeId);

		return self::customSectionsCache()->hasBySettingsName($settingsName);
	}

	/**
	 * @param int $entityTypeId
	 * @return int[]
	 */
	public static function getAllCustomSectionIdsByEntityTypeId(int $entityTypeId): array
	{
		$settingsName = IntranetManager::preparePageSettingsForItemsList($entityTypeId);
		return self::customSectionsCache()->getAllCustomSectionIdsBySettings($settingsName);
	}

	private static ?CrmCustomSectionCache $allAvailableCustomSections = null;

	private static function customSectionsCache(): CrmCustomSectionCache
	{
		if (self::$allAvailableCustomSections === null)
		{
			$sections = CustomSectionPageTable::getList([
				'select' => ['SETTINGS', 'CUSTOM_SECTION_ID'],
				'filter' => ['=MODULE_ID' => 'crm'],
				'cache' => ['ttl' => 3600],
			])->fetchAll();
			self::$allAvailableCustomSections = new CrmCustomSectionCache($sections);
		}
		return self::$allAvailableCustomSections;
	}

	public static function buildCustomSectionCounterId(int $customSectionId): string
	{
		// @fixMe remove the condition after Manager::buildCustomSectionCounterId will be released in the intranet
		if (method_exists(Manager::class, 'buildCustomSectionCounterId'))
		{
			return Manager::buildCustomSectionCounterId('crm', $customSectionId);
		}

		return 'crm_custom_section_' . $customSectionId;
	}

	/**
	 * Returns true if page is a system (added by the developer)
	 *
	 * @param string $pageSettings
	 * @return bool
	 */
	public static function isSystemPage(string $pageSettings): bool
	{
		$pageProviders = static::getSystemPageProviders();
		foreach ($pageProviders as $systemPageCode => $systemPageProvider)
		{
			if (str_starts_with($pageSettings, $systemPageCode))
			{
				return true;
			}
		}

		return false;
	}

	public static function isSystemPageCounter(string $code): bool
	{
		$pageProviders = static::getSystemPageProviders();
		foreach ($pageProviders as $systemPageCode => $systemPageProvider)
		{
			if (str_starts_with($code, self::COUNTER_PREFIX . $systemPageCode))
			{
				return true;
			}
		}

		return false;
	}

	public function getSystemPages(CustomSection $section, bool $ignorePageAvailability = false): array
	{
		$systemPages = [];

		$pageProviders = static::getSystemPageProviders();
		foreach ($pageProviders as $systemPageProvider)
		{
			/** @var SystemPageProvider $systemPageProvider */
			if (!$ignorePageAvailability && !$systemPageProvider::isPageAvailable($section))
			{
				continue;
			}

			$systemPage = $systemPageProvider::getPageInstance($section);
			if (!is_null($systemPage))
			{
				$systemPages[] = $systemPage;
			}
		}

		return $systemPages;
	}

	private static function checkIfSystemPageIsAvailable(string $pageSettings): bool
	{
		[$pageCode, $sectionCode] = self::getSystemPageCodeAndSectionCode($pageSettings);
		$pageProviders = static::getSystemPageProviders();
		if (!isset($pageProviders[$pageCode]))
		{
			return false;
		}
		$customSections = IntranetManager::getCustomSections();
		$currentUser = Container::getInstance()->getContext()->getUserId();
		$provider = new self();
		foreach ($customSections as $customSection)
		{
			if ($customSection->getCode() === $sectionCode)
			{
				$pages = $customSection->getPages();
				// system pages are available only if any entity pages are available:
				foreach ($pages as $page)
				{
					if (
						!self::isSystemPage($page->getSettings())
						&& $provider->isAvailable($page->getSettings(), $currentUser)
					)
					{
						return true;
					}
				}

				break;
			}
		}

		return false;
	}

	private static function getSystemPageCode(string $pageSettings): string
	{
		[$pageCode] = self::getSystemPageCodeAndSectionCode($pageSettings);

		return $pageCode;
	}

	private static function getSystemPageCodeAndSectionCode(string $pageSettings): array
	{
		return explode(Provider::PAGE_SETTINGS_SEPARATOR, $pageSettings);
	}

	public function getAnalytics(string $pageSettings): array
	{
		if (self::isSystemPage($pageSettings))
		{
			if (self::getSystemPageCode($pageSettings) === PermissionsPage::CODE)
			{
				return (new ViewEvent())
					->setSection(Dictionary::SECTION_CUSTOM)
					->setSubSection(Dictionary::SUB_SECTION_CONTROL_PANEL)
					->buildData()
				;
			}
		}

		return [];
	}
}
