<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Mobile\Trait\PublicErrorsTrait;
use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\ProductGridQuery;
use Bitrix\CrmMobile\ProductGrid\SingleProductQuery;
use Bitrix\CrmMobile\ProductGrid\SkuCollectionQuery;
use Bitrix\CrmMobile\ProductGrid\SummaryQuery;
use Bitrix\CrmMobile\ProductGrid\ConvertCurrencyQuery;
use Bitrix\CrmMobile\ProductGrid\CompleteStoresQuery;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\CrmMobile\Controller\BaseJson;
use Bitrix\Main\Loader;

Loader::requireModule('crmmobile');

class ProductGrid extends BaseJson
{
	use PrimaryAutoWiredEntity;
	use PublicErrorsTrait;

	protected function init()
	{
		parent::init();

		define('BX_MOBILE', true);
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
			new ActionFilter\CloseSession(),
			new CheckReadPermission(),
		];
	}

	public function loadProductsAction(Item $entity, ?string $currencyId = null): array
	{
		return (new ProductGridQuery($entity, $currencyId))->execute();
	}

	public function loadProductGridSummaryAction(
		Item $entity,
		array $products = [],
		?string $currencyId = null
	): array
	{
		return (new SummaryQuery($entity, $products, $currencyId))->execute();
	}

	public function loadProductModelAction(
		Item $entity,
		int $productId,
		string $currencyId
	): array
	{
		return (new SingleProductQuery($entity, $productId, $currencyId))->execute();
	}

	public function loadSkuCollectionAction(
		Item $entity,
		int $variationId,
		string $currencyId
	): array
	{
		return (new SkuCollectionQuery($entity, $variationId, $currencyId))->execute();
	}

	public function convertCurrencyAction(
		Item $entity,
		string $currencyId,
		array $products = []
	): array
	{
		return (new ConvertCurrencyQuery($entity, $currencyId, $products))->execute();
	}

	public function completeStoresAction(Item $entity, array $products = []): array
	{
		return (new CompleteStoresQuery($entity, $products))->execute();
	}
}
