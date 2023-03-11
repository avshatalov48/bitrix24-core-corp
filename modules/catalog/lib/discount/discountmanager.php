<?php
namespace Bitrix\Catalog\Discount;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Main\Type\Collection,
	Bitrix\Catalog,
	Bitrix\Iblock,
	Bitrix\Sale;

Loc::loadMessages(__FILE__);

class DiscountManager
{
	protected static $discountCache = [];
	protected static $typeCache = [];
	protected static $editUrlTemplate = [];
	protected static $saleIncluded = null;
	protected static $preloadedPriceData = [];
	protected static $preloadedProductsData = [];
	protected static $productProperties = [];

	/**
	 * Return methods for prepare discount.
	 *
	 * @param Main\Event $event					Event data from discount manager.
	 * @return Main\EventResult
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function catalogDiscountManager(Main\Event $event): Main\EventResult
	{
		return new Main\EventResult(
			Main\EventResult::SUCCESS,
			[
				'prepareData' => [__CLASS__, 'prepareData'],
				'getEditUrl' => [__CLASS__, 'getEditUrl'],
				'calculateApplyCoupons' => [__CLASS__, 'calculateApplyCoupons'],
				'roundBasket' => [__CLASS__, 'roundBasket']
			],
			'catalog'
		);
	}

	/**
	 * Prepare discount before saving.
	 *
	 * @param array $discount				Discount data.
	 * @param array $params					Params.
	 * @return array|bool
	 */
	public static function prepareData(array $discount, $params = [])
	{
		if (empty($discount) || empty($discount['ID']))
			return false;

		$discountId = (int)$discount['ID'];
		if ($discountId <= 0)
			return false;
		if (!isset(self::$discountCache[$discountId]))
		{
			self::$discountCache[$discountId] = false;

			$loadData = self::loadFromDatabase($discountId, $discount);
			if (!empty($loadData))
			{
				$loadData['LAST_LEVEL_DISCOUNT'] = 'N';
				if ($loadData['CURRENCY'] != $params['CURRENCY'])
					Catalog\DiscountTable::convertCurrency($loadData, $params['CURRENCY']);
				self::createSaleAction($loadData, $params);
				$loadData['EDIT_PAGE_URL'] = self::getEditUrl([
					'ID' => $discountId, 'TYPE' => $loadData['TYPE']
				]);
				self::$discountCache[$discountId] = $loadData;
			}
		}
		$result = self::$discountCache[$discountId];
		if (empty($result))
			return $result;
		if ($result['USE_COUPONS'] == 'Y')
		{
			if (isset($discount['COUPON']))
				$result['COUPON'] = $discount['COUPON'];
		}

		return $result;
	}

	/**
	 * Return url for edit discount.
	 *
	 * @param array $discount			Discount data.
	 * @return string
	 */
	public static function getEditUrl(array $discount): string
	{
		if (empty(self::$editUrlTemplate))
		{
			self::$editUrlTemplate = [
				Catalog\DiscountTable::TYPE_DISCOUNT => '/bitrix/admin/cat_discount_edit.php?lang='.LANGUAGE_ID.'&ID=',
				Catalog\DiscountTable::TYPE_DISCOUNT_SAVE => '/bitrix/admin/cat_discsave_edit.php?lang='.LANGUAGE_ID.'&ID='
			];
		}
		$result = '';
		if (empty($discount['ID']) || (int)$discount['ID'] <= 0)
			return $result;

		$id = (int)$discount['ID'];
		$type = -1;
		if (isset($discount['TYPE']))
			$type = (int)$discount['TYPE'];

		if ($type != Catalog\DiscountTable::TYPE_DISCOUNT && $type != Catalog\DiscountTable::TYPE_DISCOUNT_SAVE)
		{
			if (isset(self::$typeCache[$id]))
			{
				$type = self::$typeCache[$id];
			}
			else
			{
				$discountIterator = Catalog\DiscountTable::getList([
					'select' => ['ID', 'TYPE'],
					'filter' => ['=ID' => $id]
				]);
				$data = $discountIterator->fetch();
				if (!empty($data))
				{
					$type = (int)$data['TYPE'];
					self::$typeCache[$id] = $type;
				}
				unset($data, $discountIterator);
			}
		}
		if (isset(self::$editUrlTemplate[$type]))
			$result = self::$editUrlTemplate[$type].$id;
		unset($type, $id);
		return $result;
	}

	/**
	 * Check apply coupons.
	 *
	 * @param array $couponsList		Coupons.
	 * @param array $basket				Basket data.
	 * @param array $params				Calculate params.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function calculateApplyCoupons(array $couponsList, array $basket, array $params): array
	{
		$result = [];

		if (empty($couponsList))
			return $result;
		if (empty($basket))
			return $result;
		$filteredBasket = array_filter($basket, '\Bitrix\Catalog\Discount\DiscountManager::basketFilter');
		if (empty($filteredBasket))
			return $result;
		$filteredBasket = array_filter($filteredBasket, '\Bitrix\Catalog\Discount\DiscountManager::lastDiscountFilter');
		if (empty($filteredBasket))
			return $result;

		$filteredCoupons = [];
		foreach ($couponsList as $coupon)
		{
			if (!isset($coupon['COUPON']) || $coupon['COUPON'] == '')
				continue;
			if (!isset($coupon['DISCOUNT_ID']) || (int)$coupon['DISCOUNT_ID'] <= 0)
				continue;
			$filteredCoupons[] = $coupon['COUPON'];
		}
		unset($coupon);
		if (empty($filteredCoupons))
			return $result;

		$discountIds = [];
		$discountCoupons = [];
		$oneRowCoupons = [];
		$couponsIterator = Catalog\DiscountCouponTable::getList([
			'select' => ['ID', 'COUPON', 'DISCOUNT_ID', 'TYPE'],
			'filter' => ['@COUPON' => $filteredCoupons, 'ACTIVE' => 'Y']
		]);
		while ($coupon = $couponsIterator->fetch())
		{
			$discountIds[$coupon['DISCOUNT_ID']] = true;
			$discountCoupons[$coupon['COUPON']] = $coupon['COUPON'];
			if ($coupon['TYPE'] == Catalog\DiscountCouponTable::TYPE_ONE_ROW)
				$oneRowCoupons[$coupon['COUPON']] = true;
		}
		unset($coupon, $couponsIterator);
		if (empty($discountCoupons))
			return $result;

		$userId = (isset($params['USER_ID']) ? (int)$params['USER_ID'] : 0);
		if ($userId <= 0)
			return $result;
		$userGroups = Main\UserTable::getUserGroupIds($userId);
		$userGroups[] = -1;

		$iblockList = [];
		$product2Iblock = [];
		$itemIds = [];
		foreach ($filteredBasket as $basketItem)
		{
			$productId = (int)$basketItem['PRODUCT_ID'];
			$itemIds[$productId] = $productId;
		}
		unset($basketItem);

		$itemIterator = Iblock\ElementTable::getList([
			'select' => ['ID', 'IBLOCK_ID'],
			'filter' => ['@ID' => $itemIds, '=ACTIVE' => 'Y']
		]);
		while ($item = $itemIterator->fetch())
		{
			$id = (int)$item['ID'];
			$iblockId = (int)$item['IBLOCK_ID'];
			if (!isset($iblockList[$iblockId]))
				$iblockList[$iblockId] = [];
			$iblockList[$iblockId][$id] = $id;
			$product2Iblock[$id] = $iblockId;
			unset($iblockId, $id);
		}
		unset($item, $itemIterator);
		unset($itemIds);

		if (empty($iblockList))
			return $result;

		foreach($iblockList as $iblockId => $elements)
		{
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			\CCatalogDiscount::setProductSectionsCache($elements);
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			\CCatalogDiscount::setDiscountProductCache($elements, ['IBLOCK_ID' => $iblockId, 'GET_BY_ID' => 'Y']);
		}
		unset($iblockId, $elements);

		$discountPercentMode = \CCatalogDiscount::getUseBasePrice();
		if (isset($params['USE_BASE_PRICE']))
			\CCatalogDiscount::setUseBasePrice($params['USE_BASE_PRICE'] == 'Y');

		Main\Type\Collection::sortByColumn($filteredBasket, ['PRICE' => SORT_DESC], '', null, true);
		foreach ($filteredBasket as $basketCode => $basketItem)
		{
			$productId = (int)$basketItem['PRODUCT_ID'];
			if (!isset($product2Iblock[$productId]))
				continue;
			if (empty($discountCoupons))
				break;

			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			$discountList = \CCatalogDiscount::getDiscount(
				$productId,
				$product2Iblock[$productId],
				[-1],
				$userGroups,
				'N',
				$params['SITE_ID'],
				$discountCoupons
			);

			if (empty($discountList))
				continue;

			$itemDiscounts = [];
			foreach ($discountList as $discount)
			{
				if (!isset($discountIds[$discount['ID']]))
					continue;
				$itemDiscounts[] = $discount;
			}
			unset($discount, $discountList);
			if (empty($itemDiscounts))
				continue;

			$itemsDiscountResult = \CCatalogDiscount::applyDiscountList($basketItem['PRICE'], $basketItem['CURRENCY'], $itemDiscounts);
			unset($itemDiscounts);
			if (!empty($itemsDiscountResult['DISCOUNT_LIST']))
			{
				$result[$basketCode] = [];
				foreach ($itemsDiscountResult['DISCOUNT_LIST'] as $discount)
				{
					$result[$basketCode][] = \CCatalogDiscount::getDiscountDescription($discount);
					if (!empty($discount['COUPON']) && isset($oneRowCoupons[$discount['COUPON']]))
						unset($discountCoupons[$discount['COUPON']]);
				}
				unset($discount);
			}
			unset($itemsDiscountResult);
		}
		unset($basketCode, $basketItem, $basketItem);

		\CCatalogDiscount::setUseBasePrice($discountPercentMode);
		unset($discountPercentMode);

		return $result;
	}

	/**
	 * Round basket item price.
	 * @deprecated
	 * @see \Bitrix\Catalog\Discount\DiscountManager::roundBasket
	 *
	 * @param array $basketItem		Basket item data.
	 * @param array $roundData		Round rule.
	 * @return array
	 */
	public static function roundPrice(array $basketItem, array $roundData = []): array
	{
		if (empty($basketItem))
			return [];

		$result = self::roundBasket([0 => $basketItem], [0 => $roundData], []);
		return (!empty($result[0]) ? $result[0] : []);
	}

