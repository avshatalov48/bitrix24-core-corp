<?php

namespace Bitrix\Crm\Order\ProductManager;

use Bitrix\Catalog;
use Bitrix\Crm\Discount;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketXmlId;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowXmlId;
use Bitrix\Crm\Order\ProductManager\ProductConverter\PricesConverter;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Internals\Catalog\ProductTypeMapper;
use Exception;

/**
 * Converter without reserve info.
 */
class EntityProductConverter implements ProductConverter
{
	private ?Basket $basket = null;
	private PricesConverter $pricesConverter;
	/**
	 * @var array Product to Type cache: [productId => type, …]
	 * */
	private array $catalogTypesCache = [];

	/**
	 * @throws Exception if not installed 'sale' module.
	 */
	public function __construct()
	{
		Loader::requireModule('sale');
		Loader::requireModule('catalog');

		$this->pricesConverter = new PricesConverter;
	}

	public function setBasketItem(Basket $basket): void
	{
		$this->basket = $basket;
	}

	/**
	 * @inheritDoc
	 */
	public function convertToSaleBasketFormat(array $product): array
	{
		$prices = $this->pricesConverter->convertToSaleBasketPrices(
			(float)($product['PRICE'] ?? 0),
			(float)($product['PRICE_EXCLUSIVE'] ?? 0),
			(float)($product['PRICE_NETTO'] ?? 0),
			(float)($product['PRICE_BRUTTO'] ?? 0),
			isset($product['TAX_INCLUDED']) && $product['TAX_INCLUDED'] === 'Y'
		);

		$vatRate = null;
		if (isset($product['TAX_RATE']) && is_numeric($product['TAX_RATE']))
		{
			$vatRate = (float)$product['TAX_RATE'] * 0.01;
		}

		$xmlId = null;
		if (isset($product['ID']) && is_numeric($product['ID']))
		{
			$xmlId = BasketXmlId::getXmlIdFromRowId((int)$product['ID']);
		}

		$productType = null;
		$productId = (int)($product['PRODUCT_ID'] ?? 0);
		if ($productId)
		{
			$productType = $this->resolveProductType($productId);
		}
		if ($productType === null)
		{
			$productId = 0;
		}

		return [
			'NAME' => $product['PRODUCT_NAME'],
			'MODULE' => $productId ? 'catalog' : '',
			'PRODUCT_ID' => $productId,
			'OFFER_ID' => $productId, // used in basket builders
			'QUANTITY' => $product['QUANTITY'],
			'DISCOUNT_PRICE' => $prices['DISCOUNT_PRICE'],
			'BASE_PRICE' => $prices['BASE_PRICE'],
			'PRICE' => $prices['PRICE'],
			'CUSTOM_PRICE' => 'Y',
			'MEASURE_CODE' => $product['MEASURE_CODE'] ?? null,
			'MEASURE_NAME' => $product['MEASURE_NAME'] ?? '',
			'VAT_RATE' => $vatRate,
			'VAT_INCLUDED' => $product['TAX_INCLUDED'] ?? 'N',
			'XML_ID' => $xmlId,
			'TYPE' => ProductTypeMapper::getType((int)$productType),
			// not `sale` basket item, but used.
			'DISCOUNT_SUM' => $prices['DISCOUNT_PRICE'],
			'DISCOUNT_RATE' => $product['DISCOUNT_RATE'] ?? null,
			'DISCOUNT_TYPE_ID' => $product['DISCOUNT_TYPE_ID'] ?? null,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function convertToCrmProductRowFormat(array $basketItem): array
	{
		$taxRate = null;
		if (array_key_exists('VAT_RATE', $basketItem))
		{
			if ($basketItem['VAT_RATE'] === null)
			{
				$taxRate = false;
			}
			elseif (is_numeric($basketItem['VAT_RATE']))
			{
				$taxRate = (float)$basketItem['VAT_RATE'] * 100;
			}
		}

		$xmlId = null;
		if (isset($basketItem['ID']) && is_numeric($basketItem['ID']))
		{
			$xmlId = ProductRowXmlId::getXmlIdFromBasketId((int)$basketItem['ID']);
		}

		$productId = (int)($basketItem['PRODUCT_ID'] ?? 0);

		$defaultProductType = \Bitrix\Catalog\ProductTable::TYPE_PRODUCT;

		$result = [
			'XML_ID' => $xmlId,
			'PRODUCT_NAME' => $basketItem['NAME'],
			'PRODUCT_ID' => $productId,
			'QUANTITY' => $basketItem['QUANTITY'],
			//'PRICE_ACCOUNT' => 'Calculated when saving',
			'MEASURE_CODE' => $basketItem['MEASURE_CODE'],
			'MEASURE_NAME' => $basketItem['MEASURE_NAME'],
			'TAX_RATE' => $taxRate,
			'TAX_INCLUDED' => $basketItem['VAT_INCLUDED'],
			'DISCOUNT_TYPE_ID' => Discount::MONETARY,
			'TYPE' => $this->resolveProductType($productId) ?? $defaultProductType,
		];

		$prices = $this->pricesConverter->convertToProductRowPrices(
			(float)($basketItem['PRICE'] ?? 0),
			(float)($basketItem['BASE_PRICE'] ?? 0),
			(float)($basketItem['VAT_RATE'] ?? 0),
			($basketItem['VAT_INCLUDED'] ?? 'N') === 'Y'
		);

		return array_merge($result, $prices);
	}

	private function resolveProductType(int $productId): ?int
	{
		if ($productId <= 0)
		{
			return null;
		}

		if ($this->basket !== null && empty($this->catalogTypesCache))
		{
			$this->loadCatalogTypesForBasket();
		}

		if (array_key_exists($productId, $this->catalogTypesCache))
		{
			return $this->catalogTypesCache[$productId];
		}

		$this->catalogTypesCache[$productId] = $this->fetchTypeFromCatalog($productId);

		return $this->catalogTypesCache[$productId] ?? null;
	}

	private function fetchTypeFromCatalog(int $productId): ?int
	{
		$row = Catalog\ProductTable::getRow([
			'select' => ['TYPE'],
			'filter' => ['=ID' => $productId],
		]);

		if (!$row)
		{
			return null;
		}

		return (int)$row['TYPE'];
	}

	private function loadCatalogTypesForBasket(): void
	{
		$productIds = [];
		foreach ($this->basket as $item)
		{
			$productIds[] = (int)$item->getProductId();
		}
		$productIds = array_filter($productIds);

		if (empty($productIds))
		{
			return;
		}

		$res = Catalog\ProductTable::getList([
			'select' => ['ID', 'TYPE'],
			'filter' => ['@ID' => $productIds],
		]);
		while ($row = $res->fetch())
		{
			$this->catalogTypesCache[(int)$row['ID']] = (int)$row['TYPE'];
		}
	}
}