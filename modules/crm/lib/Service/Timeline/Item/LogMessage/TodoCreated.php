<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use CCrmActivity;

Container::getInstance()->getLocalization()->loadMessages();

class TodoCreated extends LogMessage
{
	public function getType(): string
	{
		return 'TodoCreated';
	}

	public function getIconCode(): ?string
	{
		return Icon::CIRCLE_CHECK;
	}

	public function getTitle(): ?string
	{
		$subject = $this->getModel()->getAssociatedEntityModel()?->get('SUBJECT');

		if (empty($subject))
		{
			return Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_TITLE');
		}

		return $subject;
	}

	public function getContentBlocks(): ?array
	{
		$activityData = $this->getModel()->getSettings()['ACTIVITY_DATA'];

		$baseActivityId = $activityData['ASSOCIATED_ENTITY_ID'] ?? 0;
		$deadlineTimestamp = $activityData['DEADLINE_TIMESTAMP'] ?? null;
		$description = trim($activityData['DESCRIPTION'] ?? '');
		$calendarEventId = $this->getHistoryItemModel()?->get('ASSOCIATED_ENTITY')['CALENDAR_EVENT_ID'] ?? null;
		$duration = null;
		if ($calendarEventId)
		{
			$duration = $activityData['DURATION'] ?? null;
		}

		// Temporarily removes [p] for mobile compatibility
		$descriptionType = (int)$this->getHistoryItemModel()?->get('ASSOCIATED_ENTITY')['DESCRIPTION_TYPE'] ?? null;
		if ($this->getContext()->getType() === Context::MOBILE && $descriptionType === \CCrmContentType::BBCode)
		{
			$description = \Bitrix\Crm\Format\TextHelper::removeParagraphs($description);
		}

		$result = [];
		if ($baseActivityId)
		{
			$baseActivity = CCrmActivity::GetList(
				[],
				[
					'=ID' => $baseActivityId,
					'CHECK_PERMISSIONS' => 'N'
				],
				false,
				false,
				[
					'SUBJECT',
					'ID'
				]
			)?->Fetch();

			if ($baseActivity)
			{
				$result['baseActivity'] = (new LineOfTextBlocks())
					->addContentBlock(
						'title',
						ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_LINK'))
					)
					->addContentBlock(
						'value',
						(new Text())
							->setValue($baseActivity['SUBJECT'] ?: Loc::getMessage('CRM_COMMON_UNTITLED'))
							->setColor(Text::COLOR_BASE_90)
					)
				;
			}
		}

		if ($deadlineTimestamp)
		{
			$result['created'] = (new LineOfTextBlocks())
				->addContentBlock(
					'title',
					ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_DATE'))
				)
				->addContentBlock(
					'value',
					(new Date())
						->setDate(DateTime::createFromTimestamp($deadlineTimestamp))
						->setDuration($duration)
						->setColor(Text::COLOR_BASE_90)
						->setFontSize(Text::FONT_SIZE_SM)
				)
			;
		}

		if ($description)
		{
			$result['description'] = (new EditableDescription())
				->setText($description)
				->setEditable(false)
				->setHeight(EditableDescription::HEIGHT_SHORT)
				->setUseBBCodeEditor(true)
			;
		}

		return $result;
	}
}
