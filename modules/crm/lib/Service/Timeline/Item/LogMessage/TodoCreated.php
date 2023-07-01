<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Date;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
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
		return Loc::getMessage('CRM_TIMELINE_LOG_TODO_CREATED_TITLE');
	}

	public function getContentBlocks(): ?array
	{
		$activityData = $this->getModel()->getSettings()['ACTIVITY_DATA'];

		$baseActivityId = $activityData['ASSOCIATED_ENTITY_ID'] ?? 0;
		$deadlineTimestamp = $activityData['DEADLINE_TIMESTAMP'] ?? null;
		$description = trim($activityData['DESCRIPTION'] ?? '');

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
			)->Fetch();
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
					(new Date())->setDate(DateTime::createFromTimestamp($deadlineTimestamp))->setColor(Text::COLOR_BASE_90)
				)
			;
		}

		if ($description)
		{
			$result['description'] = (new EditableDescription())
				->setText($description)
				->setEditable(false)
				->setHeight(EditableDescription::HEIGHT_SHORT);
		}

		return $result;
	}
}
