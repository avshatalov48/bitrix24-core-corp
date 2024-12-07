<?php

namespace Bitrix\Crm\Service\Communication\Category;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Loader;

final class CategoryFactory
{
	use Singleton;

	public function getCategoryHandlerInstance(Category $category): ?CategoryInterface
	{
		if (Loader::includeModule($category->getModuleId()))
		{
			return new ($category->getHandlerClass());
		}

		return null;
	}
}