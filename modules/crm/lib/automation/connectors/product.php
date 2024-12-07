<?php

namespace Bitrix\Crm\Automation\Connectors;

use Bitrix\Bizproc\FieldType;
use Bitrix\Crm\Discount;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\ProductRowTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Crm\ProductType;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\StringHelper;

/**
 * @method int getId()
 * @method int getOwnerId()
 * @method string getOwnerType()
 * @method int getProductId()
 * @method ?string getProductName()
 * @method string | null getCpProductName()
 * @method float getPrice()
 * @method float getPriceAccount()
 * @method float getPriceExclusive()
 * @method float getPriceNetto()
 * @method float getPriceBrutto()
 * @method float getQuantity()
 * @method float getSumAccount()
 * @method int getDiscountTypeId()
 * @method float getDiscountRate()
 * @method float getDiscountSum()
 * @method float | null getTaxRate()
 * @method bool getTaxIncluded()
 * @method bool getCustomized()
 * @method int getMeasureCode()
 * @method string getMeasureName()
 * @method int getSort()
 * @method string getXmlId()
 * @method int | null getType()
 */
class Product
{
	private ProductRow $product;

	public function __construct(ProductRow $product)
	{
		$this->product = $product;
		$this->product->fill(\Bitrix\Main\ORM\Fields\FieldTypeMask::FLAT);
	}

	public function __call($name, $arguments)
	{
		$method = mb_substr($name, 0, 3);
		$fieldName = mb_strtoupper(StringHelper::camel2snake(mb_substr($name, 3)));

		if ($method === 'get')
		{
			return $this->get($fieldName);
		}
	}

	public function get($fieldName)
	{
		$fieldsMap = static::getFieldsMap();
		$customMethod = 'get' . StringHelper::snake2camel($fieldName);

		if (method_exists($this, $customMethod))
		{
			return call_user_func([$this, $customMethod]);
		}
		if (array_key_exists($fieldName, $fieldsMap))
		{
			return $this->product->get($fieldName);
		}

		return null;
	}

	public function getPrintableSumAccount(): string
	{
		$currencyId = \CCrmCurrency::GetAccountCurrencyID();

		return \CCrmCurrency::MoneyToString($this->getSumAccount(), $currencyId);
	}

	public function getProductParentId(): ?int
	{
		if (!Loader::includeModule('catalog'))
		{
			return null;
		}
		if ($this->getType() !== ProductTable::TYPE_OFFER)
		{
			return null;
		}
		$productId = $this->getProductId();
		if ($productId <= 0)
		{
			return null;
		}
		$parentProduct = \CCatalogSku::getProductList($productId);

		return ($parentProduct[$productId]['ID'] ?? null);
	}

	public static function fetchFromTableByFilter(array $filter = []): ?self
	{
		return static::fetchFromTable([
			'select' => ['*'],
			'filter' => $filter,
		]);
	}

	public static function fetchFromTable(array $parameters = []): ?self
	{
		$firstAcceptableProduct = ProductRowTable::getList($parameters)->fetchObject();
		if ($firstAcceptableProduct)
		{
			return new static($firstAcceptableProduct);
		}

		return null;
	}

