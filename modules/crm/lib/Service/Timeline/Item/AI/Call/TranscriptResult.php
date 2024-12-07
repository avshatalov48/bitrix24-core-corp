<?php

namespace Bitrix\Crm\Service\Timeline\Item\AI\Call;

use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Integration\AI\Result;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Localization\Loc;

final class TranscriptResult extends Base
{
	protected function getAICallTypeId(): string
	{
		return 'TranscriptResult';
	}

	protected function getAdditionalIconCode(): string
	{
		return 'a-letter';
	}

	protected function getOpenButtonTitle(): string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_TRANSCRIPT_OPEN_BTN');
	}

	protected function getOpenAction(): ?Action
	{
		return (new Action\JsEvent('TranscriptResult:Open'))
			->addActionParamInt('activityId', $this->getActivityId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
			->addActionParamString('languageTitle', $this->getJobResultLanguageTitle())
		;
	}

	protected function getJobResult(): ?Result
	{
		$activityId = $this->getAssociatedEntityModel()?->get('ID');
		if ($activityId === null)
		{
			return null;
		}

		return JobRepository::getInstance()
			->getTranscribeCallRecordingResultByActivity($activityId)
		;
	}

	protected function buildJobLanguageBlock(): ?ContentBlock
	{
		return null;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_AI_TRANSCRIPT_RESULT');
	}
}
