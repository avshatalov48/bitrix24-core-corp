<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Integration\Calendar\ActivityHandler;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/lib/Badge/Type/CalendarSharingStatus.php");

class CalendarSharing extends Activity
{
	protected function getActivityTypeId(): string
	{
		return 'CalendarSharing';
	}

	public function getIconCode(): ?string
	{
		if (!$this->isScheduled())
		{
			if (
				$this->isCanceledByClient()
				|| $this->isCanceledByManager()
				|| $this->isNotHeldMeeting()
			)
			{
				return Icon::CIRCLE_CROSSED;
			}
		}

		return Icon::CIRCLE_CHECK;
	}

	public function getTitle(): ?string
	{
		if (!$this->isScheduled())
		{
			if ($this->isCanceledByManager())
			{
				return Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_EVENT_CANCELED_BY_MANAGER');
			}

			if ($this->isCanceledByClient() || $this->isNotHeldMeeting())
			{
				return Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_EVENT_NOT_COMPLETED');
			}

			return Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_EVENT_COMPLETED');
		}

		return Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_EVENT_PLANNED_WITH_CLIENT');
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$deadline = $this->getDeadline();
		if (!$deadline)
		{
			return null;
		}

		$logo = new Layout\Body\CalendarLogo($deadline);

		if (!$this->isScheduled())
		{
			if ($this->isCanceledByManager() || $this->isCanceledByClient() || $this->isNotHeldMeeting())
			{
				$logo->setAdditionalIconCode('cross')
					->setAdditionalIconType(Layout\Body\Logo::ICON_TYPE_FAILURE);
			}
			else
			{
				$logo->setAdditionalIconCode('check')
					->setAdditionalIconType(Layout\Body\Logo::ICON_TYPE_SUCCESS);
			}
		}

		return $logo;
	}

	public function getTags(): ?array
	{
		$tags = [];

		if (!$this->isScheduled() && $this->isCanceledByClient())
		{
			$tags['canceledByClient'] = (new Layout\Header\Tag(
				Loc::getMessage('CRM_BADGE_CALENDAR_SHARING_STATUS_CANCELED_BY_CLIENT_VALUE'),
				Layout\Header\Tag::TYPE_FAILURE
			));
		}

		return $tags;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$eventStartBlock = $this->buildEventStartBlock();
		if ($eventStartBlock)
		{
			$result['eventStart'] = $eventStartBlock;
		}

		$contactBlock = $this->buildContactBlock();
		if ($contactBlock)
		{
			$result['contact'] = $contactBlock;
		}

		$openCalendarEventBlock = $this->buildOpenCalendarEventBlock();
		if ($openCalendarEventBlock)
		{
			$result['openCalendarEvent'] = $openCalendarEventBlock;
		}

		$clientCommentBlock = $this->buildClientCommentBlock();
		$descriptionBlock = $this->buildDescriptionBlock();

		if ($descriptionBlock)
		{
			$result['clientComment'] = $clientCommentBlock;
			$result['description'] = $descriptionBlock;
		}

		return $result;
	}


	public function getButtons(): ?array
	{
		$buttons = [];

		if ($this->isScheduled())
		{
			$buttons['completed'] = (
				new Layout\Footer\Button(
					Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_COMPLETED_BUTTON'),
					Layout\Footer\Button::TYPE_PRIMARY
				)
			)
			->setAction($this->getCompleteAction())->setHideIfReadonly();

			$buttons['notCompleted'] = (
				new Layout\Footer\Button(
					Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_NOT_COMPLETED_BUTTON'),
					Layout\Footer\Button::TYPE_SECONDARY
				)
			)
			->setAction(
				(new Layout\Action\RunAjaxAction('crm.timeline.calendar.sharing.completeWithStatus'))
					->addActionParamInt('activityId', $this->getActivityId())
					->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamString('status', ActivityHandler::SHARING_STATUS_MEETING_NOT_HELD)
					->setAnimation(Layout\Action\Animation::showLoaderForItem()->setForever()
				)
			);
		}
		else if ($this->getEventId())
		{
			$buttons['openEvent'] = (
				new Layout\Footer\Button(
					Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_OPEN_MEETING'),
					Layout\Footer\Button::TYPE_SECONDARY
				)
			)
			->setScopeWeb()
			->setAction(
				(new Layout\Action\JsEvent($this->getType() . ':OpenCalendarEvent'))
				->addActionParamInt('eventId', $this->getEventId())
				->addActionParamBoolean('isSharing', $this->isCanceledByClient())
			);
		}

		return $buttons;
	}

