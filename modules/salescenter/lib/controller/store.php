<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Catalog\v2\Integration\Landing\ProductTokenizer;
use Bitrix\Catalog\v2\Integration\Landing\StoreV3Master;
use Bitrix\Landing\Site;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

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
		if (!Loader::includeModule('catalog') || !Loader::includeModule('landing'))
		{
			$this->addError(new Error('Required modules are not installed.'));

			return null;
		}

		$storeId = StoreV3Master::getStoreId();
		if ($storeId === null)
		{
			$this->addError(new Error('Can not find store.'));

			return null;
		}

		$storeUrl = Site::getPublicUrl($storeId);
		if ($storeUrl === '')
		{
			$this->addError(new Error('Can not build store public url.'));

			return null;
		}

		$uri = new Uri($storeUrl);
		$uri = ProductTokenizer::mixIntoUri($uri, $productIds);

		return [
			'id' => $storeId,
			'link' => $uri->getLocator(),
		];
	}
}
