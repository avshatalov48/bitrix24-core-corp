<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\PropertyIndex;

use Bitrix\Iblock;

class Indexer
{
	protected $iblockId = 0;
	protected $lastElementId = null;
	protected static $catalog = null;
	protected $skuIblockId = 0;
	protected $skuPropertyId = 0;
	protected $sectionParents = array();
	protected $propertyFilter = null;
	protected $priceFilter = null;

	/** @var Dictionary */
	protected $dictionary = null;
	/** @var Storage */
	protected $storage = null;

	/**
	 * @param integer $iblockId Information block identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
	}

	/**
	 * Initializes internal object state. Must be called before usage.
	 *
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function init()
	{
		$this->dictionary = new Dictionary($this->iblockId);
		$this->storage = new Storage($this->iblockId);
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
	 * Sets index mark/cursor.
	 *
	 * @param integer $lastElementId Element identifier.
	 *
	 * @return void
	 */
	public function setLastElementId($lastElementId)
	{
		$this->lastElementId = intval($lastElementId);
	}

	/**
	 * Returns index mark/cursor. Last indexed element or null if there was none.
	 *
	 * @return integer|null
	 */
	public function getLastElementId()
	{
		return $this->lastElementId;
	}

	/**
	 * Checks if storage and dictionary exists in the database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	public function isExists()
	{
		return $this->storage->isExists() && $this->dictionary->isExists();
	}

	/**
	 * Drops and recreates the index. So one can start indexing.
	 *
	 * @return boolean
	 */
	public function startIndex()
	{
		if ($this->storage->isExists())
			$this->storage->drop();
		if ($this->dictionary->isExists())
			$this->dictionary->drop();

		$this->dictionary->create();
		$this->storage->create();

		return true;
	}

	/**
	 * End of index creation. Marks iblock as indexed.
	 *
	 * @return boolean
	 */
	public function endIndex()
	{
		\Bitrix\Iblock\IblockTable::update($this->iblockId, array(
			"PROPERTY_INDEX" => "Y",
		));
		//TODO: replace \CIBlock::CleanCache to d7 method
		\CIBlock::CleanCache($this->iblockId);
		if ($this->skuIblockId)
		{
			\Bitrix\Iblock\IblockTable::update($this->skuIblockId, array(
				"PROPERTY_INDEX" => "Y",
			));
			//TODO: replace \CIBlock::CleanCache to d7 method
			\CIBlock::CleanCache($this->skuIblockId);
		}

		return true;
	}

	/**
	 * Does index step. Returns number of indexed elements.
	 *
	 * @param integer $interval Time limit for execution.
	 * @return integer
	 */
	public function continueIndex($interval = 0)
	{
		if ($interval > 0)
			$endTime = microtime(true) + $interval;
		else
			$endTime = 0;

		$indexedCount = 0;

		if ($this->lastElementId === null)
			$lastElementId = $this->storage->getLastStoredElementId();
		else
			$lastElementId = $this->lastElementId;

		$elementList = $this->getElementsCursor($lastElementId);
		while ($element = $elementList->fetch())
		{
			$this->indexElement($element["ID"]);
			$indexedCount++;
			$this->lastElementId = $element["ID"];
			if ($endTime > 0 && $endTime < microtime(true))
				break;
		}
		return $indexedCount;
	}

	/**
	 * Returns number of elements to be indexed.
	 *
	 * @return integer
	 */
	public function estimateElementCount()
	{
		$filter = array(
			"IBLOCK_ID" => $this->iblockId,
			"ACTIVE" => "Y",
			"CHECK_PERMISSIONS" => "N",
		);

		return \CIBlockElement::getList(array(), $filter, array());
	}

