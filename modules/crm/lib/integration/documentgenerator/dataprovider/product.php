<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Catalog\MeasureTable;
use Bitrix\Crm\Discount;
use Bitrix\Crm\Integration\DocumentGenerator\ProductLoader;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGenerator\Value\TaxRate;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\DocumentGenerator\DataProvider\HashDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PriceMaths;

class Product extends HashDataProvider
{
	protected $id;
	protected $properties;
	protected $propertyValues = [];

	protected static $measureInfo = [];

	public function __construct($data, array $options = [])
	{
		if(is_array($data) && !empty($data))
		{
			$this->id = (int)$data['ID'];
			$data['PRICE_RAW'] = $data['PRICE'];
			$taxRate = isset($data['TAX_RATE']) ? (double)$data['TAX_RATE'] : 0.0;
			if($data['TAX_INCLUDED'] === 'Y')
			{
				$data['PRICE_EXCLUSIVE'] = \CCrmProductRow::CalculateExclusivePrice($data['PRICE'], $taxRate);
			}
			else
			{
				$data['PRICE_EXCLUSIVE'] = $data['PRICE'];
				$data['PRICE'] = \CCrmProductRow::CalculateInclusivePrice($data['PRICE_EXCLUSIVE'], $taxRate);
			}

			if(!isset($data['DISCOUNT_RATE']) || empty($data['DISCOUNT_RATE']))
			{
				$data['DISCOUNT_RATE'] = Discount::calculateDiscountRate(($data['PRICE_EXCLUSIVE'] + $data['DISCOUNT_SUM']), $data['PRICE_EXCLUSIVE']);
			}
			$data['PRICE_NETTO'] = $data['PRICE_EXCLUSIVE'] + $data['DISCOUNT_SUM'];

			if(!isset($data['PRICE_BRUTTO']))
			{
				if($data['DISCOUNT_SUM'] <= 0)
				{
					$data['PRICE_BRUTTO'] = $data['PRICE'];
				}
				else
				{
					$data['PRICE_BRUTTO'] = \CCrmProductRow::CalculateInclusivePrice($data['PRICE_NETTO'], $taxRate);
				}
			}

			if($data['TAX_INCLUDED'] === 'Y')
			{
				$data['PRICE_RAW_NETTO'] = $data['PRICE_BRUTTO'];
			}
			else
			{
				$data['PRICE_RAW_NETTO'] = $data['PRICE_NETTO'];
			}
		}

		parent::__construct($data, $options);
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			$currencyId = $this->source['CURRENCY_ID'] ?? null;
			$this->fields = [
				'NAME' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_NAME_TITLE'),
				],
				'DESCRIPTION' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DESCRIPTION_TITLE'),
					'VALUE' => [$this, 'getIblockValue'],
				],
				'SECTION' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_SECTION_TITLE'),
					'VALUE' => [$this, 'getIblockValue'],
				],
				'PREVIEW_PICTURE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PREVIEW_PICTURE_TITLE'),
					'TYPE' => static::FIELD_TYPE_IMAGE,
					'VALUE' => [$this, 'getIblockValue'],
				],
				'DETAIL_PICTURE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DETAIL_PICTURE_TITLE'),
					'TYPE' => static::FIELD_TYPE_IMAGE,
					'VALUE' => [$this, 'getIblockValue'],
				],
				'PRODUCT_ID' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_ID_TITLE'),],
				'TITLE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_TITLE_TITLE'),
					'VALUE' => [$this, 'getIblockValue'],
				],
				'TITLE_FULL' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_TITLE_FULL_TITLE'),
					'VALUE' => [$this, 'getTitleFull'],
				],
				'SORT' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_SORT_TITLE'),],
				'PRICE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'QUANTITY' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_QUANTITY_TITLE'),],
				'QUANTITY_WORDS' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_QUANTITY_WORDS_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => [
						'WORDS' => true,
						'NO_SIGN' => true,
					],
					'VALUE' => 'QUANTITY',
				],
				'PRICE_EXCLUSIVE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_EXCLUSIVE_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_EXCLUSIVE_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_EXCLUSIVE_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_NETTO' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_NETTO_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_NETTO_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_NETTO_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_BRUTTO' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_BRUTTO_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_BRUTTO_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_BRUTTO_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'DISCOUNT_RATE' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_RATE_TITLE'),],
				'DISCOUNT_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_SUM_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'DISCOUNT_TOTAL' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_TOTAL_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'TAX_RATE' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_RATE_TITLE'),],
				'TAX_RATE_NAME' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_RATE_NAME_TITLE_MSGVER_1'),
					'VALUE' => [$this, 'getTaxRate'],
					'TYPE' => TaxRate::class,
				],
				'TAX_INCLUDED' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_INCLUDED_TITLE'),],
				'MEASURE_CODE' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_CODE_TITLE'),],
				'MEASURE_NAME' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_NAME_TITLE'),],
				'MEASURE_TITLE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_TITLE_TITLE'),
					'VALUE' => [$this, 'getMeasureTitle'],
				],
				'MEASURE_SYMBOL' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_SYMBOL_TITLE'),
					'VALUE' => [$this, 'getMeasureSymbol'],
				],
				'MEASURE_SYMBOL_INTL' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_SYMBOL_INTL_TITLE'),
					'VALUE' => [$this, 'getMeasureSymbolIntl'],
				],
				'PRICE_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'TAX_VALUE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_VALUE_TITLE'),
					'VALUE' => [$this, 'getTaxValue'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'TAX_VALUE_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_VALUE_SUM_TITLE'),
					'VALUE' => [$this, 'getTaxValueSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW_NETTO' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_NETTO_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW_NETTO_SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_NETTO_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'CUSTOMIZED' => [],
				'DISCOUNT_TYPE_ID' => [],
				'CURRENCY_ID' => [],
				'DISCOUNT_TYPE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_TYPE_TITLE'),
					'VALUE' => [$this, 'getDiscountType'],
				],
			];

			$this->fields = array_merge(
				$this->fields,
				DocumentGeneratorManager::getInstance()->getProductLoader()->getFields($this)
			);
		}

		return $this->fields;
	}

	/**
	 * @param string $placeholder
	 * @return float
	 */
	public function getSum($placeholder)
	{
		if($placeholder === 'DISCOUNT_TOTAL')
		{
			$placeholder = 'DISCOUNT_SUM';
		}
		else
		{
			$placeholder = str_replace('_SUM', '', $placeholder);
		}
		$value = $this->data[$placeholder];
		if($value instanceof Value)
		{
			$value = $value->getValue();
		}

		return $value * $this->data['QUANTITY'];
	}

	/**
	 * @param string $placeholder
	 * @return float
	 */
	public function getTaxRate()
	{
		if ($this->data['TAX_RATE'] === false)
		{
			return TaxRate::TAX_FREE;
		}

		return $this->data['TAX_RATE'];
	}

	/**
	 * @return float
	 */
	public function getTaxValue()
	{
		$value = 0;
		if($this->data['TAX_RATE'] > 0)
		{
			$value = $this->data['PRICE'] - $this->getVatlessPrice();
		}

		return $value;
	}

	/**
	 * @return float
	 */
	public function getTaxValueSum()
	{
		$value = 0;

		if($this->data['TAX_RATE'] > 0)
		{
			if($this->data['TAX_INCLUDED'] === 'Y')
			{
				$value = $this->round($this->getRawValue('PRICE_RAW_SUM') - $this->getRawValue('PRICE_RAW_SUM') / (1 + $this->data['TAX_RATE']/100));
			}
			else
			{
				$value = $this->round($this->getRawValue('PRICE_SUM') - $this->getRawValue('PRICE_EXCLUSIVE_SUM'));
			}
		}

		return $value;
	}

	/**
	 * @return string
	 */
	public function getDiscountType()
	{
		if($this->data['DISCOUNT_TYPE_ID'] === Discount::PERCENTAGE)
		{
			return '%';
		}

		return Money::getCurrencySymbol($this->data['CURRENCY_ID'], DataProviderManager::getInstance()->getRegionLanguageId());
	}

	/**
	 * @deprecated
	 * @see ProductLoader::getFields()
	 * @return array
	 */
	protected function getProperties()
	{
		return DocumentGeneratorManager::getInstance()->getProductLoader()->getFields();
	}

	/**
	 * @deprecated
	 * @see ProductLoader::loadProductProperties()
	 * @return array
	 */
	protected function loadProperties()
	{
		return DocumentGeneratorManager::getInstance()->getProductLoader()->loadProductProperties();
	}

	/**
	 * @deprecated
	 * @see ProductLoader::loadPropertyValues()
	 * Fills data with property values.
	 */
	protected function loadPropertyValues()
	{
	}

	/**
	 * @deprecated
	 * @see ProductLoader::getPropertyEnumValue()
	 * @param int $propertyId
	 * @param int $valueId
	 * @return mixed
	 */
	protected function getPropertyEnumValue($propertyId, $valueId)
	{
		return null;
	}

	/**
	 * @param string $code
	 * @return mixed
	 */
	public function getPropertyValue($code)
	{
		return DocumentGeneratorManager::getInstance()->getProductLoader()->getPropertyValue((string)$code, $this->id);
	}

	/**
	 * @deprecated
	 * @see ProductLoader::isPropertyPrintable()
	 * @param array $property
	 * @return bool
	 */
	protected function isPropertyPrintable(array $property)
	{
		return DocumentGeneratorManager::getInstance()->getProductLoader()->isPropertyPrintable($property);
	}

	/**
	 * @return float|int
	 */
	public function getVatlessPrice()
	{
		if($this->data['TAX_INCLUDED'] === 'Y')
		{
			return $this->round($this->data['PRICE_RAW'] / (1 + $this->data['TAX_RATE']/100));
		}

		return $this->data['PRICE_EXCLUSIVE'];
	}

	/**
	 * @todo move to ProductLoader
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function loadMeasureInfo()
	{
		$languageId = DataProviderManager::getInstance()->getContext()->getRegionLanguageId();
		if(!$languageId)
		{
			return false;
		}
		if(!isset(static::$measureInfo[$languageId]))
		{
			static::$measureInfo[$languageId] = [];
		}
		$code = $this->data['MEASURE_CODE'];
		if(!$code)
		{
			return false;
		}
		if(!isset(static::$measureInfo[$languageId][$code]))
		{
			if(Loader::includeModule('catalog'))
			{
				$originalLanguageId = Loc::getCurrentLang();
				Loc::setCurrentLang($languageId);
				static::$measureInfo[$languageId][$code] = \CCatalogMeasureClassifier::getMeasureInfoByCode($code);
				Loc::setCurrentLang($originalLanguageId);

				$tableData = MeasureTable::getList([
					'select' => [
						'SYMBOL_INTL',
						'SYMBOL_LETTER_INTL',
						'MEASURE_TITLE',
						'SYMBOL',
					],
					'filter' => [
						'=CODE' => $code,
					],
				])->fetch();
				if($tableData)
				{
					if(!empty($tableData['SYMBOL_INTL']))
					{
						static::$measureInfo[$languageId][$code]['SYMBOL_INTL'] = $tableData['SYMBOL_INTL'];
					}
					if(!empty($tableData['SYMBOL_LETTER_INTL']))
					{
						static::$measureInfo[$languageId][$code]['SYMBOL_LETTER_INTL'] = $tableData['SYMBOL_LETTER_INTL'];
					}
					if(!empty($tableData['MEASURE_TITLE']))
					{
						static::$measureInfo[$languageId][$code]['MEASURE_TITLE'] = $tableData['MEASURE_TITLE'];
					}
					if(!empty($tableData['SYMBOL']))
					{
						static::$measureInfo[$languageId][$code]['SYMBOL'] = $tableData['SYMBOL'];
					}
				}
			}
		}

		return static::$measureInfo[$languageId][$code];
	}

	/**
	 * @return null
	 */
	public function getMeasureTitle()
	{
		$info = $this->loadMeasureInfo();
		if($info)
		{
			return $info['MEASURE_TITLE'];
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getMeasureSymbol()
	{
		$info = $this->loadMeasureInfo();
		if($info)
		{
			return $info['SYMBOL_RUS'] ?? $info['SYMBOL'];
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getMeasureSymbolIntl()
	{
		$info = $this->loadMeasureInfo();
		if($info)
		{
			return $info['SYMBOL_INTL'];
		}

		return null;
	}

	protected function round(float $value): float
	{
		if(Loader::includeModule('sale'))
		{
			return PriceMaths::roundPrecision($value);
		}

		return round($value, 2);
	}

	/**
	 * Return value for common iblock fields.
	 *
	 * @param $code
	 * @return mixed|null
	 */
	public function getIblockValue(string $code)
	{
		$value = DocumentGeneratorManager::getInstance()->getProductLoader()->getIblockValue($code, $this->id);
		if ($code === 'TITLE' && empty($value))
		{
			return $this->data['NAME'];
		}

		return $value;
	}

	/**
	 * Return title with all offer-related property values.
	 *
	 * @return string
	 */
	public function getTitleFull()
	{
		return DocumentGeneratorManager::getInstance()->getProductLoader()->getTitleFull($this->id) ?? $this->data['NAME'];
	}
}
