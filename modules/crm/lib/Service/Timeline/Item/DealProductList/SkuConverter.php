<?php

namespace Bitrix\Crm\Service\Timeline\Item\DealProductList;

use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\v2\Helpers\PropertyValue;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Crm\Service\Timeline\Item\DealProductList\Model\Product;
use Bitrix\Main\Loader;
use Bitrix\Crm\Product\Url\ProductBuilder;
use Bitrix\Iblock\Url\AdminPage\BuilderManager;

/**
 * @internal
 */
final class SkuConverter
{
	private const DEFAULT_IMAGE_SIZE = 50;

	public static function convertToProductModel(BaseSku $sku): Product
	{
		$product =
			(new Product())
				->setOfferId($sku->getId())
				->setAdminLink(self::getAdminLink($sku))
				->setName($sku->getName())
				->setImageSource(self::getImageSrc($sku))
				->setVariationInfo(PropertyValue::getSkuPropertyDisplayValues($sku))
		;

		$basePriceGroupId = GroupTable::getBasePriceTypeId();
		$price = $basePriceGroupId ? $sku->getPriceCollection()->findByGroupId($basePriceGroupId) : null;
		if ($price)
		{
			$product
				->setPrice($price->getPrice())
				->setCurrency($price->getCurrency())
			;
		}

		return $product;
	}

	private static function getImageSrc(BaseSku $sku): ?string
	{
		$image = $sku->getFrontImageCollection()->getFrontImage();
		if (!$image)
		{
			return null;
		}

		$resizedImage = \CFile::resizeImageGet(
			\CFile::getFileArray($image->getId()),
			self::DEFAULT_IMAGE_SIZE
		);
		if (!$resizedImage)
		{
			return null;
		}

		return $resizedImage['src'] ?? null;
	}

	private static function getAdminLink(BaseSku $sku): ?string
	{
		if (!Loader::includeModule('iblock'))
		{
			return null;
		}

		$linkBuilder = BuilderManager::getInstance()->getBuilder(ProductBuilder::TYPE_ID);
		if ($linkBuilder)
		{
			$linkBuilder->setIblockId($sku->getIblockId());
		}

		return $linkBuilder ? $linkBuilder->getProductDetailUrl($sku->getId()) : null;
	}
}
