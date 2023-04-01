<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\StoreDocument;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Money;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

abstract class Base extends LogMessage
{
	public function getContentBlocks(): ?array
	{
		$title = $this->getAssociatedEntityModel()->get('TITLE');
		$entityUrl = $this->getHistoryItemModel()->get('DETAIL_LINK');
		if ($this->isItemAboutCurrentEntity() || !$entityUrl)
		{
			$titleBlock = (new Text())->setValue($title);
		}
		else
		{
			$titleBlock =  (new ContentBlock\Link())
				->setValue($title)
				->setAction(new Redirect(new Uri($entityUrl)))
			;
		}

		return [
			'content' =>
				ContentBlockFactory::createLineOfTextFromTemplate(
					$this->getHistoryItemModel()->get('TITLE_TEMPLATE'),
					[
						'#TITLE#' => $titleBlock,
						'#PRICE_WITH_CURRENCY#' =>
							(new Money())
								->setOpportunity(
									(float)$this->getHistoryItemModel()->get('TOTAL')
								)
								->setCurrencyId(
									(string)$this->getHistoryItemModel()->get('CURRENCY')
								)
						,
					]
				)
					->setTextColor(Text::COLOR_BASE_90)
			,
		];
	}

	protected function getConcreteType(): string
	{
		return (new \ReflectionClass($this))->getShortName();
	}

	protected function getConcreteTypeUpperCase(): string
	{
		return mb_strtoupper($this->getConcreteType());
	}
}