	/**
	 * Round basket prices.
	 *
	 * @param array $basket				Basket.
	 * @param array $basketRoundData	Round rules.
	 * @param array $order				Order fields (without basket, can be absent).
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function roundBasket(array $basket, array $basketRoundData = [], array $order = []): array
	{
		if (empty($basket))
			return [];

		$result = [];
		$basket = array_filter($basket, '\Bitrix\Catalog\Discount\DiscountManager::basketFilter');
		if (!empty($basket))
		{
			$priceTypes = [];
			$loadPriceId = [];
			$loadBasketCodes = [];
			foreach ($basket as $basketCode => $basketItem)
			{
				if (!empty($basketRoundData[$basketCode]))
					continue;
				$priceTypeId = 0;
				if (isset($basketItem['PRICE_TYPE_ID']))
					$priceTypeId = (int)$basketItem['PRICE_TYPE_ID'];
				if ($priceTypeId <= 0 && isset($basketItem['CATALOG_GROUP_ID']))
					$priceTypeId = (int)$basketItem['CATALOG_GROUP_ID'];
				if ($priceTypeId <= 0 && isset($basketItem['PRODUCT_PRICE_ID']))
				{
					$priceId = (int)$basketItem['PRODUCT_PRICE_ID'];
					if ($priceId > 0)
					{
						$cachedPrice = self::getPriceDataByPriceId($priceId);
						if (!empty($cachedPrice))
							$priceTypeId = (int)$cachedPrice['CATALOG_GROUP_ID'];
						if ($priceTypeId <= 0)
						{
							$loadPriceId[] = $priceId;
							$loadBasketCodes[$priceId] = $basketCode;
						}
					}
				}

				$basket[$basketCode]['PRICE_TYPE_ID'] = $priceTypeId;
				if ($priceTypeId > 0)
					$priceTypes[$priceTypeId] = $priceTypeId;

			}
			unset($priceId, $priceTypeId, $basketCode, $basketItem);

			if (!empty($loadPriceId))
			{
				sort($loadPriceId);
				foreach (array_chunk($loadPriceId, 500) as $pageIds)
				{
					$iterator = Catalog\PriceTable::getList([
						'select' => ['ID', 'CATALOG_GROUP_ID'],
						'filter' => ['@ID' => $pageIds]
					]);
					while ($row = $iterator->fetch())
					{
						$id = (int)$row['ID'];
						$priceTypeId = (int)$row['CATALOG_GROUP_ID'];
						if (!isset($loadBasketCodes[$id]))
							continue;
						$basket[$loadBasketCodes[$id]]['PRICE_TYPE_ID'] = $priceTypeId;
						$priceTypes[$priceTypeId] = $priceTypeId;
					}
					unset($priceTypeId, $id, $row, $iterator);
				}
			}
			unset($loadBasketCodes, $loadPriceId);

			if (!empty($priceTypes))
				Catalog\Product\Price::loadRoundRules($priceTypes);
			unset($priceTypes);

			foreach ($basket as $basketCode => $basketItem)
			{
				if (!empty($basketRoundData[$basketCode]))
				{
					$roundData = $basketRoundData[$basketCode];
				}
				else
				{
					$roundData = Catalog\Product\Price::searchRoundRule(
						$basketItem['PRICE_TYPE_ID'],
						$basketItem['PRICE'],
						$basketItem['CURRENCY']
					);
				}

				if (empty($roundData))
				{
					continue;
				}

				$result[$basketCode] = self::getRoundResult($basketItem, $roundData);
			}
			unset($roundData, $basketCode, $basketItem, $basketRoundData);
		}
		unset($basket);

		return $result;
	}

	/**
	 * Apply catalog discount by basket item.
	 *
	 * @param array &$product			Product data.
	 * @param array $discount			Discount data.
	 * @return void
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public static function applyDiscount(&$product, $discount): void
	{
		if (empty($product) || !is_array($product))
			return;
		if (empty($discount) || empty($discount['TYPE']))
			return;
		if (isset($discount['CURRENCY']) && $discount['CURRENCY'] != $product['CURRENCY'])
			return;
		if (!isset($product['DISCOUNT_PRICE']))
			$product['DISCOUNT_PRICE'] = 0;
		$getPercentFromBasePrice = (isset($discount['USE_BASE_PRICE']) && $discount['USE_BASE_PRICE'] == 'Y');
		$basePrice = (float)(
			isset($product['BASE_PRICE'])
			? $product['BASE_PRICE']
			: $product['PRICE'] + $product['DISCOUNT_PRICE']
		);

		switch ($discount['TYPE'])
		{
			case Catalog\DiscountTable::VALUE_TYPE_PERCENT:
				$discount['VALUE'] = -$discount['VALUE'];
				$discountValue = self::roundValue(
					((
						$getPercentFromBasePrice
							? $basePrice
							: $product['PRICE']
						)*$discount['VALUE'])/100,
					$product['CURRENCY']
				);
				if (isset($discount['MAX_VALUE']) && $discount['MAX_VALUE'] > 0)
				{
					if ($discountValue + $discount['MAX_VALUE'] <= 0)
						$discountValue = -$discount['MAX_VALUE'];
				}
				$product['PRICE'] += $discountValue;
				$product['DISCOUNT_PRICE'] -= $discountValue;
				if (!empty($product['DISCOUNT_RESULT']))
				{
					$product['DISCOUNT_RESULT']['BASKET'][0]['RESULT_VALUE'] = (string)abs($discountValue);
					$product['DISCOUNT_RESULT']['BASKET'][0]['RESULT_UNIT'] = $product['CURRENCY'];
				}
				unset($discountValue);
				break;
			case Catalog\DiscountTable::VALUE_TYPE_FIX:
				$discount['VALUE'] = self::roundValue($discount['VALUE'], $product['CURRENCY']);
				$product['PRICE'] -= $discount['VALUE'];
				$product['DISCOUNT_PRICE'] += $discount['VALUE'];
				break;
			case Catalog\DiscountTable::VALUE_TYPE_SALE:
				$discount['VALUE'] = self::roundValue($discount['VALUE'], $product['CURRENCY']);
				$product['DISCOUNT_PRICE'] += ($product['PRICE'] - $discount['VALUE']);
				$product['PRICE'] = $discount['VALUE'];
				break;
		}
	}

	/**
	 * Returns price for product which has catalog group.
	 *
	 * @param int $productId Product id.
	 * @param int $catalogGroupId Catalog group.
	 * @return null|array
	 */
	public static function getPriceDataByProductId(int $productId, int $catalogGroupId): ?array
	{
		if (!isset(self::$preloadedPriceData[$productId.'-'.$catalogGroupId]))
		{
			self::$preloadedPriceData[$productId.'-'.$catalogGroupId] = null;
			self::preloadPriceData([$productId], [$catalogGroupId]);
		}
		return self::$preloadedPriceData[$productId.'-'.$catalogGroupId];
	}

