<?php

namespace Bitrix\Sign\Service\Sign\B2e;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Sign\Item\B2e\KanbanCategory;
use Bitrix\Sign\Item\B2e\KanbanCategoryCollection;

final class KanbanCategoryService
{
	/**
	 * @return string[]
	 */
	public function getSmartB2eDocumentCategoryCodesForMenu(): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$container = Container::getInstance();
		if (!method_exists($container, 'getSignB2eIntegrationTypeService'))
		{
			return [];
		}

		return $container->getSignB2eIntegrationTypeService()->getCategoryCodesForMenu();
	}

	public function getSmartB2eDocumentCategories(): KanbanCategoryCollection
	{
		$categories = KanbanCategoryCollection::emptyList();

		if (!Loader::includeModule('crm'))
		{
			return $categories;
		}

		$container = Container::getInstance();
		if (method_exists($container, 'getSignB2eIntegrationTypeService'))
		{
			$categoriesArray = array_map(
				static fn(array $category): KanbanCategory => KanbanCategory::fromArray($category),
				$container->getSignB2eIntegrationTypeService()->getCategories(),
			);
			$categories = KanbanCategoryCollection::fromArray($categoriesArray);
		}

		return $categories;
	}
}