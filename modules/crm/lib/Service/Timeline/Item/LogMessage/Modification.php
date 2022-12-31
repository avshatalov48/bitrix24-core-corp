<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChange;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelper;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelperLink;
use Bitrix\Crm\Service\Timeline\Layout\Header\InfoHelperText;
use Bitrix\Main\Localization\Loc;

class Modification extends LogMessage
{
	public function getIconCode(): ?string
	{
		$modifiedField = $this->getHistoryItemModel()->get('MODIFIED_FIELD');

		switch ($modifiedField)
		{
			case 'STATUS_ID':
			case 'STAGE_ID':
			case 'STATUS':
			case 'TASK:STATUS':
				return 'stage-change';
			case 'IS_MANUAL_OPPORTUNITY':
				return 'sum';
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

		if ($modifiedField === 'IS_MANUAL_OPPORTUNITY')
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

		$startName = $this->getHistoryItemModel()->get('START_NAME');
		$finishName = $this->getHistoryItemModel()->get('FINISH_NAME');

		$result['valueChange'] = (new ValueChange())
			->setFrom($startName)
			->setTo($finishName);

		return $result;
	}
}