	/**
	 * Set property values cache for product.
	 *
	 * @param int $productId		Product id.
	 * @param array $props			Property values.
	 * @return void
	 * @noinspection PhpMissingParamTypeInspection
	 */
	public static function setProductPropertiesCache($productId, $props)
	{
		if (!is_array($props))
			return;

		self::$productProperties[$productId] = $props;
	}

	/**
	 * Clear property values cache.
	 *
	 * @return void
	 */
	public static function clearProductPropertiesCache()
	{
		self::$productProperties = [];
	}

	/**
	 * Clear products cache.
	 *
	 * @return void
	 */
	public static function clearProductsCache()
	{
		self::$preloadedProductsData = [];
	}

	/**
	 * Clear product prices cache.
	 *
	 * @return void
	 */
	public static function clearProductPricesCache()
	{
		self::$preloadedPriceData = [];
	}

	/**
	 * Preloads prices for products with catalog groups.
	 *
	 * @param array $productIds		List of product ids.
	 * @param array $catalogGroups	Catalog groups.
	 * @return void
	 */
	public static function preloadPriceData(array $productIds, array $catalogGroups)
	{
		if (empty($productIds) || empty($catalogGroups))
			return;
		Collection::normalizeArrayValuesByInt($productIds);
		if (empty($productIds))
			return;
		Collection::normalizeArrayValuesByInt($catalogGroups);
		if (empty($catalogGroups))
			return;

		$productIds = self::extendProductIdsToOffer($productIds);

		foreach($productIds as $i => $productId)
		{
			if(isset(self::$preloadedPriceData[$productId]))
			{
				unset($productIds[$i]);
			}
		}

		if(empty($productIds))
		{
			return;
		}

		$dbPrice = Catalog\PriceTable::getList([
			'select' => ['*'],
			'filter' => ['@PRODUCT_ID' => $productIds, '@CATALOG_GROUP_ID' => $catalogGroups]
		]);
		while($priceRow = $dbPrice->fetch())
		{
			self::$preloadedPriceData[$priceRow['PRODUCT_ID'].'-'.$priceRow['CATALOG_GROUP_ID']] = $priceRow;
		}
	}

	private static function fillByPreloadedPrices(array &$productData, array $priceList)
	{
		foreach ($productData as $productId => $product)
		{
			foreach (self::$preloadedPriceData as $priceData)
			{
				if ($priceData['PRODUCT_ID'] != $productId)
				{
					continue;
				}

				if(!in_array($priceData['ID'], $priceList))
				{
					continue;
				}

				$productData[$productId]['CATALOG_GROUP_ID'] = $priceData['CATALOG_GROUP_ID'];
			}
		}
	}

	/**
	 * Load product data for calculate discounts.
	 *
	 * @param array $productIds		Product id list.
	 * @param array $userGroups		User group list.
	 * @return void
	 */
	public static function preloadProductDataToExtendOrder(array $productIds, array $userGroups)
	{
		if (empty($productIds) || empty($userGroups))
			return;
		Collection::normalizeArrayValuesByInt($productIds, true);
		if (empty($productIds))
			return;
		Collection::normalizeArrayValuesByInt($userGroups, true);
		if (empty($userGroups))
			return;

		if(self::$saleIncluded === null)
			self::$saleIncluded = Loader::includeModule('sale');

		if(!self::$saleIncluded)
			return;

		$discountCache = Sale\Discount\RuntimeCache\DiscountCache::getInstance();

		$discountIds = $discountCache->getDiscountIds($userGroups);
		if(!$discountIds)
		{
			return;
		}

		Collection::normalizeArrayValuesByInt($discountIds, true);

		$entityList = $discountCache->getDiscountEntities($discountIds);
		if(!$entityList || empty($entityList['catalog']))
		{
			return;
		}

		$entityData = self::prepareEntity($entityList);
		if(!$entityData)
		{
			return;
		}

		$productIds = self::extendProductIdsToOffer($productIds);

		$iblockData = self::getProductIblocks($productIds);
		self::fillProductPropertyList($entityData, $iblockData);

		$productData = array_fill_keys($productIds, []);
		if(empty($iblockData['iblockElement']))
		{
			return;
		}

		self::getProductData($productData, $entityData, $iblockData);

		$cacheKeyForEntityList = self::getCacheKeyForEntityList($entityList);
		if(!isset(self::$preloadedProductsData[$cacheKeyForEntityList]))
		{
			self::$preloadedProductsData[$cacheKeyForEntityList] = [];
		}

		foreach($productData as $productId => $data)
		{
			self::$preloadedProductsData[$cacheKeyForEntityList][$productId] = $data;
		}
	}

