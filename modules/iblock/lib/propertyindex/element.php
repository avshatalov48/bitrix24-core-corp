<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;

class Element
{
	protected $iblockId = 0;
	protected $elementId = 0;
	protected static $catalog = null;
	protected static $filterPropertyID = array();
	protected $skuIblockId = 0;
	protected $skuPropertyId = 0;
	protected $elementPropertyValues = array();
	protected $elementPrices = array();
	protected $elementSections = array();
	protected static $sectionParents = array();

	/**
	 * @param integer $iblockId Information block identifier.
	 * @param integer $elementId Element identifier.
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct($iblockId, $elementId)
	{
		$this->iblockId = intval($iblockId);
		$this->elementId = intval($elementId);

		if (self::$catalog === null)
		{
			self::$catalog = \Bitrix\Main\Loader::includeModule("catalog");
		}

		if (self::$catalog)
		{
			$catalog = \CCatalogSKU::getInfoByProductIBlock($this->iblockId);
			if (!empty($catalog) && is_array($catalog))
			{
				$this->skuIblockId = $catalog["IBLOCK_ID"];
				$this->skuPropertyId = $catalog["SKU_PROPERTY_ID"];
			}
		}
	}

	/**
	 * Returns identifier of the element.
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->elementId;
	}

	/**
	 * Fills element with data from the database.
	 *
	 * @return void
	 */
	public function loadFromDatabase()
	{
		$this->elementPropertyValues = array();
		$this->loadElementProperties($this->iblockId, array(
			"IBLOCK_ID" => $this->iblockId,
			"=ID" => $this->elementId,
		));
		if ($this->skuIblockId > 0 && $this->skuPropertyId > 0)
		{
			$this->loadElementProperties($this->skuIblockId, array(
				"IBLOCK_ID" => $this->iblockId,
				"ACTIVE" => "Y",
				"=PROPERTY_".$this->skuPropertyId => $this->elementId,
			));
		}

		$this->elementPrices = array();
		if (self::$catalog)
		{
			$elements = $this->elementPropertyValues["IBLOCK_ELEMENT_ID"] ?? [$this->elementId];
			$this->loadElementPrices($elements);
		}

		$this->elementSections = array();
		$this->loadElementSections($this->elementId);
	}

	/**
	 * Fills member elementPropertyValues member with property values.
	 *
	 * @param int $iblockId Information block identifier.
	 * @param array[string]string $elementFilter Element property values criteria.
	 *
	 * @return void
	 */
	protected function loadElementProperties(int $iblockId, array $elementFilter)
	{
		if (!isset(self::$filterPropertyID[$iblockId]))
		{
			self::$filterPropertyID[$iblockId] = [];
			$properties = \Bitrix\Iblock\SectionPropertyTable::getList(array(
				"select" => array("PROPERTY_ID"),
				"filter" => array(
					"=IBLOCK_ID" => array($this->iblockId, $this->skuIblockId),
					"=SMART_FILTER" => "Y",
				),
			));
			while ($property = $properties->fetch())
			{
				self::$filterPropertyID[$iblockId][] = $property['PROPERTY_ID'];
			}
			unset($property);
			unset($properties);
		}

		$elementList = \CIBlockElement::getPropertyValues(
			$iblockId,
			$elementFilter,
			false,
			array('ID' => self::$filterPropertyID[$iblockId])
		);

		while ($element = $elementList->fetch())
		{
			foreach ($element as $propertyId => $value)
			{
				if ($value !== false)
				{
					if (!isset($this->elementPropertyValues[$propertyId]))
						$this->elementPropertyValues[$propertyId] = array();

					if (is_array($value))
						$this->elementPropertyValues[$propertyId] = array_merge($this->elementPropertyValues[$propertyId], $value);
					else
						$this->elementPropertyValues[$propertyId][] = $value;
				}
			}
		}
	}

	/**
	 * Fills member elementPrices member with prices.
	 *
	 * @param integer[] $productList Identifiers of the elements.
	 *
	 * @return void
	 */
	protected function loadElementPrices(array $productList)
	{
		$priceList = \Bitrix\Catalog\PriceTable::getList(array(
			'select' => array('ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'QUANTITY_FROM', 'QUANTITY_TO'),
			'filter' => array('@PRODUCT_ID' => $productList)
		));
		while($price = $priceList->fetch())
		{
			if (!isset($this->elementPrices[$price["CATALOG_GROUP_ID"]][$price["CURRENCY"]]))
				$this->elementPrices[$price["CATALOG_GROUP_ID"]][$price["CURRENCY"]] = array();
			$priceValue = (float)$price["PRICE"];
			$this->elementPrices[$price["CATALOG_GROUP_ID"]][$price["CURRENCY"]][(string)$priceValue] = $priceValue;
		}
		unset($price);
		unset($priceList);

		foreach ($this->elementPrices as $catalogGroupId => $currencyPrices)
		{
			foreach ($currencyPrices as $currency => $prices)
			{
				if (count($prices) > 2)
				{
					$this->elementPrices[$catalogGroupId][$currency] = array(
						min($prices),
						max($prices),
					);
				}
			}
			unset($currency, $prices);
		}
		unset($catalogGroupId, $currencyPrices);
	}

	/**
	 * Fills member elementSections member with sections identifiers of the element.
	 *
	 * @param integer $elementId Identifier of the element.
	 *
	 * @return void
	 */
	protected function loadElementSections($elementId)
	{
		$sectionList = \CIBlockElement::getElementGroups($elementId, true, array("ID"));
		while ($section = $sectionList->fetch())
		{
			$this->elementSections[] = $section["ID"];
		}
	}

	/**
	 * Returns loaded property values.
	 *
	 * @param integer $propertyId Property identifier.
	 *
	 * @return array[]mixed
	 */
	public function getPropertyValues($propertyId)
	{
		if (!$this->elementPropertyValues[$propertyId])
			return array();
		else
			return $this->elementPropertyValues[$propertyId];
	}

	/**
	 * Returns loaded price values.
	 *
	 * @param integer $priceId Price identifier.
	 *
	 * @return mixed
	 */
	public function getPriceValues($priceId)
	{
		return $this->elementPrices[$priceId];
	}

	/**
	 * Returns true if section is the one element connected with.
	 *
	 * @param integer $sectionId Section identifier.
	 *
	 * @return boolean
	 */
	public function isElementSection($sectionId)
	{
		return in_array($sectionId, $this->elementSections);
	}

	/**
	 * Returns unique array of the element sections.
	 *
	 * @return integer[]
	 */
	public function getSections()
	{
		return array_unique($this->elementSections, SORT_NUMERIC);
	}

	/**
	 * Returns unique array of the element sections with all of their parents.
	 *
	 * @return integer[]
	 */
	public function getParentSections()
	{
		$sections = array();
		foreach ($this->getSections() as $sectionId)
		{
			$sections = array_merge($sections, $this->getSectionParents($sectionId));
		}
		return array_unique($sections, SORT_NUMERIC);
	}

	/**
	 * Returns all section parents.
	 *
	 * @param integer $sectionId Section identifier.
	 *
	 * @return mixed
	 */
	public function getSectionParents($sectionId)
	{
		if (!isset(self::$sectionParents[$sectionId]))
		{
			$sections = array();
			$sectionList = \CIBlockSection::getNavChain($this->iblockId, $sectionId, array("ID"));
			while ($section = $sectionList->fetch())
			{
				$sections[] = $section["ID"];
			}
			self::$sectionParents[$sectionId] = $sections;
		}
		return self::$sectionParents[$sectionId];
	}
}
