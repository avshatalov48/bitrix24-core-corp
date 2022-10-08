<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Catalog;
use Bitrix\Crm;
use Bitrix\Iblock;
use Bitrix\Main;

class Product
{
	private const DEFAULT_IMAGE_SIZE = 50;

	public static function prepareProductsForTimeline($skuList, $imageDimensions = null)
	{
		if (!Main\Loader::includeModule('catalog') || !Main\Loader::includeModule('iblock'))
		{
			return null;
		}

		if (!is_array($imageDimensions) || !isset($imageDimensions['width'], $imageDimensions['height']))
		{
			$imageDimensions = ['width' => self::DEFAULT_IMAGE_SIZE, 'height' => self::DEFAULT_IMAGE_SIZE];
		}

		/** @var Crm\Product\Url\ProductBuilder $adminLinkBuilder */
		$adminLinkBuilder = Iblock\Url\AdminPage\BuilderManager::getInstance()->getBuilder(
			Crm\Product\Url\ProductBuilder::TYPE_ID
		);

		$basePriceGroupId = \CCatalogGroup::GetBaseGroupId();

		$newCard = Catalog\Config\State::isProductCardSliderEnabled();

		$result  = [];
		foreach ($skuList as $sku)
		{
			$price = $basePriceGroupId ? $sku->getPriceCollection()->findByGroupId($basePriceGroupId) : null;
			$image = $sku->getFrontImageCollection()->getFrontImage();
			$imageSource = $image ? \CFile::ResizeImageGet(
				\CFile::GetFileArray($image->getId()),
				$imageDimensions
			)['src'] : null;

			if ($adminLinkBuilder)
			{
				$adminLinkBuilder->setIblockId($sku->getIblockId());
			}

			$result[] = [
				'slider' => $newCard ? 'Y' : 'N',
				'offerId' => $sku->getId(),
				'adminLink' => $adminLinkBuilder ? $adminLinkBuilder->getProductDetailUrl($sku->getId()) : null,
				'name' => $sku->getName(),
				'image' => $imageSource,
				'variationInfo' => Catalog\v2\Helpers\PropertyValue::getSkuPropertyDisplayValues($sku),
				'price' => $price ? $price->getPrice() : null,
				'currency' => $price ? $price->getCurrency() : null,
			];
		}

		return $result;
	}
}
