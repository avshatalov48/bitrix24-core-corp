<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Item\DealProductList\ExpandableListFactory;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

class EncourageBuyProducts extends Configurable
{
	/**
	 * @inheritDoc
	 */
	public function getType(): string
	{
		return 'Order:EncourageBuyProducts';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_VIEWED_PRODUCTS');
	}

	/**
	 * @inheritDoc
	 */
	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::VIEW;
	}

	/**
	 * @inheritDoc
	 */
	public function getLogo(): ?Layout\Body\Logo
	{
		return Logo::getInstance(Logo::SHOP_EYE)
			->createLogo()
		;
	}

	/**
	 * @inheritDoc
	 */
	public function getContentBlocks(): ?array
	{
		$result = [
			'title' => (new Text())
				->setValue(
					Loc::getMessage('CRM_TIMELINE_ECOMMERCE_CLIENT_CUSTOMER_PLACED_ORDER')
				)
				->setFontSize(13)
				->setColor(Text::COLOR_BASE_70)
			,
		];

		$products = $this->getHistoryItemModel()->get('VIEWED_PRODUCTS');
		$dealId = (int)$this->getHistoryItemModel()->get('DEAL_ID');

		if ($dealId && is_array($products))
		{
			$result['productList'] = ExpandableListFactory::makeByProductsData($products, $dealId)
				->setTitle(Loc::getMessage('CRM_TIMELINE_ECOMMERCE_VIEWED_PRODUCTS'))
			;
		}

		return $result;
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
