<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid\Enricher;

use Bitrix\Crm\Item;
use Bitrix\Crm\ProductRow;
use Bitrix\CrmMobile\ProductGrid\ProductRowViewModel;
use Bitrix\CrmMobile\ProductGrid\TaxCalculator;
use Bitrix\Mobile\Integration\Catalog\ProductGrid\SkuDataProvider;

final class CompletePrices implements EnricherContract
{
	private TaxCalculator $taxCalculator;

	private array $productsInfo = [];

	private Item $entity;

	public function __construct(TaxCalculator $taxCalculator, Item $entity)
	{
		$this->taxCalculator = $taxCalculator;
		$this->entity = $entity;
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 * @return ProductRowViewModel[]
	 */
	public function enrich(array $rows): array
	{
		$this->loadProductData($rows);

		foreach ($rows as &$productRow)
		{
			$productData = $this->getProductData($productRow);
			$basePrice = $productData['BASE_PRICE']['PRICE'] ?? 0.0;
			$baseCurrency = $productData['BASE_PRICE']['CURRENCY'] ?? null;
			$vatId = $productData['VAT_ID'] ?? 0;
			$vatIncluded = ($productData['VAT_INCLUDED'] ?? 'N') === 'Y';

			$this->taxCalculator->calculate((float)$basePrice, (int)$vatId, $vatIncluded);

			$productRow->source = $this->rebuild($productRow->source, [
				'PRICE' => $this->taxCalculator->getFinalPrice(),
				'TAX_RATE' => $this->taxCalculator->getTaxRate(),
				'TAX_INCLUDED' => $this->taxCalculator->isTaxIncluded() ? 'Y' : 'N',
			]);

			if ($baseCurrency)
			{
				$productRow->currencyId = $baseCurrency;
			}
		}

		return $rows;
	}

	private function rebuild(ProductRow $source, array $mutations): ProductRow
	{
		$sourceFields = $source->toArray();
		$result = ProductRow::createFromArray(array_merge(
			$sourceFields,
			$mutations
		));
		$this->entity->addToProductRows($result);
		return $result;
	}

	/**
	 * @param ProductRowViewModel[] $rows
	 */
	private function loadProductData(array $rows): void
	{
		$productIds = array_map(fn($item) => $item->getProductId(), $rows);
		$this->productsInfo = SkuDataProvider::load($productIds);
	}

	private function getProductData(ProductRowViewModel $productRow): array
	{
		return $this->productsInfo[$productRow->getProductId()] ?? [];
	}
}