	public static function getFieldsMap(): array
	{
		static $fieldsMap = null;

		if (is_array($fieldsMap))
		{
			return $fieldsMap;
		}
		if (!Loader::includeModule('catalog'))
		{
			return [];
		}

		$fieldsMap = [
			'ID' => [
				'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_ID'),
				'Type' => FieldType::INT,
			],
			'OWNER_ID' => [
				'Name' => \CCrmProductRow::GetFieldCaption('OWNER_ID'),
				'Type' => FieldType::INT,
				'Required' => true,
			],
			'OWNER_TYPE' => [
				'Name' => \CCrmProductRow::GetFieldCaption('OWNER_TYPE'),
				'Type' => FieldType::STRING,
				'Required' => true,
			],
			'PRODUCT_ID' => [
				'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_PRODUCT_ID'),
				'Type' => FieldType::INT,
				'Default' => 0,
			],
			'PRODUCT_NAME' => [
				'Name' => \CCrmProductRow::GetFieldCaption('PRODUCT_NAME'),
				'Type' => FieldType::STRING,
				'Default' => '',
			],
			'PRICE_ACCOUNT' => [
				'Name' => \CCrmProductRow::GetFieldCaption('PRICE'),
				'Type' => FieldType::DOUBLE,
				'Required' => true,
				'Default' => 0.0,
			],
			'PRICE_EXCLUSIVE' => [
				'Name' => \CCrmProductRow::GetFieldCaption('PRICE_EXCLUSIVE'),
				'Type' => FieldType::DOUBLE,
				'Default' => 0.0,
			],
			'PRICE_NETTO' => [
				'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_PRICE_NETTO'),
				'Type' => FieldType::DOUBLE,
				'Default' => 0.0,
			],
			'PRICE_BRUTTO' => [
				'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_PRICE_BRUTTO'),
				'Type' => FieldType::DOUBLE,
				'Default' => 0.0,
			],
			'QUANTITY' => [
				'Name' => \CCrmProductRow::GetFieldCaption('QUANTITY'),
				'Type' => FieldType::DOUBLE,
				'Required' => true,
				'Default' => 1.0,
			],
			'SUM_ACCOUNT' => [
				'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_SUM_ACCOUNT'),
				'Type' => FieldType::DOUBLE,
			],
			'DISCOUNT_TYPE_ID' => [
				'Name' => \CCrmProductRow::GetFieldCaption('DISCOUNT_TYPE_ID'),
				'Type' => FieldType::SELECT,
				'Options' => [
					Discount::UNDEFINED => '',
					Discount::MONETARY => '',
					Discount::PERCENTAGE => '',
				],
				'Default' => Discount::UNDEFINED,
			],
			'DISCOUNT_RATE' => [
				'Name' => \CCrmProductRow::GetFieldCaption('DISCOUNT_RATE'),
				'Type' => FieldType::DOUBLE,
				'Default' => 0.0,
			],
			'DISCOUNT_SUM' => [
				'Name' => \CCrmProductRow::GetFieldCaption('DISCOUNT_SUM'),
				'Type' => FieldType::DOUBLE,
				'Default' => 0.0,
			],
			'TAX_RATE' => [
				'Name' => \CCrmProductRow::GetFieldCaption('TAX_RATE'),
				'Type' => FieldType::DOUBLE,
			],
			'TAX_INCLUDED' => [
				'Name' => \CCrmProductRow::GetFieldCaption('TAX_INCLUDED'),
				'Type' => FieldType::BOOL,
				'Default' => false,
			],
			'MEASURE_CODE' => [
				'Name' => \CCrmProductRow::GetFieldCaption('MEASURE_CODE'),
				'Type' => FieldType::INT,
				'Default' => 0,
			],
			'MEASURE_NAME' => [
				'Name' => \CCrmProductRow::GetFieldCaption('MEASURE_NAME'),
				'Type' => FieldType::STRING,
				'Default' => '',
			],
			'SORT' => [
				'Name' => \CCrmProductRow::GetFieldCaption('SORT'),
				'Type' => FieldType::INT,
				'Default' => 0,
			],
			'XML_ID' => [
				'Name' => 'XML ID',
				'Type' => FieldType::STRING,
				'Default' => '',
			],
			'PRODUCT_PARENT_ID' => [
				'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_PRODUCT_PARENT_ID'),
				'Type' => FieldType::INT,
			],
 		];

		$productTypeField = static::getProductTypeField();
		if (isset($productTypeField))
		{
			$fieldsMap['TYPE'] = $productTypeField;
		}

		return $fieldsMap;
	}

	private static function getProductTypeField(): ?array
	{
		if (!Loader::includeModule('catalog'))
		{
			return null;
		}

		return [
			'Name' => Loc::getMessage('CRM_AUTOMATION_CONNECTORS_PRODUCT_FIELD_TYPE'),
			'Type' => FieldType::SELECT,
			'Options' => ProductTable::getProductTypes(true),
			'Required' => true,
			'Default' => ProductType::TYPE_PRODUCT,
		];
	}

	private function getProductName(): ?string
	{
		return $this->product->getProductName() ?: $this->product->getCpProductName();
	}
}