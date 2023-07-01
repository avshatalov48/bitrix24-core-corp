<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Item\DealProductList\ExpandableListFactory;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/Ecommerce.php');

class ProductCompilationSentToCustomer extends Configurable
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
		return Loc::getMessage('CRM_TIMELINE_ECOMMERCE_PRODUCT_SELECTION_SENT_TO_CUSTOMER');
	}

	/**
	 * @inheritDoc
	 */
	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::STORE;
	}

	/**
	 * @inheritDoc
	 */
	public function getLogo(): ?Layout\Body\Logo
	{
		return Logo::getInstance(Logo::SHOP)
			->createLogo()
		;
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