	/**
	 * Indexes one element.
	 *
	 * @param integer $elementId Element identifier.
	 *
	 * @return void
	 */
	public function indexElement($elementId)
	{
		$element = new Element($this->iblockId, $elementId);
		$element->loadFromDatabase();

		$elementSections = $element->getSections();
		$elementIndexValues = $this->getSectionIndexEntries($element);

		foreach ($element->getParentSections() as $sectionId)
		{
			foreach ($elementIndexValues as $facetId => $values)
			{
				foreach ($values as $value)
				{
					$this->storage->queueIndexEntry(
						$sectionId,
						$elementId,
						$facetId,
						$value["VALUE"],
						$value["VALUE_NUM"],
						in_array($sectionId, $elementSections)
					);
				}
			}
		}

		foreach ($elementIndexValues as $facetId => $values)
		{
			foreach ($values as $value)
			{
				$this->storage->queueIndexEntry(
					0,
					$elementId,
					$facetId,
					$value["VALUE"],
					$value["VALUE_NUM"],
					empty($elementSections)
				);
			}
		}

		$this->storage->flushIndexEntries();
	}

	/**
	 * Removes element from the index.
	 *
	 * @param integer $elementId Element identifier.
	 *
	 * @return void
	 */
	public function deleteElement($elementId)
	{
		$this->storage->deleteIndexElement($elementId);
	}

	/**
	 * Returns elements list database cursor for indexing.
	 * This list contains only active elements,
	 * starts with $lastElementID and ID in ascending order.
	 *
	 * @param int $lastElementID Element identifier.
	 * @return \Bitrix\Main\ORM\Query\Result
	 */
	protected function getElementsCursor(int $lastElementID = 0): \Bitrix\Main\ORM\Query\Result
	{
		$filter = [
			'=IBLOCK_ID' => $this->iblockId,
			'=ACTIVE' => 'Y',
			'=WF_STATUS_ID' => 1,
			'==WF_PARENT_ELEMENT_ID' => null,
		];

		if ($lastElementID > 0)
		{
			$filter['>ID'] = $lastElementID;
		}

		return Iblock\ElementTable::getList([
			'select' => ['ID'],
			'filter' => $filter,
			'order' => ['ID' => 'ASC'],
		]);
	}

	/**
	 * Returns all relevant information for the element in section context.
	 *
	 * @param Element $element Loaded from the database element information.
	 *
	 * @return array
	 */
	protected function getSectionIndexEntries(Element $element)
	{
		$result = array(
			1 => array( //Section binding
				array("VALUE" => 0, "VALUE_NUM" => 0.0)
			)
		);

		foreach ($this->getFilterProperty(Storage::DICTIONARY) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				$value = intval($value);
				$result[$facetId][$value] = array(
					"VALUE" => $value,
					"VALUE_NUM" => 0.0,
				);
			}
		}

