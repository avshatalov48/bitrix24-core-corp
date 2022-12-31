<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Main\Localization\Loc;

class ToDo extends Activity
{
	protected function getActivityTypeId(): string
	{
		return 'ToDo';
	}

	public function getIconCode(): ?string
	{
		return 'circle-check';
	}

	public function getTitle(): string
	{
		return $this->isScheduled()
			? Loc::getMessage('CRM_TIMELINE_ITEM_TODO_TITLE_SCHEDULED')
			: Loc::getMessage('CRM_TIMELINE_ITEM_TODO_TITLE_HISTORY_ITEM')
		;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$deadline = $this->getDeadline();
		if (!$deadline)
		{
			return null;
		}

		return (new Layout\Body\CalendarLogo($deadline));
	}

	public function getContentBlocks(): array
	{
		$result = [];

		$deadline = $this->getDeadline();
		if ($deadline)
		{
			$updateDeadlineAction = null;
			if ($this->isScheduled())
			{
				$updateDeadlineAction = (new Layout\Action\RunAjaxAction('crm.activity.todo.updateDeadline'))
					->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
					->addActionParamInt('id', $this->getActivityId())
				;
			}

			$result['deadline'] = (new Layout\Body\ContentBlock\LineOfTextBlocks())
				->addContentBlock(
					'completeTo',
					ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE_TO'))
				)
				->addContentBlock(
					'deadlineSelector',
					(new Layout\Body\ContentBlock\EditableDate())
						->setStyle(Layout\Body\ContentBlock\EditableDate::STYLE_PILL)
						->setDate($deadline)
						->setAction($updateDeadlineAction)
						->setBackgroundColor($this->isScheduled() ? \Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDate::BACKGROUND_COLOR_WARNING : null)
				)
			;
		}
		$description = trim(
			$this->getAssociatedEntityModel()->get('DESCRIPTION') ?? ''
		);

		if ($this->isScheduled())
		{
			$result['description'] = (new Layout\Body\ContentBlock\EditableDescription())
				->setText($description)
				->setAction(
					(new Layout\Action\RunAjaxAction('crm.activity.todo.updateDescription'))
						->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
						->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
						->addActionParamInt('id', $this->getActivityId())
				)
			;
		}
		elseif ($description)
		{
			$result['description'] = (new Layout\Body\ContentBlock\Text())
				->setValue($description)
				->setIsMultiline()
			;
		}

		$associatedEntityId = $this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID') ?? null;
		if ($associatedEntityId)
		{
			$baseActivity = \CCrmActivity::GetList(
				[],
				[
					'=ID' => $associatedEntityId,
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
				$result['createdFrom'] = (new Layout\Body\ContentBlock\LineOfTextBlocks())
					->addContentBlock(
						'createdFrom',
						ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_CREATED_FROM'))
					)
					->addContentBlock(
						'baseActivity',
						(new Layout\Body\ContentBlock\Text())
							->setValue($baseActivity['SUBJECT'] ?: Loc::getMessage('CRM_COMMON_UNTITLED'))
							->setIsBold(true)
							->setColor(Layout\Body\ContentBlock\Text::COLOR_BASE_70)
					)
				;
			}
		}

		return $result;
	}

	public function getButtons(): array
	{
		$buttons = [];
		if (!$this->isScheduled())
		{
			return $buttons;
		}
		$buttons['complete'] = (new Layout\Footer\Button(
			Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE'),
			Layout\Footer\Button::TYPE_PRIMARY,
		))
			->setAction($this->getCompleteAction())
			->setHideIfReadonly()
		;

		if ($this->canPostpone())
		{
			$buttons['postpone'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ITEM_TODO_POSTPONE'),
				Layout\Footer\Button::TYPE_SECONDARY,
			))
				->setAction(new Layout\Action\ShowMenu($this->getPostponeMenu($this->getActivityId())))
				->setHideIfReadonly()
			;
		}

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();
		unset($items['view']);

		return $items;
	}

	public function needShowNotes(): bool
	{
		return true;
	}
}
