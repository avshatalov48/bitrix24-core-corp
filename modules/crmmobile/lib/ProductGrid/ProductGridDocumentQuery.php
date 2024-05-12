<?php

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Catalog\VatTable;
use Bitrix\CatalogMobile\Catalog;
use Bitrix\CatalogMobile\PermissionsProvider;
use Bitrix\CatalogMobile\ProductGrid\SkuDataProvider;
use Bitrix\CatalogMobile\Repository\MeasureRepository;
use Bitrix\Crm;
use Bitrix\Crm\Service\Accounting;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Dto\VatRate;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Mobile\UI\File;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Repository\PaymentRepository;
use Bitrix\Sale\Tax\VatCalculator;

Loader::requireModule('crm');
Loader::requireModule('catalogmobile');

final class ProductGridDocumentQuery
{
	protected int $documentId;
	protected Accounting $accounting;
	protected PermissionsProvider $permissionsProvider;
	private array $productsInfo;
	private ?Payment $payment = null;

	public function __construct(int $documentId)
	{
		$this->documentId = $documentId;
		$this->accounting = Container::getInstance()->getAccounting();
		$this->permissionsProvider = PermissionsProvider::getInstance();
		$this->productsInfo = [];
	}

	public function execute(): array
	{
		$products = $this->fetchItems();
		$query = $this->getSummaryQuery($products);

		return [
			'entity' => $this->prepareEntityData(),
			'products' => $products,
			'summary' => $query->execute(),
			'catalog' => [
				'id' => Catalog::getDefaultId(),
				'basePriceId' => Catalog::getBasePrice(),
				'currencyId' => Catalog::getBaseCurrency(),
			],
			'inventoryControl' => [
				'isAllowedReservation' => null,
				'isReservationRestrictedByPlan' => null,
				'defaultDateReserveEnd' => null,
			],
			'measures' => array_values(MeasureRepository::findAll()),
			'taxes' => [
				'vatRates' => $this->fetchVatRates(),
				'productRowTaxUniform' => $this->isProductRowTaxUniform(),
			],
			'permissions' => PermissionsProvider::getInstance()->getPermissions(),
		];
	}

	protected function getSummaryQuery(array $products): DocumentSummaryQuery
	{
		return new DocumentSummaryQuery(
			$this->documentId,
			$products,
			$this->payment->getOrder()->getCurrency()
		);
	}

	private function fetchVatRates(): array
	{
		$vatRates = \CCrmTax::GetVatRateInfos();
		return array_map(static fn($fields) => VatRate::make($fields), $vatRates);
	}

	private function isProductRowTaxUniform(): bool
	{
		return Option::get('crm', 'product_row_tax_uniform', 'Y') === 'Y';
	}

	private function fetchItems(): array
	{
		$items = [];
		$rows = $this->getProductRows();
		$productIds = array_map(fn($row) => $row['PRODUCT_ID'], $rows);
		$this->productsInfo = SkuDataProvider::load($productIds);

		foreach ($rows as $row)
		{
			$model = ProductRowViewModel::createFromArray($row)->toArray();
			$model = $this->completePrices($model, $row);
			$model = $this->completeGallery($model);
			$model = $this->completeSkuTree($model);
			$items[] = $model;
		}

		return $items;
	}

	private function completeGallery(array $model): array
	{
		$model['GALLERY'] = [];
		$productInfo = $this->productsInfo[$model['PRODUCT_ID']] ?? [];
		foreach ($productInfo['GALLERY'] ?? [] as $file)
		{
			$model['GALLERY'][] = File::loadWithPreview($file['ID']);
		}

		return $model;
	}

	private function completeSkuTree(array $model): array
	{
		$productInfo = $this->productsInfo[$model['PRODUCT_ID']] ?? [];
		$model['SKU_TREE'] = $productInfo['SKU_TREE'] ?? [];

		return $model;
	}

	private function getProductRows(): array
	{
		$productList = [];
		$this->payment = PaymentRepository::getInstance()->getById($this->documentId);
		if (!$this->payment)
		{
			return [];
		}
		/** @var Crm\Order\PayableItemCollection $shipmentItemCollection */
		$payableItemCollection = $this->payment->getPayableItemCollection()->getBasketItems();

		/** @var Crm\Order\PayableBasketItem $payableItem */
		foreach ($payableItemCollection as $payableItem)
		{
			$entity = $payableItem->getEntityObject();
			if (!$entity)
			{
				return [];
			}

			$item = $entity->getFieldValues();
			$item['BASKET_CODE'] = $entity->getBasketCode();
			$item['QUANTITY'] = $payableItem->getQuantity();

			$productList[] = $item;
		}

		return $productList;
	}

	private function completePrices($model, $row): array
	{
		$vatRate = null;
		if (array_key_exists('VAT_RATE', $row))
		{
			$vatRate =
				(string)$row['VAT_RATE'] !== ''
					? (float)$row['VAT_RATE']
					: null
			;

			if (\Bitrix\Main\Loader::includeModule('catalog'))
			{
				$vatId =
					isset($vatRate)
						? VatTable::getActiveVatIdByRate($vatRate * 100)
						: VatTable::getExcludeVatId()
				;
				if (isset($vatId))
				{
					$model['TAX_ID'] = $vatId;
				}
			}
		}

		$vatIncluded = $row['VAT_INCLUDED'] ?? 'Y';
		$price = (float)$row['PRICE'];
		$basePrice = (float)$row['BASE_PRICE'];
		$vatCalculator = new VatCalculator((float)$vatRate);

		$model['TAX_INCLUDED'] = $vatIncluded;
		$model['TAX_RATE'] = $vatRate * 100;
		$model['DISCOUNT_SUM'] = $row['DISCOUNT_PRICE'];
		$model['DISCOUNT_TYPE_ID'] = Crm\Discount::MONETARY;
		$model['BASE_PRICE'] = $row['BASE_PRICE'];
		$model['PRICE'] = $price;
		$model['PRICE_ACCOUNT'] = $price;

		if ($vatIncluded === 'N')
		{
			$vatSum = $vatCalculator->calc($price, false);
			$model['PRICE_EXCLUSIVE'] = $price - $vatSum;
			$model['PRICE_BRUTTO'] = $vatCalculator->accrue($basePrice);
			$model['PRICE_NETTO'] = $basePrice;
			$model['TAX_SUM'] = $vatSum;
		}
		else
		{
			$vatSum = $vatCalculator->calc($price, true);
			$model['PRICE_EXCLUSIVE'] = $price - $vatSum;
			$model['PRICE_BRUTTO'] = $basePrice;
			$model['PRICE_NETTO'] = $vatCalculator->allocate($basePrice);
			$model['TAX_SUM'] = $vatSum;
		}

		return $model;
	}

	private function prepareEntityData(): array
	{
		return [
			'id' => $this->documentId,
			'editable' => false,
		];
	}
}
