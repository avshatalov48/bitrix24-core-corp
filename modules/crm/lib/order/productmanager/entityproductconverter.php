<?php

namespace Bitrix\Crm\Order\ProductManager;

use Bitrix\Catalog;
use Bitrix\Crm\Discount;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\BasketXmlId;
use Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowXmlId;
use Bitrix\Crm\Order\ProductManager\ProductConverter\PricesConverter;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
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
	 * @throws Exception if not installed 'sale' module.
	 */
	public function __construct()
	{
		Loader::requireModule('sale');

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

		return [
			'NAME' => $product['PRODUCT_NAME'],
			'MODULE' => $product['PRODUCT_ID'] ? 'catalog' : '',
			'PRODUCT_ID' => $product['PRODUCT_ID'],
			'OFFER_ID' => $product['PRODUCT_ID'], // used in basket builders
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
			'TYPE' => ProductTypeMapper::getType((int)($product['TYPE'] ?? 0)),
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

		$result = [
			'XML_ID' => $xmlId,
			'PRODUCT_NAME' => $basketItem['NAME'],
			'PRODUCT_ID' => $basketItem['PRODUCT_ID'],
			'QUANTITY' => $basketItem['QUANTITY'],
			//'PRICE_ACCOUNT' => 'Calculated when saving',
			'MEASURE_CODE' => $basketItem['MEASURE_CODE'],
			'MEASURE_NAME' => $basketItem['MEASURE_NAME'],
			'TAX_RATE' => $taxRate,
			'TAX_INCLUDED' => $basketItem['VAT_INCLUDED'],
		];

		// prices
		$vatRate = (float)$basketItem['VAT_RATE'];
		$vatIncluded = $basketItem['VAT_INCLUDED'] === 'Y';
		$price = (float)$basketItem['PRICE'];
		$basePrice = (float)$basketItem['BASE_PRICE'];

		$result += $this->pricesConverter->convertToProductRowPrices($price, $basePrice, $vatRate, $vatIncluded);
		$result['DISCOUNT_TYPE_ID'] = Discount::MONETARY;

		// type
		$result['TYPE'] = $this->getTypeByProductId((int)$basketItem['PRODUCT_ID']);

		return $result;
	}

	private function getTypeByProductId(int $productId): ?int
	{
		$catalogProductTypes = $this->getCatalogProductTypes();
		return $catalogProductTypes[$productId] ?? null;
	}

	private function getCatalogProductTypes(): array
	{
		static $result = [];

		if (!$this->basket)
		{
			return $result;
		}

		if (!Loader::includeModule('catalog'))
		{
			return $result;
		}

		// local cache
		$basketUniqHash = $this->getBasketUniqHash();
		if (!empty($result[$basketUniqHash]))
		{
			return $result[$basketUniqHash];
		}

		$productIds = [];

		/** @var BasketItem $basketItem */
		foreach ($this->basket as $basketItem)
		{
			$productIds[] = $basketItem->getProductId();
		}

		if ($productIds)
		{
			$productIterator = Catalog\ProductTable::getList([
				'select' => ['ID', 'TYPE'],
				'filter' => [
					'@ID' => $productIds,
				],
			]);

			$rows = [];
			while ($product = $productIterator->fetch())
			{
				$rows[$product['ID']] = (int)$product['TYPE'];
			}

			$result[$basketUniqHash] = $rows;
		}

		return $result[$basketUniqHash];
	}

	private function getBasketUniqHash(): ?string
	{
		if ($this->basket)
		{
			return md5(serialize($this->basket->toArray()));
		}

		return null;
	}
}