	/**
	 * Extend basket data.
	 *
	 * @param Main\Event $event			Event.
	 * @return Main\EventResult
	 */
	public static function extendOrderData(Main\Event $event): Main\EventResult
	{
		$process = true;
		$resultData = [];
		$orderData = $event->getParameter('ORDER');
		$entityList = $event->getParameter('ENTITY');
		$cacheKeyForEntityList = self::getCacheKeyForEntityList($entityList);

		if (empty($orderData) || !is_array($orderData))
		{
			$process = false;
		}
		else
		{
			if (!isset($orderData['BASKET_ITEMS']) || !is_array($orderData['BASKET_ITEMS']))
				$process = false;
		}

		$entityData = false;
		$iblockData = false;
		if (
			$process
			&& !empty($orderData['BASKET_ITEMS'])
		)
		{
			$entityData = self::prepareEntity($entityList);
			if (empty($entityData))
				$process = false;
		}
		if ($process)
		{
			$productMap = [];
			$productList = [];
			$productData = [];
			$priceList = [];

			$basket = array_filter($orderData['BASKET_ITEMS'], '\Bitrix\Catalog\Discount\DiscountManager::basketFilter');
			if (!empty($basket))
			{
				foreach ($basket as $basketCode => $basketItem)
				{
					$basketItem['PRODUCT_ID'] = (int)$basketItem['PRODUCT_ID'];
					$productList[] = $basketItem['PRODUCT_ID'];
					if (!isset($productMap[$basketItem['PRODUCT_ID']]))
					{
						$productMap[$basketItem['PRODUCT_ID']] = [];
					}
					$productMap[$basketItem['PRODUCT_ID']][] = &$basket[$basketCode];

					if (isset($basketItem['PRODUCT_PRICE_ID']))
					{
						$priceList[] = $basketItem['PRODUCT_PRICE_ID'];
					}
				}
				unset($basketItem, $basketCode);

				if(isset(self::$preloadedProductsData[$cacheKeyForEntityList]))
				{
					$preloadedProductIds = array_keys(self::$preloadedProductsData[$cacheKeyForEntityList]);
					$loadedProductIds = array_intersect($productList, $preloadedProductIds);

					$productList = array_diff($productList, $preloadedProductIds);
				}

				$productData = array_fill_keys($productList, []);

				if($productData)
				{
					$iblockData = self::getProductIblocks($productList);
					self::fillProductPropertyList($entityData, $iblockData);
					self::fillProductPriceList($entityData, $priceList);
				}
			}

			if (!empty($iblockData['iblockElement']))
			{
				self::getProductData($productData, $entityData, $iblockData);
			}

			if(!empty($loadedProductIds))
			{
				foreach($loadedProductIds as $loadedProductId)
				{
					$productData[$loadedProductId] = self::$preloadedProductsData[$cacheKeyForEntityList][$loadedProductId];
				}

				if(!empty($entityData['priceFields']))
				{
					self::fillByPreloadedPrices($productData, $priceList);
				}
			}

			if($productData)
			{
				foreach ($productData as $product => $data)
				{
					if (empty($productMap[$product]))
						continue;
					foreach ($productMap[$product] as &$basketItem)
						$basketItem['CATALOG'] = $data;
					unset($basketItem);
				}
				unset($product, $data);

				$resultData['BASKET_ITEMS'] = $basket;
			}
			unset($basket, $productData, $productMap, $productList);
		}

		if ($process)
			$result = new Main\EventResult(Main\EventResult::SUCCESS, $resultData, 'catalog');
		else
			$result = new Main\EventResult(Main\EventResult::ERROR, null, 'catalog');
		unset($process, $resultData);

		return $result;
	}

	protected static function getCacheKeyForEntityList(array $entityList): string
	{
		return md5(serialize($entityList));
	}

	protected static function extendProductIdsToOffer(array $productIds): array
	{
		static $cache = [];

		Collection::normalizeArrayValuesByInt($productIds);
		if (empty($productIds))
			return [];
		$key = md5(implode('|', $productIds));

		if(!isset($cache[$key]))
		{
			$extendedList = array_combine($productIds, $productIds);
			foreach(\CCatalogSku::getOffersList($productIds) as $mainProduct)
			{
				foreach(array_keys($mainProduct) as $offerId)
				{
					if(!isset($extendedList[$offerId]))
					{
						$extendedList[$offerId] = $offerId;
					}
				}
			}

			$cache[$key] = $extendedList;
		}

		return $cache[$key];
	}

	/**
	 * Filter for catalog basket items.
	 *
	 * @param array $basketItem Basket item data.
	 * @return bool
	 */
	protected static function basketFilter(array $basketItem): bool
	{
		return (
			(
				(isset($basketItem['MODULE']) && $basketItem['MODULE'] == 'catalog')
				|| (isset($basketItem['MODULE_ID']) && $basketItem['MODULE_ID'] == 'catalog')
			)
			&& (isset($basketItem['PRODUCT_ID']) && (int)$basketItem['PRODUCT_ID'] > 0)
		);
	}

	/**
	 * Filter for stop discount calculate for basket item.
	 *
	 * @param array $basketItem Basket item data.
	 * @return bool
	 */
	protected static function lastDiscountFilter(array $basketItem): bool
	{
		return (
			!isset($basketItem['LAST_DISCOUNT'])
			|| $basketItem['LAST_DISCOUNT'] != 'Y'
		);
	}

	/**
	 * Load discount data from db.
	 * @param int $id Discount id.
	 * @param array $discount Exist discount data.
	 * @return bool|array
	 */
	protected static function loadFromDatabase(int $id, array $discount)
	{
		$select = [];
		if (!isset($discount['NAME']))
			$select['NAME'] = true;
		if (empty($discount['CONDITIONS']))
			$select['CONDITIONS_LIST'] = true;
		if (empty($discount['UNPACK']))
			$select['UNPACK'] = true;
		if (empty($discount['USE_COUPONS']))
			$discount['USE_COUPONS'] = (!empty($discount['COUPON']) ? 'Y' : 'N');
		if (!isset($discount['SORT']))
			$select['SORT'] = true;
		if (!isset($discount['PRIORITY']))
			$select['PRIORITY'] = true;
		if (!isset($discount['LAST_DISCOUNT']))
			$select['LAST_DISCOUNT'] = true;

		if (
			!isset($discount['TYPE'])
			|| ($discount['TYPE'] != Catalog\DiscountTable::TYPE_DISCOUNT && $discount['TYPE'] != Catalog\DiscountTable::TYPE_DISCOUNT_SAVE)
		)
			$select['TYPE'] = true;
		if (!isset($discount['VALUE_TYPE']))
		{
			$select['VALUE_TYPE'] = true;
			$select['VALUE'] = true;
			$select['MAX_DISCOUNT'] = true;
			$select['CURRENCY'] = true;
		}
		else
		{
			if (!isset($discount['VALUE']))
				$select['VALUE'] = true;
			if (!isset($discount['CURRENCY']))
				$select['CURRENCY'] = true;
			if ($discount['VALUE_TYPE'] == Catalog\DiscountTable::VALUE_TYPE_PERCENT && !isset($discount['MAX_VALUE']))
				$select['MAX_DISCOUNT'] = true;
		}
		$selectKeys = array_keys($select);

		if (!empty($select))
		{
			$discountIterator = Catalog\DiscountTable::getList([
				'select' => $selectKeys,
				'filter' => ['=ID' => $id]
			]);
			$loadData = $discountIterator->fetch();
			if (empty($loadData))
				return false;
			$discount = array_merge($loadData, $discount);
			if (isset($discount['CONDITIONS_LIST']))
			{
				$discount['CONDITIONS'] = $discount['CONDITIONS_LIST'];
				unset($discount['CONDITIONS_LIST']);
			}
			if (isset($discount['MAX_DISCOUNT']))
			{
				$discount['MAX_VALUE'] = $discount['MAX_DISCOUNT'];
				unset($discount['MAX_DISCOUNT']);
			}
			unset($loadData, $discountIterator);
		}
		$discount['DISCOUNT_ID'] = $id;
		if (empty($discount['MODULE_ID']))
			$discount['MODULE_ID'] = 'catalog';
		if (array_key_exists('HANDLERS', $discount))
		{
			if (!empty($discount['HANDLERS']['MODULES']) && empty($discount['MODULES']))
				$discount['MODULES'] = $discount['HANDLERS']['MODULES'];
			unset($discount['HANDLERS']);
		}
		if (empty($discount['MODULES']))
		{
			$discount['MODULES'] = [];

			$conn = Main\Application::getConnection();
			$helper = $conn->getSqlHelper();
			/** @noinspection SqlResolve */
			$moduleIterator = $conn->query(
				'select MODULE_ID from '.$helper->quote('b_catalog_discount_module').' where '.$helper->quote('DISCOUNT_ID').' = '.$id
			);
			while ($module = $moduleIterator->fetch())
				$discount['MODULES'][] = $module['MODULE_ID'];
			unset($module, $moduleIterator, $helper, $conn);
			if (!in_array('catalog', $discount['MODULES']))
				$discount['MODULES'][] = 'catalog';
		}
		self::$typeCache[$id] = $discount['TYPE'];

		return $discount;
	}

