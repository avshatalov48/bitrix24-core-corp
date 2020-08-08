<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

\Bitrix\Main\Loader::includeModule('documentgenerator');

use Bitrix\Crm\Discount;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\DocumentGenerator\DataProvider\HashDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\PriceMaths;

class Product extends HashDataProvider
{
	protected $properties;
	protected $propertyIDs;
	protected $propertiesLoaded = false;
	protected $propertyValues = [];

	protected static $measureInfo = [];

	public function __construct($data, array $options = [])
	{
		if(is_array($data) && !empty($data))
		{
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
			$currencyId = null;
			if(isset($this->source['CURRENCY_ID']))
			{
				$currencyId = $this->source['CURRENCY_ID'];
			}
			$this->fields = [
				'NAME' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_NAME_TITLE'),],
				'DESCRIPTION' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DESCRIPTION_TITLE'),],
				'SECTION' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_SECTION_TITLE'),],
				'PREVIEW_PICTURE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PREVIEW_PICTURE_TITLE'),
					'TYPE' => static::FIELD_TYPE_IMAGE,
				],
				'DETAIL_PICTURE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DETAIL_PICTURE_TITLE'),
					'TYPE' => static::FIELD_TYPE_IMAGE,
				],
				'PRODUCT_ID' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_ID_TITLE'),],
				'SORT' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_SORT_TITLE'),],
				'PRICE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'QUANTITY' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_QUANTITY_TITLE'),],
				'PRICE_EXCLUSIVE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_EXCLUSIVE_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_EXCLUSIVE_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_EXCLUSIVE_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_NETTO' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_NETTO_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_NETTO_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_NETTO_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_BRUTTO' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_BRUTTO_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_BRUTTO_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_BRUTTO_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'DISCOUNT_RATE' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_RATE_TITLE'),],
				'DISCOUNT_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_SUM_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'DISCOUNT_TOTAL' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_TOTAL_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'TAX_RATE' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_RATE_TITLE'),],
				'TAX_INCLUDED' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_INCLUDED_TITLE'),],
				'MEASURE_CODE' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_CODE_TITLE'),],
				'MEASURE_NAME' => ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_NAME_TITLE'),],
				'MEASURE_TITLE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_TITLE_TITLE'),
					'VALUE' => [$this, 'getMeasureTitle'],
				],
				'MEASURE_SYMBOL' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_SYMBOL_TITLE'),
					'VALUE' => [$this, 'getMeasureSymbol'],
				],
				'MEASURE_SYMBOL_INTL' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_MEASURE_SYMBOL_INTL_TITLE'),
					'VALUE' => [$this, 'getMeasureSymbolIntl'],
				],
				'PRICE_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'TAX_VALUE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_VALUE_TITLE'),
					'VALUE' => [$this, 'getTaxValue'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'TAX_VALUE_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_TAX_VALUE_SUM_TITLE'),
					'VALUE' => [$this, 'getTaxValueSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW_NETTO' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_NETTO_TITLE'),
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'PRICE_RAW_NETTO_SUM' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_PRICE_RAW_NETTO_SUM_TITLE'),
					'VALUE' => [$this, 'getSum'],
					'TYPE' => Money::class,
					'FORMAT' => ['CURRENCY_ID' => $currencyId, 'NO_SIGN' => true, 'WITH_ZEROS' => false],
				],
				'CUSTOMIZED' => [],
				'DISCOUNT_TYPE_ID' => [],
				'CURRENCY_ID' => [],
				'DISCOUNT_TYPE' => [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PRODUCT_DISCOUNT_TYPE_TITLE'),
					'VALUE' => [$this, 'getDiscountType'],
				],
			];

			$this->fields = array_merge($this->fields, $this->getProperties());
		}

		return $this->fields;
	}

	/**
	 * @param string $placeholder
	 * @return float
	 */
	public function getSum($placeholder)
	{
		if($placeholder == 'DISCOUNT_TOTAL')
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
	 * @return Money
	 */
	public function getTaxValueSum()
	{
		$value = 0;

		if($this->data['TAX_RATE'] > 0)
		{
			if($this->data['TAX_INCLUDED'] == 'Y')
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
		if($this->data['DISCOUNT_TYPE_ID'] == Discount::PERCENTAGE)
		{
			return '%';
		}
		else
		{
			return Money::getCurrencySymbol($this->data['CURRENCY_ID'], DataProviderManager::getInstance()->getRegionLanguageId());
		}
	}

	/**
	 * @return array
	 */
	protected function getProperties()
	{
		if($this->properties === null)
		{
			$this->properties = [];
			$crmOwnerTypeProvidersMap = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap();

			$catalogId = \CCrmCatalog::GetDefaultID();
			if(!$catalogId)
			{
				return $this->properties;
			}

			foreach($this->loadProperties() as $property)
			{
				if(!$this->isPropertyPrintable($property))
				{
					continue;
				}
				$field = [
					'TITLE' => $property['NAME'],
					'VALUE' => [$this, 'getPropertyValue'],
				];
				if($property['PROPERTY_TYPE'] == 'F')
				{
					$field['TYPE'] = static::FIELD_TYPE_IMAGE;
				}
				elseif($property['PROPERTY_TYPE'] == 'S')
				{
					if($property['USER_TYPE'] == 'HTML')
					{
						$field['TYPE'] = static::FIELD_TYPE_TEXT;
					}
					elseif($property['USER_TYPE'] == 'Date' || $property['USER_TYPE'] == 'DateTime')
					{
						$field['TYPE'] = static::FIELD_TYPE_DATE;
					}
					elseif($property['USER_TYPE'] == 'Money')
					{
						$field['TYPE'] = Money::class;
					}
					elseif($property['USER_TYPE'] == 'employee')
					{
						$field['PROVIDER'] = \Bitrix\DocumentGenerator\DataProvider\User::class;
						$field['OPTIONS'] = [
							'FORMATTED_NAME_FORMAT' => [
								'format' => CrmEntityDataProvider::getNameFormat(),
							]
						];
					}
					elseif($property['USER_TYPE'] == 'ECrm')
					{
						$provider = null;
						$entityTypes = [];
						if($property['USER_TYPE_SETTINGS']['LEAD'] == 'Y')
						{
							$entityTypes[] = \CCrmOwnerType::Lead;
						}
						if($property['USER_TYPE_SETTINGS']['CONTACT'] == 'Y')
						{
							$entityTypes[] = \CCrmOwnerType::Contact;
						}
						if($property['USER_TYPE_SETTINGS']['COMPANY'] == 'Y')
						{
							$entityTypes[] = \CCrmOwnerType::Company;
						}
						if($property['USER_TYPE_SETTINGS']['DEAL'] == 'Y')
						{
							$entityTypes[] = \CCrmOwnerType::Deal;
						}
						if(count($entityTypes) > 1)
						{
							continue;
						}
						$ownerTypeId = $entityTypes[0];
						if(isset($crmOwnerTypeProvidersMap[$ownerTypeId]))
						{
							$provider = $crmOwnerTypeProvidersMap[$ownerTypeId];
						}
						if($provider)
						{
							$field['PROVIDER'] = $provider;
							$field['OPTIONS']['isLightMode'] = true;
						}
					}
				}
				$this->propertyIDs[] = $property['ID'];
				$code = $property['ID'];
				$this->properties['PROPERTY_'.$code] = $field;
				if($property['CODE'])
				{
					$code = $property['CODE'];
					$this->properties['PROPERTY_'.$code] = $field;
				}
			}
		}

		return $this->properties;
	}

	/**
	 * @return array
	 */
	protected function loadProperties()
	{
		static $properties = null;
		if($properties === null)
		{
			$properties = [];

			$catalogId = \CCrmCatalog::GetDefaultID();
			if(!$catalogId)
			{
				return $properties;
			}

			$query = \CIBlock::GetProperties($catalogId, ['SORT' => 'ASC'], ['ACTIVE' => 'Y']);
			while($property = $query->Fetch())
			{
				if(!$this->isPropertyPrintable($property))
				{
					continue;
				}
				$properties[] = $property;
			}
		}

		return $properties;
	}

	/**
	 * Fills data with property values.
	 */
	protected function loadPropertyValues()
	{
		if($this->propertiesLoaded === false)
		{
			$this->propertiesLoaded = true;
			$linkedProducts = [];
			if(!$this->data['PRODUCT_ID'])
			{
				return;
			}
			$catalogId = \CCrmCatalog::GetDefaultID();
			if(!$catalogId)
			{
				return;
			}
			$propertyResult = \CIBlockElement::GetProperty(
				$catalogId,
				$this->data['PRODUCT_ID'],
				array(
					'sort' => 'asc',
					'id' => 'asc',
					'enum_sort' => 'asc',
					'value_id' => 'asc',
				),
				array(
					'ACTIVE' => 'Y',
					'EMPTY' => 'N',
					'CHECK_PERMISSIONS' => 'N',
					'ID' => $this->propertyIDs,
				)
			);
			while($property = $propertyResult->Fetch())
			{
				$codes = [$property['ID']];
				if($property['CODE'])
				{
					$codes[] = $property['CODE'];
				}
				$value = $property['VALUE'];
				if($property['PROPERTY_TYPE'] === 'F')
				{
					if($property['MULTIPLE'] == 'Y' && isset($this->propertyValues['PROPERTY_'.$codes[0]]) && !empty($this->propertyValues['PROPERTY_'.$codes[0]]))
					{
						// use the first value if there are more than one
						continue;
					}
					$value = \CFile::GetPath($value);
				}
				elseif($property['PROPERTY_TYPE'] === 'S')
				{
					if($property['USER_TYPE'] == 'HTML')
					{
						$value = $value['TEXT'];
					}
					elseif($property['USER_TYPE'] == 'Date')
					{
						$value = new Value\DateTime($value);
					}
					elseif($property['USER_TYPE'] == 'DateTime')
					{
						$value = new Value\DateTime($value, ['format' => DateTime::getFormat(DataProviderManager::getInstance()->getCulture())]);
					}
					elseif($property['USER_TYPE'] == 'Money')
					{
						list($value, $currency) = explode('|', $value);
						$value = new Money($value, ['CURRENCY_ID' => $currency]);
					}
					elseif($property['USER_TYPE'] == 'employee' || $property['USER_TYPE'] == 'ECrm')
					{
						if($property['MULTIPLE'] == 'Y' && isset($this->propertyValues['PROPERTY_'.$codes[0]]) && !empty($this->propertyValues['PROPERTY_'.$codes[0]]))
						{
							// use the first value if there are more than one
							continue;
						}
					}
				}
				elseif($property['PROPERTY_TYPE'] == 'L')
				{
					$value = $this->getPropertyEnumValue($property['ID'], $value);
				}
				elseif($property['PROPERTY_TYPE'] == 'E')
				{
					$linkedProducts['ids'][] = $value;
					foreach($codes as $code)
					{
						$linkedProducts['codes'][$value][] = $code;
					}
					continue;
				}
				foreach($codes as $code)
				{
					if(isset($this->propertyValues['PROPERTY_'.$code]) && !empty($this->propertyValues['PROPERTY_'.$code]))
					{
						if(!is_array($this->propertyValues['PROPERTY_'.$code]))
						{
							$this->propertyValues['PROPERTY_'.$code] = [$this->propertyValues['PROPERTY_'.$code]];
						}
						$this->propertyValues['PROPERTY_'.$code][] = $value;
					}
					else
					{
						$this->propertyValues['PROPERTY_'.$code] = $value;
					}
				}
			}
			if(!empty($linkedProducts))
			{
				$items = ElementTable::getList(['filter' => [
					'@ID' => array_values($linkedProducts['ids']),
					'ACTIVE' => 'Y',
				], 'select' => [
					'NAME', 'ID'
				],
				]);
				while($item = $items->fetch())
				{
					foreach($linkedProducts['codes'][$item['ID']] as $code)
					{
						if(isset($this->propertyValues['PROPERTY_'.$code]) && !empty($this->propertyValues['PROPERTY_'.$code]))
						{
							if(!is_array($this->propertyValues['PROPERTY_'.$code]))
							{
								$this->propertyValues['PROPERTY_'.$code] = [$this->propertyValues['PROPERTY_'.$code]];
							}
							$this->propertyValues['PROPERTY_'.$code][] = $item['NAME'];
						}
						else
						{
							$this->propertyValues['PROPERTY_'.$code] = $item['NAME'];
						}
					}
				}
			}
		}
	}

	/**
	 * @param int $propertyId
	 * @param int $valueId
	 * @return mixed
	 */
	protected function getPropertyEnumValue($propertyId, $valueId)
	{
		static $propertyEnums = [];
		if(!isset($propertyEnums[$propertyId]))
		{
			$enums = \CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => \CCrmCatalog::GetDefaultID(), 'PROPERTY_ID' => $propertyId]);
			while($enum = $enums->Fetch())
			{
				$propertyEnums[$propertyId][$enum['ID']] = $enum['VALUE'];
			}
		}

		return $propertyEnums[$propertyId][$valueId];
	}

	/**
	 * @param string $code
	 * @return mixed
	 */
	public function getPropertyValue($code)
	{
		$this->loadPropertyValues();
		return $this->propertyValues[$code];
	}

	protected function isPropertyPrintable(array $property)
	{
		if($property['PROPERTY_TYPE'] == 'S' && isset($property['USER_TYPE']) && !empty($property['USER_TYPE']))
		{
			return in_array($property['USER_TYPE'], [
				'HTML', 'Date', 'DateTime', 'Money', 'employee', 'ECrm', 'Sequence',
			]);
		}

		return in_array($property['PROPERTY_TYPE'], [
			'S', 'N', 'F', 'L', 'E',
		]);
	}

	/**
	 * @return float|int
	 */
	public function getVatlessPrice()
	{
		if($this->data['TAX_INCLUDED'] == 'Y')
		{
			return $this->round($this->data['PRICE_RAW'] / (1 + $this->data['TAX_RATE']/100));
		}
		else
		{
			return $this->data['PRICE_EXCLUSIVE'];
		}
	}

	/**
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
			return $info['SYMBOL_RUS'];
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
}