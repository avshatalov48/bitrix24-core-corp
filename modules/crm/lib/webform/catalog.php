<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Loader;
use Bitrix\Catalog\v2\Integration\JS\ProductForm\BasketBuilder;
use Bitrix\Catalog\v2\Integration\JS\ProductForm\BasketItem;
use Bitrix\Sale\Helpers\Order\Builder\Converter\CatalogJSProductForm;

/**
 * Class Catalog
 * @package Bitrix\Crm\WebForm
 */
class Catalog
{
	/** @var array  */
	private $items = [];

	public static function create()
	{
		return new self();
	}

	public function setItems(array $items)
	{
		$this->items = [];
		foreach ($items as $item)
		{
			if (!is_array($item))
			{
				continue;
			}

			$this->addItem($item);
		}
		return $this;
	}

	public function addItem(array $item)
	{
		$item = array_change_key_case($item, CASE_UPPER);
		$id = $item['PRODUCT_ID'] ?? $item['ID'] ?? 0;
		$this->items[] = [
			'ID' => ($id && is_numeric($id))
				? (int)$id
				: 0
			,
			'NAME' => trim($item['PRODUCT_NAME'] ?? $item['NAME']  ?? $item['VALUE'] ?? ''),
			'PRICE' => (float)($item['PRICE'] ?? 0),
			'DISCOUNT' => (float)($item['DISCOUNT'] ?? 0),
			'QUANTITY' => (float)($item['QUANTITY'] ?? 0),
		];
		return $this;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	private function make()
	{
		if(!($builder = self::createBuilder()))
		{
			return null;
		}

		foreach ($this->items as $item)
		{
			$basketItem = null;
			if ($item['ID'])
			{
				$basketItem = $builder->loadItemBySkuId($item['ID']);
			}
			if ($basketItem === null)
			{
				$basketItem = $builder->createItem();
			}

			if ($item['NAME'])
			{
				$basketItem->setName($item['NAME']);
			}

			{
				$basketItem->setPrice($item['PRICE']);
				$basketItem->setBasePrice($item['PRICE']);
				$basketItem->setPriceExclusive($item['PRICE']);
			}
			if ($item['DISCOUNT'])
			{
				$basketItem->setDiscountType(1);
				$basketItem->setDiscountValue($item['DISCOUNT']);
			}
			if ($item['QUANTITY'])
			{
				$basketItem->setQuantity($item['QUANTITY']);
			}
			$basketItem->setCustomPriceType('Y');
			$builder->setItem($basketItem);
		}

		return $builder;
	}

	public function getSelectorProducts(): array
	{
		$builder = $this->make();
		return $builder
			? $builder->getFormattedItems()
			: []
		;
	}

	public function getOrderProducts(): array
	{
		$builder = $this->make();
		if (!$builder)
		{
			return [];
		}

		$items = array_map(
			function (BasketItem $item)
			{
				return $item->getFields();
			},
			(array)$builder->getIterator()
		);

		return CatalogJSProductForm::convertToBuilderFormat($items);
	}

	private static function createBuilder()
	{
		return Loader::includeModule('catalog')
			? new BasketBuilder()
			: null
		;
	}

	private function __construct()
	{

	}
}