		foreach ($this->getFilterProperty(Storage::STRING) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				$valueId = $this->dictionary->getStringId($value);
				$result[$facetId][$valueId] = array(
					"VALUE" => $valueId,
					"VALUE_NUM" => 0.0,
				);
			}
		}

		foreach ($this->getFilterProperty(Storage::NUMERIC) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				$value = doubleval($value);
				$result[$facetId][md5($value)] = array(
					"VALUE" => 0,
					"VALUE_NUM" => $value,
				);
			}
		}

		foreach ($this->getFilterProperty(Storage::DATETIME) as $propertyId)
		{
			$facetId = $this->storage->propertyIdToFacetId($propertyId);
			$result[$facetId] = array();
			$propertyValues = $element->getPropertyValues($propertyId);
			foreach ($propertyValues as $value)
			{
				//Save date only based on server time.
				$timestamp = MakeTimeStamp($value, "YYYY-MM-DD HH:MI:SS");
				$value = date('Y-m-d', $timestamp);
				$timestamp = MakeTimeStamp($value, "YYYY-MM-DD");
				$valueId = $this->dictionary->getStringId($value);
				$result[$facetId][$valueId] = array(
					"VALUE" => $valueId,
					"VALUE_NUM" => $timestamp,
				);
			}
		}

		foreach ($this->getFilterPrices() as $priceId)
		{
			$facetId = $this->storage->priceIdToFacetId($priceId);
			$result[$facetId] = array();
			$elementPrices = $element->getPriceValues($priceId);
			if ($elementPrices)
			{
				foreach ($elementPrices as $currency => $priceValues)
				{
					$currencyId = $this->dictionary->getStringId($currency);
					foreach ($priceValues as $price)
					{
						$result[$facetId][$currencyId.":".$price] = array(
							"VALUE" => $currencyId,
							"VALUE_NUM" => $price,
						);
					}
				}
			}
		}

		return array_filter($result, "count");
	}

	/**
	 * Returns list of properties IDs marked as indexed to the section according their "TYPE".
	 * - N - maps to Indexer::NUMERIC
	 * - S - to Indexer::STRING
	 * - F, E, G, L - to Indexer::DICTIONARY
	 *
	 * @param integer $propertyType Property classification for the index.
	 *
	 * @return integer[]
	 */
	protected function getFilterProperty($propertyType)
	{
		if (!isset($this->propertyFilter))
		{
			$this->propertyFilter = array(
				Storage::DICTIONARY => array(),
				Storage::STRING => array(),
				Storage::NUMERIC => array(),
				Storage::DATETIME => array(),
			);
			$propertyList = \Bitrix\Iblock\SectionPropertyTable::getList(array(
				"select" => array("PROPERTY_ID", "PROPERTY.PROPERTY_TYPE", "PROPERTY.USER_TYPE"),
				"filter" => array(
					"=IBLOCK_ID" => array($this->iblockId, $this->skuIblockId),
					"=SMART_FILTER" => "Y",
				),
			));
			while ($link = $propertyList->fetch())
			{
				$storageType = $this->getPropertyStorageType(array(
					"PROPERTY_TYPE" => $link["IBLOCK_SECTION_PROPERTY_PROPERTY_PROPERTY_TYPE"],
					"USER_TYPE" => $link["IBLOCK_SECTION_PROPERTY_PROPERTY_USER_TYPE"],
				));
				$this->propertyFilter[$storageType][] = $link["PROPERTY_ID"];
			}
		}
		return $this->propertyFilter[$propertyType];
	}

	/**
	 * Returns list of price IDs for storing in the index.
	 *
	 * @return integer[]
	 */
	protected function getFilterPrices()
	{
		if (!isset($this->priceFilter))
		{
			$this->priceFilter = array();
			if (self::$catalog)
			{
				//TODO: replace \CCatalogGroup::GetListArray after create cached d7 method
				$priceList = \CCatalogGroup::GetListArray();
				if (!empty($priceList))
					$this->priceFilter = array_keys($priceList);
				unset($priceList);
			}
		}
		return $this->priceFilter;
	}

	/**
	 * Returns storage type for the property.
	 * - N - maps to Indexer::NUMERIC
	 * - S - to Indexer::STRING
	 * - F, E, G, L - to Indexer::DICTIONARY
	 *
	 * @param array[string]string $property Property description.
	 *
	 * @return integer
	 */
	public static function getPropertyStorageType($property)
	{
		if (isset($property['PROPERTY_TYPE']) && $property["PROPERTY_TYPE"] === Iblock\PropertyTable::TYPE_NUMBER)
		{
			return Storage::NUMERIC;
		}
		elseif (isset($property['USER_TYPE']) && $property["USER_TYPE"] === \CIBlockPropertyDateTime::USER_TYPE)
		{
			return Storage::DATETIME;
		}
		elseif (isset($property['PROPERTY_TYPE']) && $property["PROPERTY_TYPE"] === Iblock\PropertyTable::TYPE_STRING)
		{
			return Storage::STRING;
		}
		else
		{
			return Storage::DICTIONARY;
		}
	}
}
