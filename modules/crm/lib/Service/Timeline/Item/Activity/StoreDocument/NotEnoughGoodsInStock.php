<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\StoreDocument;

use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Main\Localization\Loc;

abstract class NotEnoughGoodsInStock extends Activity\StoreDocument
{
	protected function getActivityTypeId(): string
	{
		return sprintf(
			'StoreDocument:NotEnoughGoodsInStock:%s',
			$this->getConcreteType()
		);
	}

	public function getIconCode(): ?string
	{
		return Icon::TASK;
	}

	public function getButtons(): ?array
	{
		if (!$this->isScheduled())
		{
			return [];
		}

		return [
			'complete' =>
				(new Button(
					Loc::getMessage('CRM_TIMELINE_ITEM_STORE_DOCUMENT_NOT_ENOUGH_PRODUCT_IN_STOCK_MARK_COMPLETED_BUTTON'),
					Button::TYPE_PRIMARY,
				))
					->setAction($this->getCompleteAction())->setHideIfReadonly()
			,
		];
	}

	protected function getConcreteType(): string
	{
		return (new \ReflectionClass($this))->getShortName();
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
