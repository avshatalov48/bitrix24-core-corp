<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Catalog\v2\Integration\Landing\StoreV3Master;
use Bitrix\Landing\Site;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\SalesCenter\Integration\CatalogManager;

class Store extends JsonController
{
	public function getStoreInfoAction(): ?array
	{
		if (!Loader::includeModule('catalog') || !Loader::includeModule('landing'))
		{
			$this->addError(new Error('Required modules are not installed.'));

			return null;
		}

		$result = null;

		if (!StoreV3Master::hasStore())
		{
			$result = StoreV3Master::addNewStore();
			if (!$result->isSuccess())
			{
				$this->addErrors($result->getErrors());

				return null;
			}
		}

		$info = [
			'id' => StoreV3Master::getStoreId(),
			'link' => StoreV3Master::getStoreUrl(),
		];

		if ($result && $result->isSuccess())
		{
			$deactivatedStore = $result->getData()['deactivatedStore'] ?? null;
			if ($deactivatedStore)
			{
				$info['deactivatedStore'] = $deactivatedStore;
			}
		}

		return $info;
	}

	public function getLinkToProductCollectionAction(array $productIds): ?array
	{
		$compilationLinkResult = CatalogManager::getInstance()->getLinkToProductCompilationPreview($productIds);
		if (!$compilationLinkResult->isSuccess())
		{
			$this->addErrors($compilationLinkResult->getErrors());

			return null;
		}

		return $compilationLinkResult->getData();
	}
}