	public function getAdditionalIconButton(): ?Layout\Footer\IconButton
	{
		if ($this->isScheduled())
		{
			return (new Layout\Footer\IconButton('videoconference', Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_START_VIDEOCONFERENCE')))
				->setAction(
					(new Layout\Action\JsEvent($this->getType() . ':StartVideoconference'))
						->addActionParamInt('eventId', $this->getEventId())
						->addActionParamInt('ownerId', $this->getContext()->getEntityId())
						->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
				);
		}

		return null;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	public function getMenuItems(): array
	{
		$menuItems = [];
		if ($this->isScheduled())
		{
			$cancelMeetingMenuItem = $this->createCancelMeetingMenuItem();
			if ($cancelMeetingMenuItem)
			{
				$menuItems['cancelMeeting'] = $cancelMeetingMenuItem;
			}
		}

		return $menuItems;
	}

	private function buildEventStartBlock(): ?ContentBlock
	{
		$eventStart = $this->getDeadline();

		if (!$eventStart)
		{
			return null;
		}

		return (new ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(
				Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_DATE_AND_TIME')
			)
			->setContentBlock(
				(new ContentBlock\EditableDate())
					->setStyle(ContentBlock\EditableDate::STYLE_PILL)
					->setDate($eventStart)
			)
		;
	}

	private function buildContactBlock(): ?ContentBlock
	{
		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');

		if (!$settings)
		{
			return null;
		}

		$contactTypeId = $settings['CONTACT_TYPE_ID'] ?? null;
		$contactId = $settings['CONTACT_ID'] ?? null;
		$guestName = $settings['GUEST_NAME'] ?? null;

		if ((!$contactId || !$contactTypeId) && !$guestName)
		{
			return null;
		}

		if ($contactId && $contactTypeId)
		{
			$contactName = $this->getContactName($contactTypeId, $contactId);
			$contactUrl = $this->getContactUrl($contactTypeId, $contactId);
		}
		else
		{
			$contactName = $settings['GUEST_NAME'];
			$contactUrl = false;
		}


		return (new ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(
				Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_CLIENT')
			)
			->setContentBlock(
				ContentBlock\ContentBlockFactory::createTextOrLink(
					$contactName, $contactUrl ? new Layout\Action\Redirect($contactUrl) : null
				)
			)
		;
	}

	private function buildOpenCalendarEventBlock(): ?ContentBlock
	{
		if (!$this->getEventId() || !$this->isScheduled())
		{
			return null;
		}

		return (new ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(
				''
			)
			->setContentBlock(
				(new ContentBlock\Link())
					->setIsBold(false)
					->setValue(Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_OPEN_CALENDAR_EVENT'))
					->setAction(
						(new Layout\Action\JsEvent($this->getType() . ':OpenCalendarEvent'))
							->addActionParamInt('eventId', $this->getEventId())
					)
			)
			->setScopeWeb()
			;
	}

	private function buildClientCommentBlock(): ContentBlock
	{
		return ContentBlock\ContentBlockFactory::createTitle(
			Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_CLIENT_COMMENT')
		);
	}

	private function buildDescriptionBlock(): ?ContentBlock
	{
		$description = (string)($this->getAssociatedEntityModel()->get('DESCRIPTION') ?? $this->getAssociatedEntityModel()->get('DESCRIPTION_RAW') ?? '');

		if ($description === '')
		{
			return null;
		}

		$description = trim($description);

		return (new ContentBlock\EditableDescription())
			->setText($description)
			->setEditable(false)
			->setBackgroundColor(
				$this->isScheduled()
					? ContentBlock\EditableDescription::BG_COLOR_YELLOW
					: ContentBlock\EditableDescription::BG_COLOR_WHITE
			)
		;
	}

	private function getContactName($contactTypeId, $contactId): string
	{
		$contactData = Container::getInstance()
			->getEntityBroker($contactTypeId)
			->getById($contactId)
		;

		$result = false;
		if ($contactData)
		{
			if ($contactTypeId === \CCrmOwnerType::Contact)
			{
				$result = $contactData->getFullName();
			}
			else if ($contactTypeId === \CCrmOwnerType::Company)
			{
				$result = $contactData->getTitle();
			}
		}

		return $result ?: Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_NOT_FOUND');
	}

	private function getContactUrl($contactTypeId, $contactId): ?Uri
	{
		$result = null;

		$detailUrl = Container::getInstance()
			->getRouter()
			->getItemDetailUrl(
				$contactTypeId,
				$contactId
			)
		;
		if ($detailUrl)
		{
			$result = new Uri($detailUrl);
		}

		return $result;
	}

	private function getEventId(): ?int
	{
		return $this->getAssociatedEntityModel()->get('CALENDAR_EVENT_ID');
	}

	private function createCancelMeetingMenuItem(): ?Layout\Menu\MenuItem
	{
		if (!$this->canEditEntity() || !$this->getEventId())
		{
			return null;
		}

		return (new Layout\Menu\MenuItem(Loc::getMessage('CRM_TIMELINE_ITEM_CALENDAR_SHARING_CANCEL_MEETING')))
			->setAction(
				(new Layout\Action\RunAjaxAction('crm.timeline.calendar.sharing.cancelMeeting'))
					->addActionParamInt('eventId', $this->getEventId())
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
					->setAnimation(Layout\Action\Animation::disableItem()->setForever())
			);
	}

	private function canEditEntity(): bool
	{
		$userId = ($this->getContext()->getType() === \Bitrix\Crm\Service\Timeline\Context::PULL)
			? ($this->getModel()->getAuthorId() ?? 0)
			: $this->getContext()->getUserId()
		;
		return \Bitrix\Crm\Activity\Provider\CalendarSharing::checkUpdatePermission($this->getAssociatedEntityModel()->toArray(), $userId);
	}

	private function isCanceledByManager(): bool
	{
		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');

		if (!$settings)
		{
			return false;
		}

		return isset($settings[ActivityHandler::SHARING_STATUS_CANCELED_BY_MANAGER]);
	}

	private function isCanceledByClient(): bool
	{
		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');

		if (!$settings)
		{
			return false;
		}

		return isset($settings[ActivityHandler::SHARING_STATUS_CANCELED_BY_CLIENT]);
	}

	private function isNotHeldMeeting(): bool
	{
		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');

		if (!$settings)
		{
			return false;
		}

		return isset($settings[ActivityHandler::SHARING_STATUS_MEETING_NOT_HELD]);
	}
}