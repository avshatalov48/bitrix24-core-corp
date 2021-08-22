<?php

namespace Bitrix\Catalog\v2\Integration\UI\EntitySelector;

use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\Product\PropertyCatalogFeature;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\v2\Iblock\IblockInfo;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Iblock\Component\Tools;
use Bitrix\Iblock\PropertyTable;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class ProductProvider extends BaseProvider
{
	private const PRODUCT_LIMIT = 20;
	private const PRODUCT_ENTITY_ID = 'product';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['iblockId'] = (int)($options['iblockId'] ?? 0);
		$this->options['basePriceId'] = (int)($options['basePriceId'] ?? 0);
	}

	public function isAvailable(): bool
	{
		global $USER;

		if (
			!$USER->isAuthorized()
			|| !($USER->canDoOperation('catalog_read') || $USER->canDoOperation('catalog_view'))
		)
		{
			return false;
		}

		if ($this->getIblockId() <= 0 || !$this->getIblockInfo())
		{
			return false;
		}

		return true;
	}

	public function getItems(array $ids): array
	{
		$items = [];

		foreach ($this->getProductsByIds($ids) as $product)
		{
			$items[] = $this->makeItem($product);
		}

		return $items;
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->getItems($ids);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$recentItemsCount = count($dialog->getRecentItems()->getEntityItems(self::PRODUCT_ENTITY_ID));

		if ($recentItemsCount < self::PRODUCT_LIMIT)
		{
			foreach ($this->getProducts() as $product)
			{
				$dialog->addRecentItem($this->makeItem($product));
			}
		}
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$products = $this->getProductsBySearchString($searchQuery->getQuery());

		if (!empty($products))
		{
			foreach ($products as $product)
			{
				$dialog->addItem(
					$this->makeItem($product)
				);
			}

			if ($this->shouldDisableCache($products))
			{
				$searchQuery->setCacheable(false);
			}
		}
	}

	protected function makeItem(array $product): Item
	{
		$customData = array_filter(array_intersect_key($product, [
			'SKU_PROPERTIES' => true,
			'SEARCH_PROPERTIES' => true,
			'PREVIEW_TEXT' => true,
			'DETAIL_TEXT' => true,
			'PARENT_NAME' => true,
			'PARENT_SEARCH_PROPERTIES' => true,
			'PARENT_PREVIEW_TEXT' => true,
			'PARENT_DETAIL_TEXT' => true,
		]));

		return new Item([
			'id' => $product['ID'],
			'entityId' => self::PRODUCT_ENTITY_ID,
			'title' => $product['NAME'],
			'supertitle' => $product['SKU_PROPERTIES'],
			'caption' => [
				'text' => $product['PRICE'],
				'type' => 'html',
			],
			'avatar' => $product['IMAGE'],
			'customData' => $customData,
		]);
	}

	private function getIblockId()
	{
		return $this->getOptions()['iblockId'];
	}

	private function getBasePriceId()
	{
		return $this->getOptions()['basePriceId'];
	}

	private function getIblockInfo(): ?IblockInfo
	{
		static $iblockInfo = null;

		if ($iblockInfo === null)
		{
			$iblockInfo = ServiceContainer::getIblockInfo($this->getIblockId());
		}

		return $iblockInfo;
	}

	private function getImageSource(int $id): ?string
	{
		if ($id <= 0)
		{
			return null;
		}

		$file = \CFile::GetFileArray($id);
		if (!$file)
		{
			return null;
		}

		return Tools::getImageSrc($file, true) ?: null;
	}

	private function getProductsByIds(array $ids): array
	{
		[$productIds, $offerIds] = $this->separateOffersFromProducts($ids);

		$products = $this->getProducts([
			'filter' => ['=ID' => $productIds],
			'offer_filter' => ['=ID' => $offerIds],
			'sort' => [],
		]);

		// sort $products by $productIds
		return $this->sortProductsByIds($products, $productIds);
	}

	private function separateOffersFromProducts(array $ids): array
	{
		$iblockInfo = $this->getIblockInfo();
		if (!$iblockInfo)
		{
			return [[], []];
		}

		$productIds = $ids;
		$offerIds = [];

		if ($iblockInfo->canHaveSku())
		{
			$productList = \CCatalogSku::getProductList($ids, $iblockInfo->getSkuIblockId());
			if (!empty($productList))
			{
				$productIds = [];
				$counter = 0;

				foreach ($ids as $id)
				{
					if ($counter >= self::PRODUCT_LIMIT)
					{
						break;
					}

					if (isset($productList[$id]))
					{
						$productId = $productList[$id]['ID'];

						if (isset($productIds[$productId]))
						{
							continue;
						}

						$offerIds[] = $id;
						$productIds[$productId] = $productId;
					}
					else
					{
						$productIds[$id] = $id;
					}

					$counter++;
				}

				$productIds = array_values($productIds);
			}
		}

		return [$productIds, $offerIds];
	}

	private function sortProductsByIds(array $products, array $ids): array
	{
		$sorted = [];

		foreach ($ids as $id)
		{
			if (isset($products[$id]))
			{
				$sorted[$id] = $products[$id];
			}
		}

		return $sorted;
	}

	private function getProductsBySearchString(string $searchString = ''): array
	{
		$iblockInfo = $this->getIblockInfo();
		if (!$iblockInfo)
		{
			return [];
		}

		$productFilter = [];
		$offerFilter = [];

		if ($searchString !== '')
		{
			if ($iblockInfo->canHaveSku())
			{
				$productFilter[] = [
					'LOGIC' => 'OR',
					'*SEARCHABLE_CONTENT' => $searchString,
					'=ID' => \CIBlockElement::SubQuery('PROPERTY_' . $iblockInfo->getSkuPropertyId(), [
						'CHECK_PERMISSIONS' => 'Y',
						'MIN_PERMISSION' => 'R',
						'ACTIVE' => 'Y',
						'ACTIVE_DATE' => 'Y',
						'IBLOCK_ID' => $iblockInfo->getSkuIblockId(),
						'*SEARCHABLE_CONTENT' => $searchString,
					]),
				];
				$offerFilter['*SEARCHABLE_CONTENT'] = $searchString;
			}
			else
			{
				$productFilter['*SEARCHABLE_CONTENT'] = $searchString;
			}
		}

		return $this->getProducts([
			'filter' => $productFilter,
			'offer_filter' => $offerFilter,
		]);
	}

	private function getProducts(array $parameters = []): array
	{
		$iblockInfo = $this->getIblockInfo();
		if (!$iblockInfo)
		{
			return [];
		}

		$productFilter = (array)($parameters['filter'] ?? []);
		$offerFilter = (array)($parameters['offer_filter'] ?? []);

		$products = $this->loadElements([
			'filter' => array_merge($productFilter, [
				'IBLOCK_ID' => $iblockInfo->getProductIblockId(),
			]),
			'limit' => self::PRODUCT_LIMIT,
		]);
		if (empty($products))
		{
			return [];
		}

		$products = $this->loadProperties($products, $iblockInfo->getProductIblockId(), $iblockInfo);

		if ($iblockInfo->canHaveSku())
		{
			$products = $this->loadOffers($products, $iblockInfo, $offerFilter);
		}

		$products = $this->loadPrices($products);

		return $products;
	}

	private function loadElements(array $parameters = []): array
	{
		$elements = [];

		$additionalFilter = (array)($parameters['filter'] ?? []);
		$limit = (int)($parameters['limit'] ?? 0);

		$filter = [
			'CHECK_PERMISSIONS' => 'Y',
			'MIN_PERMISSION' => 'R',
			'ACTIVE' => 'Y',
			'ACTIVE_DATE' => 'Y',
		];
		$selectFields = array_filter(array_unique(array_merge(
			[
				'ID',
				'NAME',
				'IBLOCK_ID',
				'TYPE',
				'PREVIEW_PICTURE',
				'DETAIL_PICTURE',
				'PREVIEW_TEXT',
				'PREVIEW_TEXT_TYPE',
				'DETAIL_TEXT',
				'DETAIL_TEXT_TYPE',
			],
			array_keys($additionalFilter)
		)));
		$navParams = false;
		if ($limit > 0)
		{
			$navParams = [
				'nTopCount' => $limit,
			];
		}

		$elementIterator = \CIBlockElement::GetList(
			['ID' => 'DESC'],
			array_merge($filter, $additionalFilter),
			false,
			$navParams,
			$selectFields
		);
		while ($element = $elementIterator->Fetch())
		{
			$element['ID'] = (int)$element['ID'];
			$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
			$element['TYPE'] = (int)$element['TYPE'];
			$element['IMAGE'] = null;
			$element['PRICE'] = null;
			$element['SKU_PROPERTIES'] = null;

			if (!empty($element['PREVIEW_PICTURE']))
			{
				$element['IMAGE'] = $this->getImageSource((int)$element['PREVIEW_PICTURE']);
			}

			if (empty($element['IMAGE']) && !empty($element['DETAIL_PICTURE']))
			{
				$element['IMAGE'] = $this->getImageSource((int)$element['DETAIL_PICTURE']);
			}

			if (!empty($element['PREVIEW_TEXT']) && $element['PREVIEW_TEXT_TYPE'] === 'html')
			{
				$element['PREVIEW_TEXT'] = HTMLToTxt($element['PREVIEW_TEXT']);
			}

			if (!empty($element['DETAIL_TEXT']) && $element['DETAIL_TEXT_TYPE'] === 'html')
			{
				$element['DETAIL_TEXT'] = HTMLToTxt($element['DETAIL_TEXT']);
			}

			$elements[$element['ID']] = $element;
		}

		return $elements;
	}

	private function loadOffers(array $products, IblockInfo $iblockInfo, array $additionalFilter = []): array
	{
		$productsWithOffers = $this->filterProductsWithOffers($products);
		if (empty($productsWithOffers))
		{
			return $products;
		}

		$skuPropertyId = 'PROPERTY_' . $iblockInfo->getSkuPropertyId();
		$offerFilter = [
			'IBLOCK_ID' => $iblockInfo->getSkuIblockId(),
			$skuPropertyId => array_keys($productsWithOffers),
		];
		if (!empty($additionalFilter))
		{
			$offerFilter = array_merge($offerFilter, $additionalFilter);
		}

		// first - load offers with coincidence in searchable content or by offer ids
		$offers = $this->loadElements(['filter' => $offerFilter]);
		$offers = array_column($offers, null, $skuPropertyId . '_VALUE');

		$productsStillWithoutOffers = array_diff_key($productsWithOffers, $offers);
		if (!empty($productsStillWithoutOffers))
		{
			// second - load any offer for product if have no coincidences in searchable content
			$additionalOffers = $this->loadElements([
				'filter' => [
					'IBLOCK_ID' => $iblockInfo->getSkuIblockId(),
					$skuPropertyId => array_keys($productsStillWithoutOffers),
				],
			]);
			$additionalOffers = array_column($additionalOffers, null, $skuPropertyId . '_VALUE');
			$offers = array_merge($offers, $additionalOffers);
		}

		if (!empty($offers))
		{
			$offers = array_column($offers, null, 'ID');
			$offers = $this->loadProperties($offers, $iblockInfo->getSkuIblockId(), $iblockInfo);
			$products = $this->matchProductOffers($products, $offers, $skuPropertyId . '_VALUE');
		}

		return $products;
	}

	private function loadPrices(array $elements): array
	{
		if (empty($elements))
		{
			return [];
		}

		$variationToProductMap = [];
		foreach ($elements as $id => $element)
		{
			$variationToProductMap[$element['ID']] = $id;
		}

		$priceTableResult = PriceTable::getList([
			'select' => ['PRICE', 'CURRENCY', 'PRODUCT_ID'],
			'filter' => [
				'PRODUCT_ID' => array_keys($variationToProductMap),
				'=CATALOG_GROUP_ID' => $this->getBasePriceId(),
				[
					'LOGIC' => 'OR',
					'<=QUANTITY_FROM' => 1,
					'=QUANTITY_FROM' => null,
				],
				[
					'LOGIC' => 'OR',
					'>=QUANTITY_TO' => 1,
					'=QUANTITY_TO' => null,
				],
			],
		]);

		while ($price = $priceTableResult->fetch())
		{
			$productId = $variationToProductMap[$price['PRODUCT_ID']];
			$formattedPrice = \CCurrencyLang::CurrencyFormat($price['PRICE'], $price['CURRENCY'], true);
			$elements[$productId]['PRICE'] = $formattedPrice;
		}

		return $elements;
	}

	private function matchProductOffers(array $products, array $offers, string $productLinkProperty): array
	{
		foreach ($offers as $offer)
		{
			$productId = $offer[$productLinkProperty] ?? null;

			if ($productId && isset($products[$productId]))
			{
				$fieldsToSafelyMerge = [
					'ID',
					'NAME',
					'PREVIEW_PICTURE',
					'DETAIL_PICTURE',
					'PREVIEW_TEXT',
					'DETAIL_TEXT',
					'SEARCH_PROPERTIES',
				];
				foreach ($fieldsToSafelyMerge as $field)
				{
					$products[$productId]['PARENT_' . $field] = $products[$productId][$field] ?? null;
					$products[$productId][$field] = $offer[$field] ?? null;
				}

				if (!empty($offer['IMAGE']))
				{
					$products[$productId]['IMAGE'] = $offer['IMAGE'];
				}

				if (!empty($offer['SKU_PROPERTIES']))
				{
					$products[$productId]['SKU_PROPERTIES'] = $offer['SKU_PROPERTIES'];
				}
			}
		}

		return $products;
	}

	private function filterProductsWithOffers(array $products): array
	{
		return array_filter($products, static function ($item) {
			return $item['TYPE'] === ProductTable::TYPE_SKU;
		});
	}

	private function loadProperties(array $elements, int $iblockId, IblockInfo $iblockInfo): array
	{
		if (empty($elements))
		{
			return [];
		}

		$propertyIds = [];

		$skuTreeProperties = null;
		if ($iblockInfo->getSkuIblockId() === $iblockId)
		{
			$skuTreeProperties = array_map('intval', PropertyCatalogFeature::getOfferTreePropertyCodes($iblockId) ?? []);
			if (!empty($skuTreeProperties))
			{
				$propertyIds = array_merge($propertyIds, $skuTreeProperties);
			}
		}

		$morePhotoId = $this->getMorePhotoPropertyId($iblockId);
		if ($morePhotoId)
		{
			$propertyIds[] = $morePhotoId;
		}

		$searchProperties = $this->getSearchPropertyIds($iblockId);
		if (!empty($searchProperties))
		{
			$propertyIds = array_merge($propertyIds, $searchProperties);
		}

		if (empty($propertyIds))
		{
			return $elements;
		}

		$elementPropertyValues = array_fill_keys(array_keys($elements), []);
		$offersFilter = [
			'IBLOCK_ID' => $iblockId,
			'ID' => array_column($elements, 'ID'),
		];
		$propertyFilter = [
			'ID' => array_unique($propertyIds),
		];
		\CIBlockElement::GetPropertyValuesArray($elementPropertyValues, $iblockId, $offersFilter, $propertyFilter);

		foreach ($elementPropertyValues as $elementId => $elementProperties)
		{
			if (empty($elementProperties))
			{
				continue;
			}

			$currentElement =& $elements[$elementId];

			foreach ($elementProperties as $property)
			{
				$propertyId = (int)$property['ID'];

				if ($propertyId === $morePhotoId)
				{
					if (empty($currentElement['IMAGE']))
					{
						$propertyValue = is_array($property['VALUE']) ? reset($property['VALUE']) : $property['VALUE'];
						if ((int)$propertyValue > 0)
						{
							$currentElement['IMAGE'] = $this->getImageSource((int)$propertyValue);
						}
					}
				}
				else
				{
					$isArray = is_array($property['~VALUE']);
					if (
						($isArray && !empty($property['~VALUE']))
						|| (!$isArray && (string)$property['~VALUE'] !== '')
					)
					{
						$propertyValue = $this->getPropertyDisplayValue($property);
						if ($propertyValue !== '')
						{
							if ($skuTreeProperties !== null && in_array($propertyId, $skuTreeProperties, true))
							{
								$currentElement['SKU_PROPERTIES'][$propertyId] = $propertyValue;
							}
							else
							{
								$currentElement['SEARCH_PROPERTIES'][$propertyId] = $propertyValue;
							}
						}
					}
				}
			}

			$currentElement['SKU_PROPERTIES'] = !empty($currentElement['SKU_PROPERTIES'])
				? implode(', ', $currentElement['SKU_PROPERTIES'])
				: null;

			$currentElement['SEARCH_PROPERTIES'] = !empty($currentElement['SEARCH_PROPERTIES'])
				? implode(', ', $currentElement['SEARCH_PROPERTIES'])
				: null;
		}

		unset($currentElement);

		return $elements;
	}

	private function getPropertyDisplayValue(array $property): string
	{
		if (!empty($property['USER_TYPE']))
		{
			$userType = \CIBlockProperty::GetUserType($property['USER_TYPE']);
			$searchMethod = $userType['GetSearchContent'] ?? null;

			if ($searchMethod && is_callable($searchMethod))
			{
				$value = $searchMethod($property, ['VALUE' => $property['~VALUE']], []);
			}
			else
			{
				$value = '';
			}
		}
		else
		{
			$value = $property['~VALUE'] ?? '';
		}

		if (is_array($value))
		{
			$value = implode(', ', $value);
		}

		$value = trim((string)$value);

		return $value;
	}

	private function getMorePhotoPropertyId(int $iblockId): ?int
	{
		$iterator = PropertyTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=CODE' => \CIBlockPropertyTools::CODE_MORE_PHOTO,
				'=ACTIVE' => 'Y',
			],
		]);
		if ($row = $iterator->fetch())
		{
			return (int)$row['ID'];
		}

		return null;
	}

	private function getSearchPropertyIds(int $iblockId): array
	{
		$properties = PropertyTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=IBLOCK_ID' => $iblockId,
				'=ACTIVE' => 'Y',
				'=SEARCHABLE' => 'Y',
				'@PROPERTY_TYPE' => [
					PropertyTable::TYPE_STRING,
					PropertyTable::TYPE_NUMBER,
					PropertyTable::TYPE_LIST,
				],
			],
			'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
		])->fetchAll()
		;

		return array_map('intval', array_column($properties, 'ID'));
	}

	private function shouldDisableCache(array $products): bool
	{
		if (count($products) >= self::PRODUCT_LIMIT)
		{
			return true;
		}

		foreach ($products as $product)
		{
			if (!empty($product['PARENT_ID']))
			{
				return true;
			}
		}

		return false;
	}
}