<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Crm\Integration\AI\Operation\FillItemFieldsFromCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\FillRepeatSaleTips;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\SummarizeCallTranscription;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
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
			FillRepeatSaleTips::TYPE_ID => Loc::getMessage('CRM_TIMELINE_LOG_LAUNCH_ERROR_TITLE_REPEAT_SALE'),
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

		$errorsList = array_unique($settings['ERRORS'] ?? []);
		$errorText = empty($errorsList) ? '' : implode(PHP_EOL, $errorsList);
		$engineId = $settings['ENGINE_ID'] ?? 0;
		$statusTagLocCode = 'CRM_TIMELINE_LOG_LAUNCH_ERROR_TAG';
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
	
	public function getContentBlocks(): ?array
	{
		$operationTypeId = $this->getModel()->getSettings()['OPERATION_TYPE_ID'] ?? 0;
		if ($operationTypeId === FillRepeatSaleTips::TYPE_ID)
		{
			$params = $this->getAssociatedEntityModel()?->get('PROVIDER_PARAMS') ?? [];
			$segmentId = (int)($params['SEGMENT_ID'] ?? 0);
			if ($segmentId > 0)
			{
				$segment = RepeatSaleSegmentController::getInstance()->getById($segmentId);
				$segmentName = $segment?->getTitle() ?? Loc::getMessage('CRM_TIMELINE_ITEM_REPEAT_SALE_UNKNOWN_SCENARIO');
			}

			$textOrLink = ContentBlockFactory::createTextOrLink(
				$segmentName ?? Loc::getMessage('CRM_TIMELINE_ITEM_REPEAT_SALE_UNKNOWN_SCENARIO'),
				$segmentId > 0
					? (new JsEvent('Activity:RepeatSale:OpenSegment'))->addActionParamInt('segmentId', $segmentId)
					: null
			);
			
			return [
				'segment' => (new LineOfTextBlocks())
					->addContentBlock('title', ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_REPEAT_SALE_SCENARIO')))
					->addContentBlock('value', $textOrLink->setIsBold($segmentId > 0))
			];
		}
		
		return parent::getContentBlocks();
	}
}
