<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChange;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChangeItem;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelper;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelperLink;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelperText;
use Bitrix\Main\Localization\Loc;

class Modification extends LogMessage
{
	public function getIconCode(): ?string
	{
		if (in_array(
			$this->getModel()->getAssociatedEntityTypeId(),
			[
				\CCrmOwnerType::Order,
				\CCrmOwnerType::OrderShipment,
				\CCrmOwnerType::OrderPayment,
			],
			true
		))
		{
			return Icon::STORE;
		}
		

		$modifiedField = $this->getHistoryItemModel()->get('MODIFIED_FIELD');
		switch ($modifiedField)
		{
			case 'STATUS_ID':
			case 'STATUS':
			case 'TASK:STATUS':
			case Item::FIELD_NAME_STAGE_ID:
				return Icon::STAGE_CHANGE;
			case Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY:
				return Icon::SUM;
			case Item::FIELD_NAME_CATEGORY_ID:
				return Icon::PIPELINE;
		}

		return parent::getIconCode();
	}

	public function getType(): string
	{
		return 'Modification';
	}

	public function getTitle(): ?string
	{
		return $this->getHistoryItemModel()->get('TITLE');
	}

	public function getInfoHelper(): ?InfoHelper
	{
		$modifiedField = $this->getHistoryItemModel()->get('MODIFIED_FIELD');

		if ($modifiedField === Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY)
		{
			$finalValue = $this->getHistoryItemModel()->get('FINISH');
			$phrase = $finalValue === 'Y'
				? 'CRM_TIMELINE_LOG_MODIFICATION_IS_MANUAL_OPPORTUNITY_Y'
				: 'CRM_TIMELINE_LOG_MODIFICATION_IS_MANUAL_OPPORTUNITY_N';

			$action = (new JsEvent('Helpdesk:Open'))
				->addActionParamString('articleCode', '11732044');

			return (new InfoHelper())
				->setIconCode(InfoHelper::ICON_AUTO_SUM)
				->setPrimaryAction($action)
				->addText(new InfoHelperText(Loc::getMessage($phrase) . ' '))
				->addLink(new InfoHelperLink(
					Loc::getMessage('CRM_TIMELINE_LOG_MODIFICATION_READ_MORE'),
					$action
				));
		}

		return null;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];
		$historyItemModel = $this->getHistoryItemModel();
		$modifiedField = $historyItemModel->get('MODIFIED_FIELD');

		if ($modifiedField === Item::FIELD_NAME_CATEGORY_ID)
		{
			$from = (new ValueChangeItem())
				->setIconCode('pipeline')
				->setText($historyItemModel->get('START_CATEGORY_NAME'))
				->setPillText($historyItemModel->get('START_STAGE_NAME'))
			;

			$to = (new ValueChangeItem())
				->setIconCode('pipeline')
				->setText($historyItemModel->get('FINISH_CATEGORY_NAME'))
				->setPillText($historyItemModel->get('FINISH_STAGE_NAME'))
			;
		}
		else
		{
			$startName = $historyItemModel->get('START_NAME');
			$finishName = $historyItemModel->get('FINISH_NAME');

			$from = (new ValueChangeItem())->setPillText($startName);
			$to = (new ValueChangeItem())->setPillText($finishName);
		}

		$result['valueChange'] = (new ValueChange())
			->setFrom($from)
			->setTo($to);

		return $result;
	}
}