	/**
	 * Prepare entity to iblock and catalog fields.
	 *
	 * @param array $entityList			Entity list.
	 * @return array|bool
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected static function prepareEntity($entityList)
	{
		$result = [
			'iblockFields' => [],
			'sections' => false,
			'iblockProperties' => [],
			'iblockPropertiesMap' => [],
			'catalogFields' => [],
			'priceFields' => []
		];

		if (!is_array($entityList))
			return false;

		if (empty($entityList['catalog']))
			return $result;

		if (!empty($entityList['catalog']))
		{
			if (!empty($entityList['catalog']['ELEMENT']) && is_array($entityList['catalog']['ELEMENT']))
			{
				foreach ($entityList['catalog']['ELEMENT'] as $entity)
				{
					if ($entity['FIELD_ENTITY'] == 'SECTION_ID')
					{
						$result['sections'] = true;
						continue;
					}
					$result['iblockFields'][$entity['FIELD_TABLE']] = $entity['FIELD_ENTITY'];
				}
				unset($entity);
			}
			if (!empty($entityList['catalog']['ELEMENT_PROPERTY']) && is_array($entityList['catalog']['ELEMENT_PROPERTY']))
			{
				foreach ($entityList['catalog']['ELEMENT_PROPERTY'] as $entity)
				{
					$propertyData = explode(':', $entity['FIELD_TABLE']);
					if (!is_array($propertyData) || count($propertyData) != 2)
						continue;
					$iblock = (int)$propertyData[0];
					$property = (int)$propertyData[1];
					unset($propertyData);
					if (!isset($result['iblockProperties'][$iblock]))
						$result['iblockProperties'][$iblock] = [];
					$result['iblockProperties'][$iblock][] = $property;
					if (!isset($result['iblockPropertiesMap'][$iblock]))
						$result['iblockPropertiesMap'][$iblock] = [];
					$result['iblockPropertiesMap'][$iblock][$property] = $entity['FIELD_ENTITY'];
				}
				unset($iblock, $property, $entity);
			}

			if (!empty($entityList['catalog']['PRODUCT']) && is_array($entityList['catalog']['PRODUCT']))
			{
				foreach ($entityList['catalog']['PRODUCT'] as $entity)
					$result['catalogFields'][$entity['FIELD_TABLE']] = $entity['FIELD_ENTITY'];
				unset($entity);
			}

			if (!empty($entityList['catalog']['PRICE']) && is_array($entityList['catalog']['PRICE']))
			{
				foreach ($entityList['catalog']['PRICE'] as $entity)
					$result['priceFields'][$entity['FIELD_TABLE']] = $entity['FIELD_ENTITY'];
				unset($entity);
			}
		}

		return $result;
	}

	/**
	 * Returns product separate by iblocks.
	 *
	 * @param array $productList Product id list.
	 * @return array
	 */
	protected static function getProductIblocks(array $productList): array
	{
		$result = [
			'iblockElement' => [],
			'iblockList' => [],
			'skuIblockList' => []
		];

		if (empty($productList))
			return $result;

		$elementIterator = Iblock\ElementTable::getList([
			'select' => ['ID', 'IBLOCK_ID'],
			'filter' => ['@ID' => $productList]
		]);
		while ($element = $elementIterator->fetch())
		{
			$element['ID'] = (int)$element['ID'];
			$element['IBLOCK_ID'] = (int)$element['IBLOCK_ID'];
			if (!isset($result['iblockElement'][$element['IBLOCK_ID']]))
				$result['iblockElement'][$element['IBLOCK_ID']] = [];
			$result['iblockElement'][$element['IBLOCK_ID']][] = $element['ID'];
		}
		unset($element, $elementIterator);
		if (!empty($result['iblockElement']))
		{
			$result['iblockList'] = array_keys($result['iblockElement']);

			$skuIterator = Catalog\CatalogIblockTable::getList([
				'select' => ['IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID'],
				'filter' => ['@IBLOCK_ID' => $result['iblockList'], '!=PRODUCT_IBLOCK_ID' => 0]
			]);
			while ($sku = $skuIterator->fetch())
			{
				$sku['IBLOCK_ID'] = (int)$sku['IBLOCK_ID'];
				$sku['PRODUCT_IBLOCK_ID'] = (int)$sku['PRODUCT_IBLOCK_ID'];
				$sku['SKU_PROPERTY_ID'] = (int)$sku['SKU_PROPERTY_ID'];
				$result['skuIblockList'][$sku['IBLOCK_ID']] = $sku;
			}
			unset($sku, $skuIterator);
		}

		return $result;
	}

	/**
	 * Create property list for discounts.
	 *
	 * @param array &$entityData Entity data.
	 * @param array $iblockData Iblock data.
	 * @return void
	 */
	protected static function fillProductPropertyList(array &$entityData, array $iblockData): void
	{
		$entityData['needProperties'] = [];
		if (!empty($entityData['iblockProperties']) && !empty($iblockData['iblockList']))
		{
			foreach ($iblockData['iblockList'] as $iblock)
			{
				if (!empty($entityData['iblockProperties'][$iblock]))
					$entityData['needProperties'][$iblock] = $entityData['iblockProperties'][$iblock];
			}
			unset($iblock);
		}
		if (!empty($iblockData['skuIblockList']))
		{
			foreach ($iblockData['skuIblockList'] as $skuData)
			{
				if (!isset($entityData['needProperties'][$skuData['IBLOCK_ID']]))
					$entityData['needProperties'][$skuData['IBLOCK_ID']] = [];
				$entityData['needProperties'][$skuData['IBLOCK_ID']][] = $skuData['SKU_PROPERTY_ID'];
				$entityData['iblockPropertiesMap'][$skuData['IBLOCK_ID']][$skuData['SKU_PROPERTY_ID']] = 'PARENT_ID';
				if (!empty($entityData['iblockProperties'][$skuData['PRODUCT_IBLOCK_ID']]))
					$entityData['needProperties'][$skuData['PRODUCT_IBLOCK_ID']] = $entityData['iblockProperties'][$skuData['PRODUCT_IBLOCK_ID']];
			}
			unset($skuData);
		}
	}

