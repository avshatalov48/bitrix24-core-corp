<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

final class LaunchError extends Base
{
	public function getType(): string
	{
		return 'LaunchError';
	}

	public function getTitle(): ?string
	{
		$settings = $this->getModel()->getSettings();
		if (empty($settings))
		{
			return Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE');
		}

		$operationTypeId = $settings['OPERATION_TYPE_ID'] ?? 0;

		return match ($operationTypeId)
		{
			TranscribeCallRecording::TYPE_ID => Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE_TRANSCRIBE_CALL'),
			SummarizeCallTranscription::TYPE_ID => Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE_SUMMARIZE_CALl'),
			FillItemFieldsFromCallTranscription::TYPE_ID => Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE_FILL_FIELDS'),
			ScoreCall::TYPE_ID => Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE_SCORE_CALl'),
			default => Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE'),
		};
	}

	public function getTags(): ?array
	{
		$settings = $this->getModel()->getSettings();
		if (empty($settings))
		{
			return null;
		}

		$statusTagLocCode = 'CRM_TIMELINE_LOG_LAUNCH_ERROR_TAG';
		$errorText = empty($settings['ERRORS']) ? '' : implode(PHP_EOL, $settings['ERRORS']);
		$engineId = $settings['ENGINE_ID'] ?? 0;
		if ($engineId !== 0)
		{
			$statusTagLocCode = 'CRM_TIMELINE_LOG_LAUNCH_ERROR_THIRDPARTY_TAG';
			$errorText = Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_THIRDPARTY_TAG_TOOLTIP');
		}
		
		$statusTag = new Tag(Loc::getMessage($statusTagLocCode), Tag::TYPE_FAILURE);
		if (!empty($errorText))
		{
			$statusTag->setHint($errorText);
		}

		return [
			'error' => $statusTag,
		];
	}
}
