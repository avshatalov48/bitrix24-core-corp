<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Service\Accounting;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteExtraFields;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompletePrices;
use Bitrix\CrmMobile\ProductGrid\Enricher\CompleteStores;
use Bitrix\CrmMobile\ProductGrid\Enricher\ConvertCurrency;
use Bitrix\CrmMobile\ProductGrid\Enricher\EnricherContract;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\PermissionsProvider;
use Bitrix\Mobile\Query;

Loader::requireModule('crm');
Loader::requireModule('catalogmobile');

class SingleProductQuery extends Query
{
	protected Item $entity;

	private string $currencyId;

	private int $productId;

	private Accounting $accounting;

	private TaxCalculator $taxCalculator;

	private PermissionsProvider $permissionsProvider;

	public function __construct(
		Item $entity,
		int $productId,
		?string $currencyId = null
	)
	{
		$this->entity = $entity;
		$this->productId = $productId;
		$this->currencyId = $currencyId ?? $this->entity->getCurrencyId();
		$this->accounting = Container::getInstance()->getAccounting();
		$this->permissionsProvider = PermissionsProvider::getInstance();
		$this->taxCalculator = new TaxCalculator($this->accounting);
	}

	public function execute(): array
	{
		$productRow = $this->initRow();

		$rows = [
			new ProductRowViewModel($productRow, $this->entity->getCurrencyId())
		];

		/** @var EnricherContract[] $enrichers */
		$enrichers = $this->getEnrichers();

		foreach ($enrichers as $enricher)
		{
			$rows = $enricher->enrich($rows);
		}

		return $rows[0]->toArray();
	}

	protected function getEnrichers(): array
	{
		return [
			new CompletePrices($this->taxCalculator, $this->entity),
			new ConvertCurrency($this->currencyId),
			new CompleteExtraFields($this->accounting, $this->permissionsProvider, $this->entity),
			new CompleteStores($this->entity),
		];
	}

	private function initRow(): ProductRow
	{
		$productRow = ProductRow::createFromArray(['PRODUCT_ID' => $this->productId]);
		$this->entity->addToProductRows($productRow);
		return $productRow;
	}
}
