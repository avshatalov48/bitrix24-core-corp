<?php

namespace Bitrix\Crm\Integration\Intranet;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Intranet\CustomSection\Provider;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Web\Uri;

class CustomSectionProvider extends Provider
{
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
}
