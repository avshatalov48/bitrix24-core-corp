<?php

namespace Bitrix\Crm\Service\Timeline\Item\DealProductList\Model;

/**
 * @internal
 */
final class Converter
{
	public static function convertToArray(Product $product): array
	{
		return [
			'offerId' => $product->getOfferId(),
			'name' => $product->getName(),
			'adminLink' => $product->getAdminLink(),
			'image' => $product->getImageSource(),
			'variationInfo' => $product->getVariationInfo(),
			'price' => $product->getPrice(),
			'currency' => $product->getCurrency(),
		];
	}

	public static function convertFromArray(array $product): Product
	{
		return
			(new Product())
				->setOfferId($product['offerId'] ?? null)
				->setName($product['name'] ?? null)
				->setAdminLink($product['adminLink'] ?? null)
				->setImageSource($product['image'] ?? null)
				->setVariationInfo($product['variationInfo'] ?? null)
				->setPrice($product['price'] ?? null)
				->setCurrency($product['currency'] ?? null)
		;
	}
}
