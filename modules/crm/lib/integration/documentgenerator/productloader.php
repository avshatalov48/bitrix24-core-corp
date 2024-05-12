<?php

namespace Bitrix\Crm\Integration\DocumentGenerator;

use Bitrix\Crm\Integration\DocumentGenerator\DataProvider\CrmEntityDataProvider;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Product;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Value;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class ProductLoader
{
	protected const FIELD_PROPERTY_PREFIX = 'PROPERTY_';
	/** @var ElementTable */
	protected $elementTableClass = ElementTable::class;
	/** @var \CIBlockElement */
	protected $iblockElementClass = \CIBlockElement::class;

	protected $productIblockId;
	protected $offerIblockId;
	protected $rows = [];
	protected $productIds;
	protected $offerIds;
	protected $offerToProductsMap;
	protected $fields;
	protected $productProperties;
	protected $offerProperties;
	protected $propertyValues;
	protected $preparedPropertyValues = [];
	protected $iblockValues;
	protected $preparedIblockData;
	protected $propertyCodes;
	protected $directoryImagePropertyCodes;
	protected $loadedDirectoryPropertyValues;
	protected $loadedEnumPropertyValues;
	protected $linkedElements;

	public function __construct()
	{
		if (!Loader::includeModule('catalog') || !Loader::includeModule('iblock'))
		{
			return;
		}
		$this->productIblockId = Product\Catalog::getDefaultId();
		$this->offerIblockId = Product\Catalog::getDefaultOfferId();
	}

	/**
	 * Add multiple rows.
	 *
	 * @param array $rows
	 * @return $this
	 */
	public function addRows(array $rows): self
	{
		foreach ($rows as $row)
		{
			$this->addRow($row);
		}

		return $this->clearCache();
	}

	/**
	 * Add single row.
	 *
	 * @param array $rowData
	 * @return $this
	 */
	public function addRow(array $rowData): self
	{
		$this->rows[(int)$rowData['ID']] = $rowData;

		return $this->clearCache();
	}

	/**
	 * Clear inner cache of loaded data.
	 *
	 * @return $this
	 */
	public function clearCache(): self
	{
		$this->propertyValues = null;
		$this->productIds = null;
		$this->offerIds = null;
		$this->offerToProductsMap = null;
		$this->preparedPropertyValues = [];
		$this->iblockValues = null;
		$this->preparedIblockData = null;
		$this->linkedElements = null;

		return $this;
	}

	/**
	 * Return fields for $provider.
	 *
	 * @param DataProvider\Product $provider
	 * @return array
	 */
	public function getFields(DataProvider\Product $provider): array
	{
		$fields = [];

		foreach ($this->getFieldsStub() as $code => $field)
		{
			$field['VALUE'] = [$provider, 'getPropertyValue'];
			$fields[$code] = $field;
		}

		return $fields;
	}

	/**
	 * Return all related element ids of products iblock.
	 *
	 * @return array
	 */
	protected function getProductIds(): array
	{
		$this->loadMap();

		return array_keys($this->productIds);
	}

	/**
	 * Return all element ids of offers iblock.
	 *
	 * @return array
	 */
	protected function getOfferIds(): array
	{
		$this->loadMap();

		return array_keys($this->offerIds);
	}

	/**
	 * Return product id by offer id.
	 *
	 * @param int $offerId
	 * @return int|null
	 */
	protected function getProductIdByOfferId(int $offerId): ?int
	{
		$this->loadMap();

		return $this->offerToProductsMap[$offerId] ?? null;
	}

	/**
	 * Return iblock element id by row id.
	 *
	 * @param int $rowId
	 * @return int|null
	 */
	protected function getElementIdByRowId(int $rowId): ?int
	{
		$data = $this->rows[$rowId] ?? [];

		return $data['PRODUCT_ID'] ?? null;
	}

	/**
	 * Load products to offers map.
	 */
	protected function loadMap(): void
	{
		if ($this->productIds !== null)
		{
			return;
		}
		$this->productIds = [];
		$this->offerIds = [];
		$this->offerToProductsMap = [];

		$allIds = [];

		foreach ($this->rows as $row)
		{
			$itemId = (int)($row['PRODUCT_ID'] ?? 0);
			if ($itemId > 0)
			{
				$allIds[$itemId] = (int)$row['ID'];
			}
		}

		$offersData = \CCatalogSku::getProductList(array_keys($allIds), $this->offerIblockId);
		if (is_array($offersData))
		{
			foreach ($offersData as $offerId => $offerData)
			{
				$this->offerIds[$offerId] = $allIds[$offerId];

				$productId = (int)($offerData['ID'] ?? 0);

				$this->productIds[$productId] = $allIds[$productId] ?? null;
				$this->offerToProductsMap[$offerId] = $productId;
			}
		}

		foreach ($allIds as $productId => $rowId)
		{
			if (!isset($this->offerIds[$productId]) && !isset($this->productIds[$productId]))
			{
				$this->productIds[$productId] = $rowId;
			}
		}
	}

	/**
	 * Return title of an offer with all related property names and values.
	 *
	 * @return string
	 */
	public function getTitleFull(int $rowId): ?string
	{
		$elementId = $this->getElementIdByRowId($rowId);
		if (!$elementId)
		{
			return null;
		}

		$name = $this->getIblockValue('NAME', $rowId);
		$isProduct = isset($this->productIds[$elementId]);
		if ($isProduct)
		{
			return $name;
		}

		if (empty($this->offerProperties))
		{
			return $name;
		}

		$offerPropertyValues = [];
		foreach ($this->offerProperties as $property)
		{
			$value = $this->getPreparedPropertyValue($property, $elementId);
			if (!empty($value))
			{
				$offerPropertyValues[] = Loc::getMessage('CRM_DOCUMENTGENERATOR_PRODUCTLOADER_OFFER_PROPERTY_PAIR', [
					'#NAME#' => $property['NAME'],
					'#VALUE#' => $value,
				]);
			}
		}

		if (!empty($offerPropertyValues))
		{
			$name = Loc::getMessage('CRM_DOCUMENTGENERATOR_PRODUCTLOADER_FULL_TITLE', [
				'#NAME#' => $name,
				'#PROPERTY_VALUES#' => implode(
					Loc::getMessage('CRM_DOCUMENTGENERATOR_PRODUCTLOADER_OFFER_PROPERTY_PAIR_DELIMITER'),
					$offerPropertyValues
				),
			]);
		}

		return $name;
	}

	/**
	 * Return base field value with $code from iblock for $rowId.
	 *
	 * @param string $code - name of the field.
	 * @param int $rowId - id of the row.
	 * @return mixed|null
	 */
	public function getIblockValue(string $code, int $rowId)
	{
		$elementId = $this->getElementIdByRowId($rowId);
		if (!$elementId)
		{
			return null;
		}

		$data = $this->prepareIblockData($elementId);

		if ($code === 'TITLE')
		{
			$code = 'NAME';
		}

		return $data[$code] ?? null;
	}

	protected function loadIblockValues(): void
	{
		if ($this->iblockValues !== null)
		{
			return;
		}
		$this->iblockValues = [];

		$iblockElementIds = array_merge($this->getProductIds(), $this->getOfferIds());
		if (empty($iblockElementIds))
		{
			return;
		}

		$iterator = $this->elementTableClass::getList([
			'select' => ['ID', 'NAME', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'IBLOCK_SECTION.NAME'],
			'filter' => [
				'@ID' => $iblockElementIds,
			],
		]);

		while ($data = $iterator->fetch())
		{
			$this->iblockValues[(int)$data['ID']] = [
				'NAME' => $data['NAME'],
				'DESCRIPTION' => $data['DETAIL_TEXT'],
				'PREVIEW_PICTURE' => (int)$data['PREVIEW_PICTURE'],
				'DETAIL_PICTURE' => (int)$data['DETAIL_PICTURE'],
				'SECTION' => $data['IBLOCK_ELEMENT_IBLOCK_SECTION_NAME'],
			];
		}
	}

	protected function prepareIblockData(int $elementId): array
	{
		$this->loadIblockValues();

		if (isset($this->preparedIblockData[$elementId]))
		{
			return $this->preparedIblockData[$elementId];
		}

		if (!isset($this->iblockValues[$elementId]))
		{
			return [];
		}

		$elementData = $this->iblockValues[$elementId];
		$preparedData = $elementData;
		if ($preparedData['PREVIEW_PICTURE'] > 0)
		{
			$preparedData['PREVIEW_PICTURE'] = \CFile::GetPath($preparedData['PREVIEW_PICTURE']);
		}
		else
		{
			$preparedData['PREVIEW_PICTURE'] = null;
		}
		if ($preparedData['DETAIL_PICTURE'] > 0)
		{
			$preparedData['DETAIL_PICTURE'] = \CFile::GetPath($preparedData['DETAIL_PICTURE']);
		}
		else
		{
			$preparedData['DETAIL_PICTURE'] = null;
		}
		$isProduct = array_key_exists($elementId, $this->productIds);
		if ($isProduct)
		{
			$this->preparedIblockData[$elementId] = $preparedData;
			return $preparedData;
		}

		$productId = $this->getProductIdByOfferId($elementId);
		$productPreparedData = $this->prepareIblockData($productId);
		$preparedData['DESCRIPTION'] = $preparedData['DESCRIPTION'] ?? $productPreparedData['DESCRIPTION'];
		$preparedData['PREVIEW_PICTURE'] = $preparedData['PREVIEW_PICTURE'] ?? $productPreparedData['PREVIEW_PICTURE'];
		$preparedData['DETAIL_PICTURE'] = $preparedData['PREVIEW_PICTURE'] ?? $productPreparedData['DETAIL_PICTURE'];
		$preparedData['SECTION'] = $productPreparedData['SECTION'];

		$this->preparedIblockData[$elementId] = $preparedData;
		return $preparedData;
	}

	/**
	 * Return prepared for printing property value with $code for row with $rowId.
	 *
	 * @param $code - code of the field related to property.
	 * It could be like "PROPERTY_111" or "PROPERTY_MY_CODE" or "PROPERTY_111_IMAGE" for directory image properties.
	 * @param int $rowId
	 * @return mixed|null
	 */
	public function getPropertyValue(string $code, int $rowId)
	{
		$this->loadMap();

		$elementId = $this->getElementIdByRowId($rowId);
		if (!$elementId)
		{
			return null;
		}

		if (isset($this->directoryImagePropertyCodes[$code]))
		{
			return $this->getPreparedDirectoryImagePropertyValue($this->directoryImagePropertyCodes[$code], $elementId);
		}

		$productProperty = $this->getProductPropertyByFieldCode($code);
		$offerProperty = $this->getOfferPropertyByFieldCode($code);
		if (!$productProperty && !$offerProperty)
		{
			return null;
		}

		$isProduct = isset($this->productIds[$elementId]);
		if ($isProduct && $productProperty)
		{
			return $this->getPreparedPropertyValue($productProperty, $elementId);
		}

		$value = null;
		if ($offerProperty)
		{
			$value = $this->getPreparedPropertyValue($offerProperty, $elementId);
		}
		if ($value)
		{
			return $value;
		}

		$productId = $this->getProductIdByOfferId($elementId);
		if (!$productId || !$productProperty)
		{
			return null;
		}

		return $this->getPreparedPropertyValue($productProperty, $productId);
	}

	protected function getProductPropertyByFieldCode($code): ?array
	{
		$propertyId = $this->getPropertyCodeByFieldCode($code);
		$property = $this->productProperties[$propertyId] ?? null;
		if (!$property)
		{
			$this->fillPropertyCodesMap();
			$propertyId = (int)($this->propertyCodes['product'][$propertyId] ?? 0);
			if ($propertyId > 0)
			{
				$property = $this->productProperties[$propertyId] ?? null;
			}
		}

		return $property;
	}

	protected function getOfferPropertyByFieldCode($code): ?array
	{
		$propertyId = $this->getPropertyCodeByFieldCode($code);
		$property = $this->offerProperties[$propertyId] ?? null;
		if (!$property)
		{
			$this->fillPropertyCodesMap();
			$propertyId = (int)($this->propertyCodes['offer'][$propertyId] ?? 0);
			if ($propertyId > 0)
			{
				$property = $this->offerProperties[$propertyId] ?? null;
			}
		}

		return $property;
	}

	protected function fillPropertyCodesMap(): void
	{
		if ($this->propertyCodes === null)
		{
			$this->propertyCodes = [
				'product' => [],
				'offer' => [],
			];
			foreach ($this->productProperties as $id => $property)
			{
				if (!empty($property['CODE']))
				{
					$this->propertyCodes['product'][$property['CODE']] = $id;
				}
			}
			foreach ($this->offerProperties as $id => $property)
			{
				if (!empty($property['CODE']))
				{
					$this->propertyCodes['offer'][$property['CODE']] = $id;
				}
			}
		}
	}

	protected function loadPropertyValues(): array
	{
		if ($this->propertyValues !== null)
		{
			return $this->propertyValues;
		}
		$this->propertyValues = [];
		$productIds = $this->getProductIds();
		if (empty($productIds) || $this->productIblockId <= 0)
		{
			$this->propertyValues = [];
			return $this->propertyValues;
		}

		$productProperties = $this->loadProductProperties();
		$productPropertyIds = array_keys($productProperties);
		$this->propertyValues = $this->loadIblockPropertyValues($this->productIblockId, $productIds, $productPropertyIds);

		$offerIds = $this->getOfferIds();
		if (empty($offerIds) || $this->offerIblockId <= 0)
		{
			return $this->propertyValues;
		}

		$offerProperties = $this->loadOfferProperties();
		$offerPropertyIds = array_keys($offerProperties);
		$offerPropertyValues = $this->loadIblockPropertyValues($this->offerIblockId, $offerIds, $offerPropertyIds);
		foreach ($offerPropertyValues as $itemId => $values)
		{
			$this->propertyValues[$itemId] = $values;
		}

		return $this->propertyValues;
	}

	protected function loadIblockPropertyValues(int $iblockId, array $itemIds, array $propertyIds): array
	{
		if (empty($propertyIds))
		{
			return [];
		}

		$result = [];

		$iterator = $this->iblockElementClass::GetPropertyValues(
			$iblockId,
			[
				'ID' => $itemIds,
			],
			false,
			[
				'ID' => $propertyIds,
			]
		);
		while($rowPropertyValues = $iterator->fetch())
		{
			$result[(int)$rowPropertyValues['IBLOCK_ELEMENT_ID']] = $rowPropertyValues;
		}

		return $result;
	}

	protected function getRawPropertyValue(array $property, int $elementId)
	{
		$propertyValues = $this->loadPropertyValues();
		return $propertyValues[$elementId][(int)$property['ID']] ?? null;
	}

	protected function getPreparedDirectoryImagePropertyValue($propertyId, int $elementId)
	{
		$preparedPropertyCode = $propertyId . '_IMAGE';
		if (
			isset($this->preparedPropertyValues[$preparedPropertyCode])
			&& array_key_exists($elementId, $this->preparedPropertyValues[$preparedPropertyCode])
		)
		{
			return $this->preparedPropertyValues[$preparedPropertyCode][$elementId];
		}

		$property = $this->productProperties[$propertyId] ?? $this->offerProperties[$propertyId] ?? null;
		if (!$property)
		{
			return null;
		}

		$propertyValue = $this->getRawPropertyValue($property, $elementId);
		if (is_array($propertyValue))
		{
			$propertyValue = reset($propertyValue);
		}
		if (empty($propertyValue))
		{
			return null;
		}

		$directoryValue = $this->getPropertyDirectoryValue($property, $propertyValue);
		$fileId = (int)$directoryValue['FILE_ID'];

		if ($fileId > 0)
		{
			$this->preparedPropertyValues[$preparedPropertyCode][$elementId] = \CFile::GetPath($fileId);
		}
		else
		{
			$this->preparedPropertyValues[$preparedPropertyCode][$elementId] = null;
		}

		return $this->preparedPropertyValues[$preparedPropertyCode][$elementId];
	}

	protected function getPreparedPropertyValue(array $property, int $elementId)
	{
		$propertyId = (int)$property['ID'];
		if (
			isset($this->preparedPropertyValues[$propertyId])
			&& array_key_exists($elementId, $this->preparedPropertyValues[$propertyId])
		)
		{
			return $this->preparedPropertyValues[$propertyId][$elementId];
		}

		$propertyValue = $this->getRawPropertyValue($property, $elementId);
		if (empty($propertyValue))
		{
			return null;
		}
		$isMultiple = (isset($property['MULTIPLE'])
			&& $property['MULTIPLE'] === 'Y'
			&& is_array($propertyValue)
		);

		if ($property['PROPERTY_TYPE'] === 'F')
		{
			if (is_array($propertyValue))
			{
				$propertyValue = reset($propertyValue);
			}
			$propertyValue = (int)$propertyValue;
			if ($propertyValue > 0)
			{
				$this->preparedPropertyValues[$propertyId][$elementId] = \CFile::GetPath($propertyValue);
				return $this->preparedPropertyValues[$propertyId][$elementId];
			}
		}
		if ($property['PROPERTY_TYPE'] === 'S')
		{
			if ($property['USER_TYPE'] === 'HTML')
			{
				$getTextFromValue = static function($propertyValue): ?string {
					if (!empty($propertyValue) && is_string($propertyValue))
					{
						$propertyValue = unserialize($propertyValue, ['allowed_classes' => false]);
					}
					if (is_array($propertyValue) && (isset($propertyValue['HTML']) || isset($propertyValue['TEXT'])))
					{
						return $propertyValue['HTML'] ?? $propertyValue['TEXT'];
					}

					return null;
				};
				if ($isMultiple)
				{
					$propertyValue = array_map($getTextFromValue, $propertyValue);
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple($propertyValue);
				}
				else
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = $getTextFromValue($propertyValue);
				}

				return $this->preparedPropertyValues[$propertyId][$elementId];
			}
			if ($property['USER_TYPE'] === 'Date')
			{
				if ($isMultiple)
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple(
						array_map(
							static function($value) {
								return new Value\DateTime($value);
							},
							$propertyValue
						)
					);
				}
				else
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\DateTime($propertyValue);
				}

				return $this->preparedPropertyValues[$propertyId][$elementId];
			}
			if ($property['USER_TYPE'] === 'DateTime')
			{
				if ($isMultiple)
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple(
						array_map(
							static function($value) {
								return new Value\DateTime(
									$value,
									[
										'format' => DateTime::getFormat(DataProviderManager::getInstance()->getCulture()),
									]
								);
							},
							$propertyValue
						)
					);
				}
				else
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\DateTime(
						$propertyValue,
						[
							'format' => DateTime::getFormat(DataProviderManager::getInstance()->getCulture()),
						]
					);
				}

				return $this->preparedPropertyValues[$propertyId][$elementId];
			}
			if ($property['USER_TYPE'] === 'Money')
			{
				if ($isMultiple)
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple(
						array_map(
							static function ($value) {
								if (!is_string($value))
								{
									return null;
								}

								[$value, $currency] = explode('|', $value);

								return new Money($value, [
									'CURRENCY_ID' => $currency,
								]);
							},
							$propertyValue
						)
					);
				}
				elseif(is_string($propertyValue))
				{
					[$value, $currency] = explode('|', $propertyValue);
					$this->preparedPropertyValues[$propertyId][$elementId] = new Money($value, [
						'CURRENCY_ID' => $currency,
					]);
				}
				else
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = null;
				}

				return $this->preparedPropertyValues[$propertyId][$elementId];
			}
			if ($property['USER_TYPE'] === 'directory')
			{
				if ($isMultiple)
				{
					$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple(
						$this->getPropertyDirectoryValueMultiple($property, $propertyValue)
					);
				}
				else
				{
					$value = $this->getPropertyDirectoryValue($property, $propertyValue);
					$this->preparedPropertyValues[$propertyId][$elementId] = $value['VALUE'] ?? null;
				}

				return $this->preparedPropertyValues[$propertyId][$elementId];
			}
			if ($property['USER_TYPE'] === 'employee' || $property['USER_TYPE'] === 'ECrm')
			{
				if ($isMultiple)
				{
					$propertyValue = reset($propertyValue);
					$isMultiple = false;
				}
			}
		}
		elseif ($property['PROPERTY_TYPE'] === 'L')
		{
			if ($isMultiple)
			{
				$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple(
					$this->getPropertyEnumValueMultiple($propertyId, $propertyValue)
				);
			}
			else
			{
				$this->preparedPropertyValues[$propertyId][$elementId] = $this->getPropertyEnumValue($propertyId, $propertyValue);
			}

			return $this->preparedPropertyValues[$propertyId][$elementId];
		}
		elseif ($property['PROPERTY_TYPE'] === 'E')
		{
			if ($isMultiple)
			{
				$this->preparedPropertyValues[$propertyId][$elementId] = new Value\Multiple(
					$this->getPropertyElementValueMultiple($propertyValue)
				);
			}
			else
			{
				$this->preparedPropertyValues[$propertyId][$elementId] = $this->getPropertyElementValue((int)$propertyValue);
			}

			return $this->preparedPropertyValues[$propertyId][$elementId];
		}
		elseif ($property['PROPERTY_TYPE'] === 'N')
		{
			if ($isMultiple)
			{
				$propertyValue = array_map('floatval', $propertyValue);
			}
			else
			{
				$propertyValue = (float)$propertyValue;
			}
		}

		if ($isMultiple)
		{
			$propertyValue = new Value\Multiple($propertyValue);
		}

		$this->preparedPropertyValues[$propertyId][$elementId] = $propertyValue;
		return $this->preparedPropertyValues[$propertyId][$elementId];
	}

	protected function getPropertyDirectoryValue(array $property, $value)
	{
		if (!isset($this->loadedDirectoryPropertyValues[$property['ID']][$value]))
		{
			$this->loadedDirectoryPropertyValues[$property['ID']][$value] = \CIBlockPropertyDirectory::GetExtendedValue($property, ['VALUE' => $value]);
		}

		return $this->loadedDirectoryPropertyValues[$property['ID']][$value];
	}

	protected function getPropertyDirectoryValueMultiple(array $property, array $values): array
	{
		$result = [];

		foreach ($values as $value)
		{
			$value = $this->getPropertyDirectoryValue($property, $value);
			$result[] = $value['VALUE'] ?? null;
		}

		return $result;
	}

	protected function getPropertyEnumValue(int $propertyId, $propertyValue)
	{
		if (!isset($this->loadedEnumPropertyValues[$propertyId]))
		{
			$this->loadedEnumPropertyValues[$propertyId] = [];
			$enums = \CIBlockPropertyEnum::GetList([], [
				'PROPERTY_ID' => $propertyId,
			]);
			while($enum = $enums->Fetch())
			{
				$this->loadedEnumPropertyValues[$propertyId][$enum['ID']] = $enum['VALUE'];
			}
		}

		return $this->loadedEnumPropertyValues[$propertyId][$propertyValue] ?? null;
	}

	protected function getPropertyEnumValueMultiple(int $propertyId, array $values): array
	{
		$result = [];

		foreach ($values as $value)
		{
			$result[] = $this->getPropertyEnumValue($propertyId, $value);
		}

		return $result;
	}

	protected function getPropertyElementValue(int $propertyValue)
	{
		if ($this->linkedElements === null)
		{
			$this->linkedElements = [];
			$elementProperties = [];
			$properties = array_merge($this->productProperties, $this->offerProperties);
			foreach ($properties as $property)
			{
				if ($property['PROPERTY_TYPE'] === 'E')
				{
					$propertyId = (int)$property['ID'];
					$elementProperties[$propertyId] = $property;
				}
			}

			foreach ($elementProperties as $property)
			{
				foreach ($this->getProductIds() as $productId)
				{
					$value = $this->getRawPropertyValue($property, $productId);
					if (
						isset($property['MULTIPLE'])
						&& $property['MULTIPLE'] === 'Y'
						&& is_array($value)
					)
					{
						foreach ($value as $id)
						{
							$this->linkedElements[$id] = [];
						}
					}
					elseif ($value > 0)
					{
						$this->linkedElements[$value] = [];
					}
				}
			}

			$notFoundElements = [];
			foreach ($this->linkedElements as $itemId => $data)
			{
				if (isset($this->iblockValues[$itemId]))
				{
					$this->linkedElements[$itemId] = $this->iblockValues[$itemId]['NAME'];
				}
				else
				{
					$notFoundElements[] = $itemId;
				}
			}

			if (!empty($notFoundElements))
			{
				$iterator = $this->elementTableClass::getList([
					'select' => ['ID', 'NAME'],
					'filter' => [
						'@ID' => $notFoundElements,
					],
				]);
				while($data = $iterator->fetch())
				{
					$this->linkedElements[(int)$data['ID']] = $data['NAME'];
				}
			}
		}

		return $this->linkedElements[$propertyValue] ?? null;
	}

	protected function getPropertyElementValueMultiple(array $values): array
	{
		$result = [];

		foreach ($values as $value)
		{
			$result[] = $this->getPropertyElementValue($value);
		}

		return $result;
	}

	protected function getFieldsStub(): array
	{
		if ($this->fields !== null)
		{
			return $this->fields;
		}

		$this->fields = [];

		$properties = array_merge($this->loadProductProperties(), $this->loadOfferProperties());
		foreach ($properties as $property)
		{
			if ($property['PROPERTY_TYPE'] === 'S' && $property['USER_TYPE'] === 'directory')
			{
				$this->addDirectoryFields($property);
				continue;
			}
			$field = $this->makeFieldFromProperty($property);
			if ($field)
			{
				$this->addField($property, $field);
			}
		}

		return $this->fields;
	}

	/**
	 * Return properties data for products iblock.
	 *
	 * @return array
	 */
	public function loadProductProperties(): array
	{
		if ($this->productProperties !== null)
		{
			return $this->productProperties;
		}
		$this->productProperties = [];
		if ($this->productIblockId <= 0)
		{
			return $this->productProperties;
		}

		$this->productProperties = $this->loadPropertiesData($this->productIblockId);

		return $this->productProperties;
	}

	/**
	 * Return properties data for offers iblock.
	 *
	 * @return array
	 */
	public function loadOfferProperties(): array
	{
		if ($this->offerProperties !== null)
		{
			return $this->offerProperties;
		}
		$this->offerProperties = [];
		if ($this->offerIblockId <= 0)
		{
			return $this->offerProperties;
		}

		$this->offerProperties = $this->loadPropertiesData($this->offerIblockId);

		return $this->offerProperties;
	}

	protected function loadPropertiesData(int $iblockId, array $propertyIds = []): array
	{
		$properties = [];
		$filter = ['ACTIVE' => 'Y'];
		$query = \CIBlock::GetProperties($iblockId, ['SORT' => 'ASC'], $filter);
		while ($property = $query->Fetch())
		{
			if (!$this->isPropertyPrintable($property))
			{
				continue;
			}
			if (!empty($propertyIds) && !in_array($property['ID'], $propertyIds))
			{
				continue;
			}
			$properties[(int)$property['ID']] = $property;
		}

		return $properties;
	}

	/**
	 * Return true if $property can be printed in a document.
	 *
	 * @param array $property
	 * @return bool
	 */
	public function isPropertyPrintable(array $property): bool
	{
		if ($property['PROPERTY_TYPE'] === 'S' && !empty($property['USER_TYPE']))
		{
			return in_array($property['USER_TYPE'], [
				'HTML', 'Date', 'DateTime', 'Money', 'employee', 'ECrm', 'Sequence', 'directory',
			]);
		}

		return in_array($property['PROPERTY_TYPE'], [
			'S', 'N', 'F', 'L', 'E',
		]);
	}

	protected function makeFieldFromProperty(array $property): ?array
	{
		$field = [
			'TITLE' => $property['NAME'],
		];
		$type = $this->getFieldType($property);
		if ($type)
		{
			$field['TYPE'] = $type;
			return $field;
		}
		if ($property['PROPERTY_TYPE'] !== 'S' || empty($property['USER_TYPE']))
		{
			return $field;
		}
		if ($property['USER_TYPE'] === 'employee')
		{
			$field['PROVIDER'] = \Bitrix\DocumentGenerator\DataProvider\User::class;
			$field['OPTIONS'] = [
				'FORMATTED_NAME_FORMAT' => [
					'format' => CrmEntityDataProvider::getNameFormat(),
				]
			];
			return $field;
		}
		if ($property['USER_TYPE'] === 'ECrm')
		{
			$provider = null;
			$entityTypes = [];
			if ($property['USER_TYPE_SETTINGS']['LEAD'] === 'Y')
			{
				$entityTypes[] = \CCrmOwnerType::Lead;
			}
			if ($property['USER_TYPE_SETTINGS']['CONTACT'] === 'Y')
			{
				$entityTypes[] = \CCrmOwnerType::Contact;
			}
			if ($property['USER_TYPE_SETTINGS']['COMPANY'] === 'Y')
			{
				$entityTypes[] = \CCrmOwnerType::Company;
			}
			if ($property['USER_TYPE_SETTINGS']['DEAL'] === 'Y')
			{
				$entityTypes[] = \CCrmOwnerType::Deal;
			}
			if (count($entityTypes) !== 1)
			{
				return $field;
			}

			$ownerTypeId = $entityTypes[0];
			$provider = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvider($ownerTypeId);

			if ($provider)
			{
				$field['PROVIDER'] = $provider;
				$field['OPTIONS']['isLightMode'] = true;
			}
		}

		return $field;
	}

	protected function getFieldType(array $property): ?string
	{
		if ($property['PROPERTY_TYPE'] === 'F')
		{
			return DataProvider\Product::FIELD_TYPE_IMAGE;
		}
		if ($property['PROPERTY_TYPE'] !== 'S' || empty($property['USER_TYPE']))
		{
			return null;
		}

		if ($property['USER_TYPE'] === 'HTML')
		{
			return DataProvider\Product::FIELD_TYPE_TEXT;
		}
		if ($property['USER_TYPE'] === 'Date' || $property['USER_TYPE'] === 'DateTime')
		{
			return DataProvider\Product::FIELD_TYPE_DATE;
		}
		if ($property['USER_TYPE'] === 'Money')
		{
			return Money::class;
		}

		return null;
	}

	protected function addField(array $property, array $field): self
	{
		$code = $this->getPropertyFieldCode($property['ID']);
		$this->fields[$code] = $field;
		if (!empty($property['CODE']))
		{
			$code = $this->getPropertyFieldCode($property['CODE']);
			$this->fields[$code] = $field;
		}

		return $this;
	}

	protected function addDirectoryFields(array $property): void
	{
		$field = $this->makeFieldFromProperty($property);
		$this->addField($property, $field);

		$directoryImageFakeProperty = $property;
		$directoryImageFakeProperty['NAME'] = Loc::getMessage('CRM_DOCUMENTGENERATOR_PRODUCTLOADER_DIRECTORY_IMAGE_FIELD', [
			'#NAME#' => $directoryImageFakeProperty['NAME'],
		]);
		$directoryImageFakeProperty['ID'] .= '_IMAGE';
		$this->directoryImagePropertyCodes[$this->getPropertyFieldCode($directoryImageFakeProperty['ID'])] = (int)$property['ID'];
		if (!empty($directoryImageFakeProperty['CODE']))
		{
			$directoryImageFakeProperty['CODE'] .= '_IMAGE';
			$this->directoryImagePropertyCodes[$this->getPropertyFieldCode($directoryImageFakeProperty['CODE'])] = (int)$property['ID'];
		}

		$directoryImageFakeProperty['PROPERTY_TYPE'] = 'F';
		$field = $this->makeFieldFromProperty($directoryImageFakeProperty);
		$this->addField($directoryImageFakeProperty, $field);
	}

	protected function getPropertyFieldCode($code): string
	{
		return static::FIELD_PROPERTY_PREFIX . $code;
	}

	protected function getPropertyCodeByFieldCode(string $code): string
	{
		return mb_substr($code, mb_strlen(static::FIELD_PROPERTY_PREFIX));
	}
}
