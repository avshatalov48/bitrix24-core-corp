<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ValueChangeItem;
use Bitrix\Main\Localization\Loc;

final class RecordRestartAutomation extends LogMessage
{
	public function getIconCode(): ?string
	{
		return Icon::ROBOT;
	}

	public function getType(): string
	{
		return 'RestartAutomation';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_RECORD_RESTART_AUTOMATION_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$stageName = $this->getModel()->getSettings()['STAGE_NAME'];
		$stage = (new ValueChangeItem())
			->setText(Loc::getMessage('CRM_TIMELINE_RECORD_RESTART_AUTOMATION_TEXT'))
			->setPillText($stageName)->setIconCode(self::getIconCode())
		;

		return [
			'content' => $stage,
		];
	}
}
