<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Main\Localization\Loc;

class RuleUpdated extends LogMessage
{
	use CalendarSharing\FormatTrait;
	use CalendarSharing\ModelDataTrait;
	use CalendarSharing\MessageTrait;

	public function getType(): string
	{
		return 'CalendarSharingRuleUpdated';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_RULE_UPDATED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::LINK;
	}

	public function getContentBlocks(): ?array
	{
		$rule = $this->getLinkRule();

		$titleBlock = $this->getTextBlock(Loc::getMessage('CRM_TIMELINE_CALENDAR_SHARING_RULE_UPDATED_DETAILS_TITLE'));
		$rangeBlocks = [];
		foreach ($rule['ranges'] as $index => $range)
		{
			$rangeBlocks["detail_range_$index"] = $this->getTextBlock(
				Loc::getMessage('CRM_TIMELINE_CALENDAR_SHARING_RULE_UPDATED_DETAILS_RANGE', [
					'#WEEKDAYS#' => $range['weekdaysTitle'],
					'#FROM#' => $this->formatTime($range['from']),
					'#TO#' => $this->formatTime($range['to']),
				])
			);
		}
		$durationBlock = $this->getTextBlock(
			Loc::getMessage('CRM_TIMELINE_CALENDAR_SHARING_RULE_UPDATED_DETAILS_DURATION', [
				'#DURATION#' => $this->formatDuration($rule['slotSize']),
			])
		);

		return array_merge(
			[
				'detail_title' => $titleBlock,
			],
			$rangeBlocks,
			[
				'detail_duration' => $durationBlock,
			],
		);
	}

	protected function getTextBlock(string $text): Text
	{
		return ContentBlockFactory::createTitle($text)->setColor(Text::COLOR_BASE_70);
	}
}