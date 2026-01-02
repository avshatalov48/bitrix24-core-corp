<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Main\Localization\Loc;

final class RepeatSaleCreated extends LogMessage
{
	public function getType(): string
	{
		return 'RepeatSaleCreated';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_REPEAT_SALE_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		return [
			'scenario' => $this->buildScenarioBlock(),
		];
	}

	private function buildScenarioBlock(): LineOfTextBlocks
	{
		$params = $this->getAssociatedEntityModel()?->get('PROVIDER_PARAMS') ?? [];
		$segmentId = (int)($params['SEGMENT_ID'] ?? 0);
		$segmentName = Loc::getMessage('CRM_TIMELINE_LOG_REPEAT_SALE_UNKNOWN_SCENARIO');
		if ($segmentId > 0)
		{
			$segment = RepeatSaleSegmentController::getInstance()->getById($segmentId);
			$segmentName = $segment?->getTitle() ?? Loc::getMessage('CRM_TIMELINE_LOG_REPEAT_SALE_UNKNOWN_SCENARIO');
		}

		$action = null;

		if ($segmentId > 0 && Container::getInstance()->getRepeatSaleAvailabilityChecker()->hasPermission())
		{
			$action = (new JsEvent('Activity:RepeatSale:OpenSegment'))
				->addActionParamInt('segmentId', $segmentId)
			;
		}
		elseif ($segmentId > 0)
		{
			$action = new JsEvent('Activity:RepeatSale:ShowRestrictionSlider');
		}

		$textOrLink = ContentBlockFactory::createTextOrLink($segmentName, $action);

		return (new LineOfTextBlocks())
			->addContentBlock('title', ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_LOG_REPEAT_SALE_SCENARIO')))
			->addContentBlock('value', $textOrLink->setIsBold($segmentId > 0))
		;
	}
}
