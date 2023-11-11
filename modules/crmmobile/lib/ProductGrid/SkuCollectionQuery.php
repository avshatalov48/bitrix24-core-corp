<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Catalog\v2\Barcode\Barcode;
use Bitrix\Catalog\v2\Facade\Repository;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Product\BaseProduct;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Accounting;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Query;
use Bitrix\Mobile\UI\File;

Loader::requireModule('crm');
Loader::requireModule('catalog');

final class SkuCollectionQuery extends Query
{
	private Item $entity;

	private int $variationId;

	private Accounting $accounting;

	private TaxCalculator $taxCalculator;

	private Repository $productsRepository;

	private string $currencyId;

	public function __construct(
		Item $entity,
		int $variationId,
		string $currencyId
	)
	{
		$this->entity = $entity;
		$this->variationId = $variationId;
		$this->currencyId = $currencyId;
		$this->accounting = Container::getInstance()->getAccounting();
		$this->taxCalculator = new TaxCalculator($this->accounting);
		$this->productsRepository = ServiceContainer::getRepositoryFacade();
	}

	public function execute(): array
	{
		$product = $this->findParentProduct();
		if (!$product)
		{
			return [];
		}

		$variations = [];

		$storeData = $this->getStoreData($product);
		foreach ($product->getSkuCollection() as $sku)
		{
			$fields = $sku->getFields();

			$basePriceFields = $sku->getPriceCollection()->findBasePrice()->getFields();
			$basePrice = $basePriceFields['PRICE'] ?? 0.0;
			$currencyId = $basePriceFields['CURRENCY'] ?? null;
			if ($currencyId && $currencyId !== $this->currencyId)
			{
				$basePrice = \CCrmCurrency::ConvertMoney($basePrice, $currencyId, $this->currencyId);
			}

			$vatId = $fields['VAT_ID'] ?? 0;
			$vatIncluded = ($fields['VAT_INCLUDED'] ?? 'N') === 'Y';

			$this->taxCalculator->calculate((float)$basePrice, (int)$vatId, $vatIncluded);

			$gallery = array_values(array_map(
				fn($item) => File::loadWithPreview($item['ID']),
				$sku->getFrontImageCollection()->toArray()
			));

			$skuId = $sku->getId();

			$variationItem = [
				'ID' => $skuId,
				'NAME' => $sku->getName(),
				'GALLERY' => $gallery,
				'PRICE' => $this->taxCalculator->getFinalPrice(),
				'CURRENCY' => $this->currencyId,
				'PRICE_BEFORE_TAX' => $this->taxCalculator->getPriceBeforeTax(),
				'TAX_VALUE' => $this->taxCalculator->getTaxValue(),
				'TAX_RATE' => $this->taxCalculator->getTaxRate(),
				'TAX_INCLUDED' => $this->taxCalculator->isTaxIncluded(),
				'TAX_NAME' => $this->taxCalculator->getVatName(),
				'TAX_MODE' => $this->accounting->isTaxMode(),
				'EMPTY_PRICE' => empty($basePriceFields['PRICE']),
				'BARCODE' => $this->findBarcode($sku),
			];

			if ($this->isAllowedReservation())
			{
				$store = $storeData[$skuId]['INITIAL_STORE'] ?? null;
				$storeId = $store ? $store['ID'] : null;
				$storeAmount = $storeData[$skuId]['STORES'][$storeId]['AMOUNT'] ?? null;
				$storeAvailableAmount =
					isset($storeAmount) && isset($storeData[$skuId]['STORES'][$storeId]['QUANTITY_RESERVED'])
						? $storeAmount - $storeData[$skuId]['STORES'][$storeId]['QUANTITY_RESERVED']
						: null
				;
				$shouldSyncReserveQuantity = ReservationService::getInstance()->isReserveEqualProductQuantity();

				$variationItem = array_merge(
					$variationItem,
					[
						'HAS_STORE_ACCESS' => true,
						'STORE_ID' => $store ? $store['ID'] : null,
						'STORE_NAME' => $store ? $store['TITLE'] : null,
						'STORE_AMOUNT' => $storeAmount,
						'STORE_AVAILABLE_AMOUNT' => $storeAvailableAmount,
						'STORES' =>
							isset($storeData[$skuId]['STORES'])
								? array_values($storeData[$skuId]['STORES'])
								: []
						,
						'SHOULD_SYNC_RESERVE_QUANTITY' => $shouldSyncReserveQuantity,
						'ROW_RESERVED' => 0,
						'DEDUCTED_QUANTITY' => 0,
					]
				);
			}

			$variations[$skuId] = $variationItem;
		}

		return [
			'variations' => $variations,
		];
	}

	private function findParentProduct(): ?BaseProduct
	{
		$sku = $this->productsRepository->loadVariation($this->variationId);
		if (!$sku)
		{
			return null;
		}

		/** @var BaseProduct $product */
		$product = $sku->getParent();

		return $product;
	}

	private function findBarcode(BaseSku $sku): string
	{
		$barcode = '';
		$barcodeEntity = $sku->getBarcodeCollection()->getFirst();

		/** @var Barcode|null $barcodeEntity */
		if ($barcodeEntity)
		{
			$barcode = $barcodeEntity->getBarcode() ?? '';
		}

		return $barcode;
	}

	private function getStoreData(BaseProduct $product): array
	{
		$skuIds = [];
		foreach ($product->getSkuCollection() as $sku)
		{
			$skuIds[] = $sku->getId();
		}

		return StoreDataProvider::provideStoreData($skuIds);
	}

	private function isAllowedReservation(): bool
	{
		return \CCrmSaleHelper::isAllowedReservation(
			$this->entity->getEntityTypeId(),
			$this->entity->getCategoryId() ?? 0
		);
	}
}
