<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\Dynamic;
use Bitrix\Crm\Service\Router;
use Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable;
use Bitrix\Intranet\CustomSection\Manager;
use Bitrix\Intranet\CustomSection\Provider;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Web\Uri;

class CustomSectionProvider extends Provider
{
	public const COUNTER_PREFIX = 'crm_custom_page_';

	public static function getEntityTypeByPageSetting(string $pageSetting): ?int
	{
		return IntranetManager::getEntityTypeIdByPageSettings($pageSetting);
	}

	public static function getEntityTypeIdByCounterId(string $code): ?int
	{
		return IntranetManager::getEntityTypeIdByPageSettings(CustomSectionProvider::getPageSettingsByCounterId($code));
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(string $pageSettings, int $userId): bool
	{
		$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($pageSettings);
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			return false;
		}

		return Container::getInstance()->getUserPermissions($userId)->checkReadPermissions($entityTypeId);
	}

	/**
	 * @inheritDoc
	 */
	public function resolveComponent(string $pageSettings, Uri $url): ?Component
	{
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
				$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($page->getSettings());
				if (($entityTypeId > 0) && ($page->getSettings() === $pageSettings))
				{
					$url = IntranetManager::getUrlForCustomSectionPage($section->getCode(), $page->getCode());
					$componentParameters = [
						'root' => ($url ? $url->getPath() : null),
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
		return EntityCounterFactory::createNamed($this->getCounterId($pageSettings))->getValue();
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
		\Bitrix\Intranet\CustomSection\Entity\CustomSectionPageTable::cleanCache();
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
		else
		{
			return 'crm_custom_section_' . $customSectionId;
		}
	}
}
