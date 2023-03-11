<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument\NotEnoughGoodsInStock;

use Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument\NotEnoughGoodsInStock;
use Bitrix\Main\Localization\Loc;

class Product extends NotEnoughGoodsInStock
{
	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ITEM_STORE_DOCUMENT_NOT_ENOUGH_PRODUCT_IN_STOCK_TITLE_PRODUCT');
	}

	protected function getContentText(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ITEM_STORE_DOCUMENT_NOT_ENOUGH_PRODUCT_IN_STOCK_DESCRIPTION_PRODUCT');
	}
}
