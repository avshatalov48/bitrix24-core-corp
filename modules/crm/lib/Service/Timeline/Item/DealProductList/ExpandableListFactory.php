<?php

namespace Bitrix\Crm\Service\Timeline\Item\DealProductList;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\ProductRow;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\DealProductList\Model\Converter;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ExpandableList\ExpandableList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ExpandableList\ExpandableListItem;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ExpandableList\ExpandableListItemButton;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

/**
 * @internal
 */
final class ExpandableListFactory
{
	/**
	 * Creates an ExpandableList content block based on passed deal identifier and products
	 * @see Converter::convertFromArray()
	 *
	 * @param array $productsData
	 * @param int $dealId
	 * @return ExpandableList
	 *
	 */
	public static function makeByProductsData(array $productsData, int $dealId): ExpandableList
	{
		$list = new ExpandableList();

		$products = array_map(
			static function (array $productData)
			{
				return Converter::convertFromArray($productData);
			},
			$productsData
		);
		$dealSkuIds = self::getDealSkuIds($dealId);
		foreach ($products as $product)
		{
			$isInDeal = in_array($product->getOfferId(), $dealSkuIds, true);

			$listItem =
				(new ExpandableListItem($product->getOfferId()))
					->setTitle($product->getName())
					->setTitleAction(self::getOpenProductDetailAction($product->getAdminLink()))
					->setImage($product->getImageSource())
					->setIsSelected($isInDeal)
			;

			$bottomBlock = new LineOfTextBlocks();
			if ($product->getVariationInfo())
			{
				$bottomBlock->addContentBlock(
					'variationInfo',
					(new Text())
						->setColor(Text::COLOR_BASE_50)
						->setValue($product->getVariationInfo())
						->setFontSize(Text::FONT_SIZE_SM)
				);
			}

			if (
				$product->getPrice()
				&& $product->getCurrency()
			)
			{
				$bottomBlock->addContentBlock(
					'price',
					(new Money())
						->setOpportunity($product->getPrice())
						->setCurrencyId($product->getCurrency())
						->setFontSize(Text::FONT_SIZE_SM)
				);
			}

			if (!$bottomBlock->isEmpty())
			{
				$listItem->setBottomBlock($bottomBlock);
			}

			$buttonText = null;
			$buttonAction = null;
			if ($isInDeal)
			{
				$buttonText = Loc::getMessage('CRM_TIMELINE_CONTENT_BLOCK_PRODUCT_LIST_ALREADY_IN_DEAL');
				$buttonAction = null;
			}
			elseif (self::hasCatalogRead())
			{
				$buttonText = Loc::getMessage('CRM_TIMELINE_CONTENT_BLOCK_PRODUCT_LIST_TO_DEAL');
				$buttonAction = (new Action\JsEvent('ProductList:AddToDeal'))
					->addActionParamInt('dealId', $dealId)
					->addActionParamInt('productId', $product->getOfferId())
					->addActionParamArray('options', [
						'price' => $product->getPrice(),
					])
					->setAnimation(Action\Animation::showLoaderForItem())
				;
			}

			if ($buttonText)
			{
				$listItem->setButton(
					(new ExpandableListItemButton())
						->setText($buttonText)
						->setAction($buttonAction)
				);
			}

			$list->addListItem($listItem);
		}

		return $list;
	}

	private static function getOpenProductDetailAction(?string $detailLink): ?Action
	{
		if (!$detailLink)
		{
			return null;
		}

		return self::hasCatalogRead() ? new Action\Redirect(new Uri($detailLink)) : null;
	}

	private static function hasCatalogRead(): bool
	{
		$hasCatalogRead = false;
		if (Loader::includeModule('catalog'))
		{
			$hasCatalogRead = AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
		}

		return $hasCatalogRead;
	}

	private static function getDealSkuIds(int $dealId): array
	{
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

		/** @var Deal|null $deal */
		$deal = $factory->getItem($dealId);
		if (!$deal)
		{
			return [];
		}

		/**
		 * @var ProductRow[]|null $productRows
		 */
		$productRows = $deal->getProductRows();
		if (!$productRows)
		{
			return [];
		}

		$result = [];
		foreach ($productRows as $productRow)
		{
			$result[(int)$productRow->getProductId()] = true;
		}

		return array_keys($result);
	}
}
