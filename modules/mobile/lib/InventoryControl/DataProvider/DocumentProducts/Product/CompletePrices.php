<?php

namespace Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Product;

use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts\Document;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;

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
			$document = Document::load($record->documentId);
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

			$completedRecord = clone $record;
			$completedRecord->price = [
				'purchase' => [
					'amount' => $purchasingPrice,
					'currency' => $currency,
				],
				'sell' => [
					'amount' => $sellPrice,
					'currency' => $currency,
				]
			];

			$result[] = $completedRecord;

		}

		return $result;
	}
}
