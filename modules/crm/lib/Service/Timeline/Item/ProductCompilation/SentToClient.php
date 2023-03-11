<?php

namespace Bitrix\Crm\Service\Timeline\Item\ProductCompilation;

use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Item\DealProductList\ExpandableListFactory;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Localization\Loc;

class SentToClient extends Configurable
{
	/**
	 * @inheritDoc
	 */
	public function getType(): string
	{
		return 'ProductCompilation:SentToClient';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_PRODUCT_COMPILATION_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function getIconCode(): ?string
	{
		return 'store';
	}

	/**
	 * @inheritDoc
	 */
	public function getLogo(): ?Layout\Body\Logo
	{
		return (new Layout\Body\Logo('shop'))->setInCircle();
	}

	/**
	 * @inheritDoc
	 */
	public function getContentBlocks(): ?array
	{
		$products = $this->getHistoryItemModel()->get('SENT_PRODUCTS');
		$dealId = (int)$this->getHistoryItemModel()->get('DEAL_ID');

		if (!($dealId && is_array($products)))
		{
			return null;
		}

		return [
			'productList' => ExpandableListFactory::makeByProductsData($products, $dealId),
		];
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
