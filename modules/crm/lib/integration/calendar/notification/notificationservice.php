<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Calendar\Core\Managers\Duration\DurationManager;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\MessageSender;
use Bitrix\Calendar\Sharing;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Localization\Loc;
use Bitrix\Calendar\Core\Event;
use Bitrix\Calendar\Core;

class NotificationService extends AbstractService
{
	public const TEMPLATE_SHARING_EVENT_INVITATION = 'SHARING_EVENT_INVITATION';
	public const TEMPLATE_SHARING_EVENT_AUTO_ACCEPTED = 'SHARING_EVENT_ACCEPTED_2';
	public const TEMPLATE_SHARING_EVENT_CANCELLED_LINK_ACTIVE = 'SHARING_EVENT_CANCELLED_1';
	public const TEMPLATE_SHARING_EVENT_CANCELLED = 'SHARING_EVENT_CANCELLED_2';
	public const TEMPLATE_SHARING_EVENT_EDITED = 'SHARING_EVENT_EDITED';

	/**
	 * @param ItemIdentifier $entity
	 * @return bool
	 */
	public static function canSendMessage(ItemIdentifier $entity): bool
	{
		$repo = MessageSender\Channel\ChannelRepository::create($entity);

		$channel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		if (is_null($channel))
		{
			return false;
		}

		return NotificationsManager::canUse();
	}


	/**
	 * @param string $template
	 * @param array $placeholders
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function sendMessage(string $template, array $placeholders): bool
	{
		$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $this->crmDealLink->getEntityId());
		$channel = $this->getEntityChannel($entity);
		if (is_null($channel))
		{
			return false;
		}

		$to = $this->getToEntity($channel, $this->crmDealLink->getContactId(), $this->crmDealLink->getContactType());
		if (!$to)
		{
			return false;
		}

		return (new MessageSender\SendFacilitator\Notifications($channel))
			->setTo($to)
			->setPlaceholders($placeholders)
			->setTemplateCode($template)
			->setLanguageId('ru')
			->send()
			->isSuccess()
		;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingInvited(): bool
	{
		$manager = Sharing\Helper::getOwnerInfo($this->crmDealLink->getOwnerId());
		$placeholders = [
			'NAME' => Sharing\Helper::getPersonFullNameLoc($manager['name'], $manager['lastName']),
			'URL' => Sharing\Helper::getShortUrl($this->crmDealLink->getUrl()),
			'FIRST_NAME' => $manager['name'], // for sms
		];

		return $this->sendMessage(self::TEMPLATE_SHARING_EVENT_INVITATION, $placeholders);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingAutoAccepted(): bool
	{
		$manager = Sharing\Helper::getOwnerInfo($this->crmDealLink->getOwnerId());
		$fullName = Sharing\Helper::getPersonFullNameLoc($manager['name'], $manager['lastName']);
		$placeholders = [
			'NAME' => $fullName,
			'DATE' => Sharing\Helper::formatDate($this->event->getStart()),
			'EVENT_URL' => Sharing\Helper::getShortUrl($this->eventLink->getUrl()),
			'VIDEOCONFERENCE_URL' => Sharing\Helper::getShortUrl($this->eventLink->getUrl() . Sharing\Helper::ACTION_CONFERENCE),
			'EVENT_NAME' => Sharing\SharingEventManager::getSharingEventNameByUserName($fullName), // for title
		];

		return $this->sendMessage(self::TEMPLATE_SHARING_EVENT_AUTO_ACCEPTED, $placeholders);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingCancelled(): bool
	{
		$manager = Sharing\Helper::getOwnerInfo($this->crmDealLink->getOwnerId());
		$template = self::TEMPLATE_SHARING_EVENT_CANCELLED;
		$placeholders = [
			'NAME' => Sharing\Helper::getPersonFullNameLoc($manager['name'], $manager['lastName']),
			'DATE' => Sharing\Helper::formatDate($this->event->getStart()),
			'EVENT_URL' => Sharing\Helper::getShortUrl($this->eventLink->getUrl()),
		];

		if ($this->crmDealLink->isActive())
		{
			$template = self::TEMPLATE_SHARING_EVENT_CANCELLED_LINK_ACTIVE;
			$placeholders['URL'] = Sharing\Helper::getShortUrl($this->crmDealLink->getUrl());
		}

		return $this->sendMessage($template, $placeholders);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingEdited(): bool
	{
		$manager = Sharing\Helper::getOwnerInfo($this->crmDealLink->getOwnerId());

		$locEdited = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_EDITED_ACTION');
		if ($manager['gender'] === 'M')
		{
			$locEdited = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_EDITED_ACTION_M');
		}
		else if ($manager['gender'] === 'F')
		{
			$locEdited = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_EDITED_ACTION_F');
		}

		$oldEvent = $this->oldEvent;
		$newEvent = $this->event;
		$durationManager = new DurationManager($newEvent->getStart(), $newEvent->getEnd());
		$hasDurationChanged = !$durationManager->areDurationsEqual($oldEvent->getStart(), $oldEvent->getEnd());
		$oldDate = $this->formatEditedEventDate($oldEvent, $hasDurationChanged);
		$newDate = $this->formatEditedEventDate($newEvent, $hasDurationChanged);

		$placeholders = [
			'NAME' => Sharing\Helper::getPersonFullNameLoc($manager['name'], $manager['lastName']),
			'EVENT_URL' => Sharing\Helper::getShortUrl($this->eventLink->getUrl()),
			'VIDEOCONFERENCE_URL' => Sharing\Helper::getShortUrl($this->eventLink->getUrl() . Sharing\Helper::ACTION_CONFERENCE),
			'LOC_EDITED' => $locEdited,
			'OLD_DATE' => $oldDate,
			'NEW_DATE' => $newDate,
		];

		return $this->sendMessage(self::TEMPLATE_SHARING_EVENT_EDITED, $placeholders);
	}

	/**
	 * @param ItemIdentifier $entity
	 * @return MessageSender\Channel|null
	 */
	protected function getEntityChannel(ItemIdentifier $entity): ?MessageSender\Channel
	{
		$repo = MessageSender\Channel\ChannelRepository::create($entity);
		$channel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		if (is_null($channel))
		{
			return null;
		}

		return $channel;
	}

	protected function formatEditedEventDate(Event\Event $event, $hasDurationChanged): string
	{
		if ($event->isFullDayEvent())
		{
			$result = Loc::getMessage(
				'CRM_CALENDAR_SHARING_EVENT_FULL_DAY_INFO',
				['#EVENT_DATE#' => Sharing\Helper::formatDateWithoutTime($event->getStart())]
			);
		}
		else if ($hasDurationChanged)
		{
			$durationManager = new DurationManager($event->getStart(), $event->getEnd());
			$formattedDate = Sharing\Helper::formatDate($event->getStart());
			$formattedDuration = $durationManager->getFormattedDuration();

			$result = "{$formattedDate} ({$formattedDuration})";
		}
		else
		{
			$result = Sharing\Helper::formatDate($event->getStart());
		}

		return $result;
	}
}