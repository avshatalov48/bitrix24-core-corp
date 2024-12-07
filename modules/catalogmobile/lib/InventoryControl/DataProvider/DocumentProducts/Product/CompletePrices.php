<?php

namespace Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\VatTable;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts\Document;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Sale\Tax\VatCalculator;

Loader::includeModule('catalog');
Loader::includeModule('currency');

final class CompletePrices implements Enricher
{
	/**
	 * @param DocumentProductRecord[] $records
	 * @return DocumentProductRecord[]
	 */
	public function enrich(array $records): array
	{
		$result = [];
		foreach ($records as $record)
		{
			$document = Document::load($record->documentId, $record->documentType);
			$currency = $document->currency;

			$sku = null;
			$purchasingPrice = 0.0;
			$sellPrice = 0.0;
			$repositoryFacade = ServiceContainer::getRepositoryFacade();
			if ($repositoryFacade && $record->productId)
			{
				$sku = $repositoryFacade->loadVariation($record->productId);
			}

			if ($sku)
			{
				$purchasingPrice = $sku->getField('PURCHASING_PRICE');
				$purchasingCurrency = $sku->getField('PURCHASING_CURRENCY');

				if ($purchasingCurrency !== $currency)
				{
					$purchasingPrice = \CCurrencyRates::ConvertCurrency(
						$purchasingPrice,
						$purchasingCurrency,
						$currency
					);
				}

				$basePrice = $sku->getPriceCollection()->findBasePrice();
				if ($basePrice)
				{
					$sellPrice = $basePrice->getPrice() ?? 0.0;
					$sellCurrency = $basePrice->getCurrency();

					if ($sellCurrency !== $currency)
					{
						$sellPrice = \CCurrencyRates::ConvertCurrency(
							$sellPrice,
							$sellCurrency,
							$currency
						);
					}
				}
			}

			$vatId = $sku->getField('VAT_ID');
			$vatIncluded = $sku->getField('VAT_INCLUDED');
			$vat = (int)$vatId ? VatTable::getRowById($vatId) : [];
			$vatRate = $vat['RATE'] ?? null;
			$priceWithVat = $sellPrice;
			$vatValue = 0;

			if ($vatRate !== null)
			{
				$vatRate = $vatRate / 100;
				$isVatInPrice = ($vatIncluded === 'Y');
				$vatCalculator = new VatCalculator($vatRate);

				$priceWithVat = $isVatInPrice
					? $priceWithVat
					: $vatCalculator->accrue($sellPrice);

				$vatValue = $vatCalculator->calc(
					$sellPrice,
					$isVatInPrice
				);
			}

			$completedRecord = clone $record;
			$completedRecord->price = [
				'purchase' => [
					'amount' => $purchasingPrice,
					'currency' => $currency,
				],
				'sell' => [
					'amount' => $sellPrice,
					'currency' => $currency,
				],
				'vat' => [
					'priceWithVat' => $priceWithVat,
					'vatRate' => $vatRate,
					'vatIncluded' => $vatIncluded,
					'vatValue' => $vatValue,
				],
			];

			$result[] = $completedRecord;

		}

		return $result;
	}
}
