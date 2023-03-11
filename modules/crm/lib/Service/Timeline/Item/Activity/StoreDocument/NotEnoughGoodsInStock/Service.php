<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument\NotEnoughGoodsInStock;

use Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument\NotEnoughGoodsInStock;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Link;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

class Service extends NotEnoughGoodsInStock
{
	private const HELP_ARTICLE_CODE = 14828480;

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ITEM_STORE_DOCUMENT_NOT_ENOUGH_PRODUCT_IN_STOCK_TITLE_SERVICE');
	}

	public function getContentBlocks(): ?array
	{
		$content = new LineOfTextBlocks();

		$content->addContentBlock(
			'text',
			(new Text())
				->setValue(
					Loc::getMessage('CRM_TIMELINE_ITEM_STORE_DOCUMENT_NOT_ENOUGH_PRODUCT_IN_STOCK_DESCRIPTION_SERVICE')
				)
		);

		$content->addContentBlock(
			'helpLink',
			(new Link())
				->setAction(
					(new JsEvent('Helpdesk:Open'))
						->addActionParamInt('articleCode', self::HELP_ARTICLE_CODE)
				)
				->setValue(
					Loc::getMessage('CRM_TIMELINE_ITEM_STORE_DOCUMENT_NOT_ENOUGH_PRODUCT_IN_STOCK_DETAILS')
				)
		);

		return [
			'content' => $content,
		];
	}
}
