<?php

namespace Bitrix\Crm\Service\Timeline\Item\AI\Call;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Main\Localization\Loc;

final class TranscriptSummaryResult extends Base
{
	protected function getAICallTypeId(): string
	{
		return 'TranscriptSummaryResult';
	}

	protected function getAdditionalIconCode(): string
	{
		return 'shape';
	}

	protected function getOpenButtonTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_TRANSCRIPT_SUMMARY_OPEN_BTN');
	}

	protected function getOpenAction(): ?Action
	{
		return (new Action\JsEvent('TranscriptSummaryResult:Open'))
			->addActionParamInt('activityId', $this->getActivityId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
		;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_TRANSCRIPT_SUMMARY_RESULT');
	}
}
