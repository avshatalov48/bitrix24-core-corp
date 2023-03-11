<?php

namespace Bitrix\Crm\Service\Timeline\Item\Order;

use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Item\DealProductList\ExpandableListFactory;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

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
		return Loc::getMessage('CRM_TIMELINE_ORDER_ENCOURAGE_BUY_PRODUCTS_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function getIconCode(): ?string
	{
		return 'view';
	}

	/**
	 * @inheritDoc
	 */
	public function getLogo(): ?Layout\Body\Logo
	{
		return (new Layout\Body\Logo('shop-eye'))->setInCircle();
	}

	/**
	 * @inheritDoc
	 */
	public function getContentBlocks(): ?array
	{
		$result = [
			'title' => (new Text())
				->setValue(
					Loc::getMessage('CRM_TIMELINE_ORDER_ENCOURAGE_BUY_PRODUCTS_MESSAGE_BODY')
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
				->setTitle(Loc::getMessage('CRM_TIMELINE_ORDER_ENCOURAGE_BUY_PRODUCTS_VIEWED_PRODUCTS'))
			;
		}

		return $result;
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
