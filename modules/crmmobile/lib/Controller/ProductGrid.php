<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Crm\Engine\ActionFilter\CheckReadPermission;
use Bitrix\Crm\Item;
use Bitrix\CrmMobile\ProductGrid\ProductGridQuery;
use Bitrix\CrmMobile\ProductGrid\SingleProductQuery;
use Bitrix\CrmMobile\ProductGrid\SkuCollectionQuery;
use Bitrix\CrmMobile\ProductGrid\SummaryQuery;
use Bitrix\CrmMobile\ProductGrid\ConvertCurrencyQuery;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\JsonController;
use Bitrix\Main\Loader;

Loader::requireModule('crmmobile');

class ProductGrid extends JsonController
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
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
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

	public function loadProductModelAction(int $productId, string $currencyId, Item $entity): array
	{
		return (new SingleProductQuery($entity, $productId, $currencyId))->execute();
	}

	public function loadSkuCollectionAction(int $variationId, string $currencyId): array
	{
		return (new SkuCollectionQuery($variationId, $currencyId))->execute();
	}

	public function convertCurrencyAction(string $currencyId, array $products = []): array
	{
		return (new ConvertCurrencyQuery($currencyId, $products))->execute();
	}
}