	/**
	 * Convert properties values to discount format.
	 *
	 * @param array &$productData			Product data.
	 * @param array $propertyValues			Product properties.
	 * @param array $entityData				Entity data.
	 * @param array $iblockData				Iblock data.
	 * @return void
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected static function convertProperties(&$productData, $propertyValues, $entityData, $iblockData): void
	{
		if (empty($productData) || !is_array($productData))
			return;
		if (empty($propertyValues) || !is_array($propertyValues))
			return;
		if (empty($entityData) || !is_array($entityData))
			return;
		if (empty($iblockData) || !is_array($iblockData))
			return;

		if (empty($entityData['needProperties']) || !is_array($entityData['needProperties']))
			return;
		$propertyIblocks = array_keys($entityData['needProperties']);
		foreach ($propertyIblocks as $iblock)
		{
			if (empty($iblockData['iblockElement'][$iblock]))
				continue;
			$propertyMap = $entityData['iblockPropertiesMap'][$iblock];
			foreach ($iblockData['iblockElement'][$iblock] as $element)
			{
				if (empty($propertyValues[$element]))
					continue;
				foreach ($propertyValues[$element] as $property)
				{
					if (empty($property) || empty($property['ID']))
						continue;
					if ($property['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_FILE)
						continue;
					$property['ID'] = (int)$property['ID'];
					if (empty($propertyMap[$property['ID']]))
						continue;
					$propertyKey = $propertyMap[$property['ID']];
					$value = '';

					$check = false;
					if ($property['MULTIPLE'] == 'N')
					{
						if (!empty($property['USER_TYPE']))
						{
							switch($property['USER_TYPE'])
							{
								case 'DateTime':
								case 'Date':
									$property['VALUE'] = (string)$property['VALUE'];
									if ($property['VALUE'] != '')
									{
										$propertyFormat = false;
										if ($property['USER_TYPE'] == 'DateTime')
										{
											if (defined('FORMAT_DATETIME'))
												$propertyFormat = FORMAT_DATETIME;
										}
										else
										{
											if (defined('FORMAT_DATE'))
												$propertyFormat = FORMAT_DATE;
										}
										$intStackTimestamp = (int)$property['VALUE'];
										$property['VALUE'] = (
											$intStackTimestamp.'!' != $property['VALUE'].'!'
											? (int)MakeTimeStamp($property['VALUE'], $propertyFormat)
											: $intStackTimestamp
										);
									}
									$value = $property['VALUE'];
									$check = true;
									break;
							}
						}
						if (!$check)
						{
							switch ($property['PROPERTY_TYPE'])
							{
								case Iblock\PropertyTable::TYPE_LIST:
									$property['VALUE_ENUM_ID'] = (int)$property['VALUE_ENUM_ID'];
									$value = ($property['VALUE_ENUM_ID'] > 0 ? $property['VALUE_ENUM_ID'] : -1);
									break;
								case Iblock\PropertyTable::TYPE_ELEMENT:
								case Iblock\PropertyTable::TYPE_SECTION:
									$property['VALUE'] = (int)$property['VALUE'];
									$value = ($property['VALUE'] > 0 ? $property['VALUE'] : -1);
									break;
								default:
									$value = $property['VALUE'];
									break;
							}
						}
					}
					else
					{
						$value = [];
						if (!empty($property['USER_TYPE']))
						{
							switch($property['USER_TYPE'])
							{
								case 'DateTime':
								case 'Date':
									if (!empty($property['VALUE']) && is_array($property['VALUE']))
									{
										$propertyFormat = false;
										if ($property['USER_TYPE'] == 'DateTime')
										{
											if (defined('FORMAT_DATETIME'))
												$propertyFormat = FORMAT_DATETIME;
										}
										else
										{
											if (defined('FORMAT_DATE'))
												$propertyFormat = FORMAT_DATE;
										}
										foreach ($property['VALUE'] as &$oneValue)
										{
											$oneValue = (string)$oneValue;
											if ('' != $oneValue)
											{
												$intStackTimestamp = (int)$oneValue;
												if ($intStackTimestamp.'!' != $oneValue.'!')
													$oneValue = (int)MakeTimeStamp($oneValue, $propertyFormat);
												else
													$oneValue = $intStackTimestamp;
											}
											$value[] = $oneValue;
										}
										unset($oneValue, $propertyFormat);
									}
									$check = true;
									break;
							}
						}
						if (!$check)
						{
							switch ($property['PROPERTY_TYPE'])
							{
								case Iblock\PropertyTable::TYPE_LIST:
									if (!empty($property['VALUE_ENUM_ID']) && is_array($property['VALUE_ENUM_ID']))
									{
										foreach ($property['VALUE_ENUM_ID'] as &$oneValue)
										{
											$oneValue = (int)$oneValue;
											if ($oneValue > 0)
												$value[] = $oneValue;
										}
										unset($oneValue);
									}
									if (empty($value))
										$value = [-1];
									break;
								case Iblock\PropertyTable::TYPE_ELEMENT:
								case Iblock\PropertyTable::TYPE_SECTION:
									if (!empty($property['VALUE']) && is_array($property['VALUE']))
									{
										foreach ($property['VALUE'] as &$oneValue)
										{
											$oneValue = (int)$oneValue;
											if ($oneValue > 0)
												$value[] = $oneValue;
										}
										unset($oneValue);
									}
									if (empty($value))
										$value = [-1];
									break;
								default:
									$value = $property['VALUE'];
									break;
							}
						}
					}
					$productData[$element][$propertyKey] = (is_array($value) ? $value : [$value]);
				}
			}
			unset($element);
		}
		unset($iblock);
	}

	/**
	 * Fill empty property values for iblock 1.0
	 *
	 * @param array &$propertyValues		Product properties.
	 * @param int $iblockId					Iblock id.
	 * @param array $itemIds				Product id list.
	 * @param array $propertyIds			Property id list.
	 * @return void
	 */
	protected static function fillEmptyProperties(array &$propertyValues, int $iblockId, array $itemIds, array $propertyIds): void
	{
		if ($iblockId <= 0 || empty($itemIds) || empty($propertyIds))
		{
			return;
		}
		$propertyList = [];
		$iterator = Iblock\PropertyTable::getList([
			'select' => ['ID', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE'],
			'filter' => ['=IBLOCK_ID' => $iblockId, '@ID' => $propertyIds]
		]);
		while ($row = $iterator->fetch())
		{
			$id = (int)$row['ID'];
			$multiple = ($row['MULTIPLE'] == 'Y');
			if ($multiple)
			{
				$row = $row
					+ [
						'VALUE_ENUM' => null,
						'VALUE_XML_ID' => null,
						'VALUE_SORT' => null,
						'VALUE' => false,
						'PROPERTY_VALUE_ID' => false,
						'DESCRIPTION' => false,
						'~DESCRIPTION' => false,
						'~VALUE' => false
					];
			}
			else
			{
				$row = $row
					+ [
						'VALUE_ENUM' => null,
						'VALUE_XML_ID' => null,
						'VALUE_SORT' => null,
						'VALUE' => '',
						'PROPERTY_VALUE_ID' => null,
						'DESCRIPTION' => '',
						'~DESCRIPTION' => '',
						'~VALUE' => '',
					];
			}
			if ($row['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_LIST)
			{
				$row['VALUE_ENUM_ID'] = ($multiple ? false : null);
			}

			$propertyList[$id] = $row;
		}
		unset($row, $iterator);

		foreach ($itemIds as $id)
		{
			if (!empty($propertyValues[$id]))
			{
				continue;
			}
			$propertyValues[$id] = $propertyList;
		}
		unset($propertyList);
	}

	/**
	 * Returns parent product data.
	 *
	 * @param array &$productData			Product data.
	 * @param array $entityData				Entity data.
	 * @param array $iblockData				Iblock data.
	 * @return void
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected static function getParentProducts(&$productData, $entityData, $iblockData): void
	{
		if (empty($iblockData['skuIblockList']))
			return;
		if (empty($productData) || !is_array($productData))
			return;
		$parentMap = [];
		$parentData = [];
		$parentIblockData = [
			'iblockElement' => [],
			'iblockList' => []
		];
		if (!empty($entityData['iblockFields']))
		{
			foreach ($entityData['iblockFields'] as &$value)
				$value = 'PARENT_'.$value;
		}
		if (array_key_exists('catalogFields', $entityData))
			unset($entityData['catalogFields']);
		foreach ($iblockData['skuIblockList'] as $skuData)
		{
			if (empty($iblockData['iblockElement'][$skuData['IBLOCK_ID']]))
				continue;
			foreach ($iblockData['iblockElement'][$skuData['IBLOCK_ID']] as $element)
			{
				if (empty($productData[$element]['PARENT_ID']))
					continue;
				$parentId = (int)(
					is_array($productData[$element]['PARENT_ID'])
					? current($productData[$element]['PARENT_ID'])
					: $productData[$element]['PARENT_ID']
				);
				if ($parentId <= 0)
					continue;
				if (!isset($parentMap[$parentId]))
					$parentMap[$parentId] = [];
				$parentMap[$parentId][] = $element;
				$parentData[$parentId] = [];
				if (!isset($parentIblockData['iblockElement'][$skuData['PRODUCT_IBLOCK_ID']]))
					$parentIblockData['iblockElement'][$skuData['PRODUCT_IBLOCK_ID']] = [];
				$parentIblockData['iblockElement'][$skuData['PRODUCT_IBLOCK_ID']][] = $parentId;
			}
			unset($parentId, $element);
		}
		unset($skuData);
		if (empty($parentIblockData['iblockElement']))
			return;
		$parentIblockData['iblockList'] = array_keys($parentIblockData['iblockElement']);

		self::getProductData($parentData, $entityData, $parentIblockData);

		foreach ($parentData as $parentId => $data)
		{
			$parentSections = [];
			if ($entityData['sections'])
			{
				$parentSections = $data['SECTION_ID'];
				unset($data['SECTION_ID']);
			}
			if(!isset($parentMap[$parentId]))
			{
				continue;
			}
			foreach ($parentMap[$parentId] as $element)
			{
				$productData[$element] = array_merge($productData[$element], $data);
				if ($entityData['sections'])
				{
					$productData[$element]['SECTION_ID'] = (
						empty($productData['SECTION_ID'])
						? $parentSections
						: array_merge($productData[$element]['SECTION_ID'], $parentSections)
					);
				}
			}
			unset($element, $parentSections);
		}
		unset($parentId, $data);
	}

	protected static function loadIblockFields(array $productIds, array $fields): array
	{
		if (isset($fields['DATE_ACTIVE_FROM']))
		{
			$fields['ACTIVE_FROM'] = $fields['DATE_ACTIVE_FROM'];
			unset($fields['DATE_ACTIVE_FROM']);
		}
		if (isset($fields['DATE_ACTIVE_TO']))
		{
			$fields['ACTIVE_TO'] = $fields['DATE_ACTIVE_TO'];
			unset($fields['DATE_ACTIVE_TO']);
		}

		$productData = [];

		\CTimeZone::Disable();
		$elementIterator = Iblock\ElementTable::getList([
			'select' => array_merge(['ID'], array_keys($fields)),
			'filter' => ['@ID' => $productIds]
		]);
		while ($element = $elementIterator->fetch())
		{
			$element['ID'] = (int)$element['ID'];
			foreach ($fields as $key => $alias)
			{
				if ($element[$key] instanceof Main\Type\DateTime)
					$productData[$element['ID']][$alias] = $element[$key]->getTimestamp();
				else
					$productData[$element['ID']][$alias] = $element[$key];
			}
		}
		\CTimeZone::Enable();

		return $productData;
	}

	protected static function loadSections(array $productIds): array
	{
		$productSection = array_fill_keys($productIds, []);
		$elementSectionIterator = Iblock\SectionElementTable::getList([
			'select' => ['*'],
			'filter' => ['@IBLOCK_ELEMENT_ID' => $productIds]
		]);
		while ($elementSection = $elementSectionIterator->fetch())
		{
			$elementSection['IBLOCK_ELEMENT_ID'] = (int)$elementSection['IBLOCK_ELEMENT_ID'];
			$elementSection['IBLOCK_SECTION_ID'] = (int)$elementSection['IBLOCK_SECTION_ID'];
			$elementSection['ADDITIONAL_PROPERTY_ID'] = (int)$elementSection['ADDITIONAL_PROPERTY_ID'];
			if ($elementSection['ADDITIONAL_PROPERTY_ID'] > 0)
				continue;
			$productSection[$elementSection['IBLOCK_ELEMENT_ID']][$elementSection['IBLOCK_SECTION_ID']] = true;
			$parentSectionIterator = \CIBlockSection::GetNavChain(0, $elementSection['IBLOCK_SECTION_ID'], ['ID']);
			while ($parentSection = $parentSectionIterator->fetch())
			{
				$parentSection['ID'] = (int)$parentSection['ID'];
				$productSection[$elementSection['IBLOCK_ELEMENT_ID']][$parentSection['ID']] = true;
			}
			unset($parentSection, $parentSectionIterator);
		}
		unset($elementSection, $elementSectionIterator);

		return $productSection;
	}

	protected static function loadCatalogFields(array $productIds, array $fields): array
	{
		$productData = [];

		$productIterator = Catalog\ProductTable::getList([
			'select' => array_merge(['ID'], array_keys($fields)),
			'filter' => ['@ID' => $productIds]
		]);
		while ($product = $productIterator->fetch())
		{
			$product['ID'] = (int)$product['ID'];
			foreach ($fields as $key => $alias)
			{
				$productData[$product['ID']][$alias] = $product[$key];
			}
		}

		return $productData;
	}

	protected static function fillProperties(array &$productData, array $productIds, array $iblockData, array $entityData)
	{
		$propertyValues = array_fill_keys($productIds, []);
		foreach ($entityData['needProperties'] as $iblock => $propertyList)
		{
			if (empty($iblockData['iblockElement'][$iblock]))
			{
				continue;
			}

			$needToLoad = array_fill_keys($iblockData['iblockElement'][$iblock], true);
			if(self::$productProperties)
			{
				foreach ($iblockData['iblockElement'][$iblock] as $productId)
				{
					$allExist = true;
					foreach ($propertyList as $prop)
					{
						$propData = self::getCachedProductProperty($productId, $prop);
						if (!empty($propData))
						{
							$propertyValues[$productId][$propData['ID']] = $propData;
						}
						else
						{
							$allExist = false;
							break;
						}
					}
					unset($prop);
					if (!$allExist)
					{
						// if property value is not exist
						$propertyValues[$productId] = [];
					}
					else
					{
						unset($needToLoad[$productId]);
					}
					unset($allExist);
				}
			}

			if(!empty($needToLoad))
			{
				$needProductIds = array_keys($needToLoad);
				sort($needProductIds);
				$iblockPropertyValues = array_fill_keys($needProductIds, []);

				\CTimeZone::Disable();
				foreach (array_chunk($needProductIds, 500) as $pageIds)
				{
					$filter = [
						'ID' => $pageIds,
						'IBLOCK_ID' => $iblock
					];

					\CIBlockElement::GetPropertyValuesArray(
						$iblockPropertyValues,
						$iblock,
						$filter,
						['ID' => $propertyList],
						[
							'USE_PROPERTY_ID' => 'Y',
							'PROPERTY_FIELDS' => ['ID', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE']
						]
					);
					foreach ($iblockPropertyValues as $productId => $data)
					{
						if (!empty($data))
						{
							$propertyValues[$productId] = $data;
							unset($needToLoad[$productId]);
						}
					}
					unset($productId, $data);
				}
				unset($pageIds);
				\CTimeZone::Enable();
				unset($iblockPropertyValues, $needProductIds);

				if (!empty($needToLoad))
				{
					self::fillEmptyProperties($propertyValues, $iblock, array_keys($needToLoad), $propertyList);
				}
			}
		}

		self::convertProperties($productData, $propertyValues, $entityData, $iblockData);
	}

	/**
	 * Returns product data.
	 *
	 * @param array &$productData			Product data.
	 * @param array $entityData				Entity data.
	 * @param array $iblockData				Iblock list data.
	 * @return void
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected static function getProductData(&$productData, $entityData, $iblockData): void
	{
		if (!empty($iblockData['iblockElement']))
		{
			$productList = array_keys($productData);
			if (!empty($entityData['iblockFields']))
			{
				foreach(self::loadIblockFields($productList, $entityData['iblockFields']) as $productId => $fields)
				{
					$productData[$productId] = (
						empty($productData[$productId])
						? $fields
						: array_merge($productData[$productId], $fields)
					);
				}
				unset($fields);
			}
			if ($entityData['sections'])
			{
				foreach(self::loadSections($productList) as $element => $sections)
				{
					$productData[$element]['SECTION_ID'] = array_keys($sections);
				}
			}
			if (!empty($entityData['needProperties']))
			{
				self::fillProperties($productData, $productList, $iblockData, $entityData);
			}
			if (!empty($entityData['catalogFields']))
			{
				foreach(self::loadCatalogFields($productList, $entityData['catalogFields']) as $productId => $fields)
				{
					$productData[$productId] = (
						empty($productData[$productId])
						? $fields
						: array_merge($productData[$productId], $fields)
					);
				}
				unset($fields);
			}
			if (!empty($entityData['priceFields']) && !empty($entityData['priceData']))
			{
				foreach($entityData['priceData'] as $productId => $priceId)
				{
					$productData[$productId]['CATALOG_GROUP_ID'] = $priceId;
				}
				unset($product, $productIterator);
			}

			if (!empty($iblockData['skuIblockList']))
				self::getParentProducts($productData, $entityData, $iblockData);
		}
	}

	/**
	 * Create sale action.
	 *
	 * @param array &$discount			Discount data.
	 * @param array $params				Manager parameters.
	 * @return void
	 * @noinspection PhpMissingParamTypeInspection
	 */
	protected static function createSaleAction(&$discount, $params): void
	{
		$data = [
			'TYPE' => $discount['VALUE_TYPE'],
			'VALUE' => $discount['VALUE'],
			'CURRENCY' => $discount['CURRENCY'],
			'USE_BASE_PRICE' => $params['USE_BASE_PRICE']
		];
		if ($discount['TYPE'] == Catalog\DiscountTable::VALUE_TYPE_PERCENT)
			$data['MAX_VALUE'] = $discount['MAX_VALUE'];

		$action = '\Bitrix\Catalog\Discount\DiscountManager::applyDiscount('.$params['BASKET_ITEM'].', '.var_export($data, true).');';
		$discount['APPLICATION'] = 'function (&'.$params['BASKET_ITEM'].'){'.$action.'};';
		$discount['ACTIONS'] = $data;
		unset($action, $data);

		if (self::$saleIncluded === null)
			self::$saleIncluded = Loader::includeModule('sale');
		if (!self::$saleIncluded)
			return;

		$type = '';
		$descr = [
			'VALUE_ACTION' => (
				$discount['TYPE'] == Catalog\DiscountTable::TYPE_DISCOUNT_SAVE
				? Sale\Discount\Formatter::VALUE_ACTION_CUMULATIVE
				: Sale\Discount\Formatter::VALUE_ACTION_DISCOUNT
			),
			'VALUE' => $discount['VALUE']
		];
		switch ($discount['VALUE_TYPE'])
		{
			case Catalog\DiscountTable::VALUE_TYPE_PERCENT:
				$type = (
					$discount['MAX_VALUE'] > 0
					? Sale\Discount\Formatter::TYPE_LIMIT_VALUE
					: Sale\Discount\Formatter::TYPE_VALUE
				);
				$descr['VALUE_TYPE'] = Sale\Discount\Formatter::VALUE_TYPE_PERCENT;
				if ($discount['MAX_VALUE'] > 0)
				{
					$descr['LIMIT_TYPE'] = Sale\Discount\Formatter::LIMIT_MAX;
					$descr['LIMIT_UNIT'] = $discount['CURRENCY'];
					$descr['LIMIT_VALUE'] = $discount['MAX_VALUE'];
				}
				break;
			case Catalog\DiscountTable::VALUE_TYPE_FIX:
				$type = Sale\Discount\Formatter::TYPE_VALUE;
				$descr['VALUE_TYPE'] = Sale\Discount\Formatter::VALUE_TYPE_CURRENCY;
				$descr['VALUE_UNIT'] = $discount['CURRENCY'];
				break;
			case Catalog\DiscountTable::VALUE_TYPE_SALE:
				$type = Sale\Discount\Formatter::TYPE_FIXED;
				$descr['VALUE_UNIT'] = $discount['CURRENCY'];
				break;
		}
		$descrResult = Sale\Discount\Formatter::prepareRow($type, $descr);
		if ($descrResult !== null)
		{
			$discount['ACTIONS_DESCR'] = [
				'BASKET' => [
					0 => $descrResult
				]
			];
		}
		unset($descrResult, $descr, $type);
	}

	protected static function fillProductPriceList(&$entityData, $priceIds)
	{
		$entityData['priceData'] = [];
		if(empty($entityData['priceFields']) || empty($priceIds))
		{
			return;
		}

		$priceData = [];
		$priceList = Catalog\PriceTable::getList([
			'select' => ['PRODUCT_ID', 'CATALOG_GROUP_ID'],
			'filter' => ['@ID' => $priceIds],
		]);
		while($price = $priceList->fetch())
		{
			if(!isset($priceData[$price['PRODUCT_ID']]))
			{
				$priceData[$price['PRODUCT_ID']] = [];
			}
			$priceData[$price['PRODUCT_ID']] = $price['CATALOG_GROUP_ID'];
		}

		$entityData['priceData'] = $priceData;
	}

	/**
	 * Rounded catalog discount value.
	 *
	 * @param float|int $value Value.
	 * @param string $currency Currency.
	 * @return float
	 */
	protected static function roundValue($value, string $currency): float
	{
		if (self::$saleIncluded === null)
			self::$saleIncluded = Loader::includeModule('sale');
		if (self::$saleIncluded)
			return Sale\Discount\Actions::roundValue($value, $currency);
		else
			return roundEx($value, CATALOG_VALUE_PRECISION);
	}

	/**
	 * Returns data after price rounding.
	 * @internal
	 *
	 * @param array $basketItem     Basket row data.
	 * @param array $roundData      Round rule.
	 * @return array
	 */
	private static function getRoundResult(array $basketItem, array $roundData): array
	{
		$result = [
			'ROUND_RULE' => $roundData
		];
		$result['PRICE'] = Catalog\Product\Price::roundValue(
			$basketItem['PRICE'],
			$roundData['ROUND_PRECISION'],
			$roundData['ROUND_TYPE']
		);

		if (isset($basketItem['BASE_PRICE']))
		{
			$result['DISCOUNT_PRICE'] = $basketItem['BASE_PRICE'] - $result['PRICE'];
		}
		else
		{
			if (!isset($result['DISCOUNT_PRICE']))
				$result['DISCOUNT_PRICE'] = 0;
			$result['DISCOUNT_PRICE'] += ($basketItem['PRICE'] - $result['PRICE']);
		}

		return $result;
	}

	private static function getPriceDataByPriceId($priceId)
	{
		foreach(self::$preloadedPriceData as $priceData)
		{
			if($priceData['ID'] == $priceId)
			{
				return $priceData;
			}
		}

		return null;
	}

	private static function getCachedProductProperty($productId, $propertyId)
	{
		if(!isset(self::$productProperties[$productId]))
		{
			return null;
		}

		foreach(self::$productProperties[$productId] as $props)
		{
			if($props['ID'] == $propertyId)
			{
				return $props;
			}
		}

		return null;
	}
}
