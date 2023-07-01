<?php

namespace Bitrix\Crm\Integration\Calendar;

use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Calendar\Sharing\Link\CrmDealLink;
use Bitrix\Calendar\Sharing\Link\CrmDealLinkMapper;
use Bitrix\Calendar\Sharing\Link\EventLink;
use Bitrix\Calendar\Sharing\Link\Factory;
use Bitrix\Calendar\Sharing\SharingConference;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;

class Helper
{
	private static ?Factory $linkFactory = null;
	private static ?self $instance = null;
	public const IS_SHARING_CRM_FEATURE_ENABLE = 'isSharingCrmFeatureEnable';

	public static function isSharingCrmAvaible(): bool
	{
		return true;
	}

	/**
	 * sends crm deal calendar sharing link to client
	 *
	 * @param int $entityId
	 * @param int $contactId
	 * @param int $contactTypeId
	 * @param string $channelId
	 * @param string $senderId
	 * @return Result
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function sendLinkToClient(
		int $entityId,
		int $contactId,
		int $contactTypeId,
		string $channelId,
		string $senderId
	): Result
	{
		$result = new Result();
		if (!$this->isAvailable())
		{
			$result->addError(new Error('Sharing is not available', 10010));

			return $result;
		}

		$contact = $this->getContactPhoneCommunications(
			$entityId,
			\CCrmOwnerType::Deal,
			$contactId,
			$contactTypeId,
		);

		if ($contact === null)
		{
			$result->addError(new Error('Contact not found', 10020));

			return $result;
		}

		$broker = Container::getInstance()->getEntityBroker(\CCrmOwnerType::Deal);
		if (!$broker)
		{
			$result->addError(new Error('Deal broker not found', 10030));

			return $result;
		}

		$deal = $broker->getById($entityId);
		if (!$deal)
		{
			$result->addError(new Error('Deal not found', 10040));

			return $result;
		}

		$ownerId = $deal->getAssignedById();

		$contactType = $contact['entityTypeId'];

		$factory = self::getLinkFactory();
		/** @var CrmDealLink $crmDealLink */
		$crmDealLink = $factory->getCrmDealLink($entityId, $ownerId, $contactId, $contactType);
		if (!$crmDealLink)
		{
			$crmDealLink = $factory->createCrmDealLink($ownerId, $entityId, $contactId, $contactType, $channelId, $senderId);
		}

		if ($crmDealLink->getSenderId() !== $senderId || $crmDealLink->getChannelId() !== $channelId)
		{
			$crmDealLink
				->setSenderId($senderId)
				->setChannelId($channelId)
			;

			(new CrmDealLinkMapper())->update($crmDealLink);
		}

		$result->setData(['linkHash' => $crmDealLink->getHash()]);

		Notification\Manager::getSenderInstance($crmDealLink)
			->setCrmDealLink($crmDealLink)
			->sendCrmSharingInvited()
		;

		return $result;
	}

	/**
	 * adds new calendar sharing entry in timeline
	 *
	 * @param string $linkHash
	 * @param string $eventType
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addTimelineEntry(string $linkHash, string $eventType): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		/** @var CrmDealLink $crmDealLink */
		$crmDealLink = self::getLinkFactory()->getLinkByHash($linkHash);

		if ($crmDealLink->getObjectType() !== \Bitrix\Calendar\Sharing\Link\Helper::CRM_DEAL_SHARING_TYPE)
		{
			return false;
		}

		$eventData = EventData::createFromCrmDealLink($crmDealLink, $eventType);

		if ($eventType === EventData::SHARING_ON_INVITATION_SENT && $crmDealLink->getContactId())
		{
			$contactCommunications = $this->getContactPhoneCommunications(
				$crmDealLink->getEntityId(),
				\CCrmOwnerType::Deal,
				$crmDealLink->getContactId(),
				$crmDealLink->getContactType(),
			);

			if ($contactCommunications && isset($contactCommunications['phones'][0]))
			{
				$eventData->setContactCommunication($contactCommunications['phones'][0]['valueFormatted']);
			}

			if ($crmDealLink->getChannelId())
			{
				$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $crmDealLink->getEntityId());
				$repo = ChannelRepository::create($entity);
				$channel = $repo->getById(SmsManager::getSenderCode(), $crmDealLink->getChannelId());

				if ($channel)
				{
					$eventData->setChannelName($channel->getName());
				}
			}
		}

		CalendarSharingTimeline::createTimelineEntry($eventData);

		return true;
	}

	/**
	 * gets phone communication of contact in entity
	 *
	 * @param int $entityId
	 * @param int $entityTypeId
	 * @param int $contactId
	 * @param int $contactTypeId
	 * @return mixed|null
	 */
	public function getContactPhoneCommunications(int $entityId, int $entityTypeId, int $contactId, int $contactTypeId)
	{
		$communications = SmsManager::getEntityPhoneCommunications($entityTypeId, $entityId);
		$contact = current(array_filter($communications, static function ($communication) use ($contactId, $contactTypeId) {
			return $communication['entityId'] === $contactId && $communication['entityTypeId'] === $contactTypeId;
		}));

		if (!$contact)
		{
			return null;
		}

		return $contact;
	}

	/**
	 * gets chat id of sharing conference by event id
	 *
	 * @param int $eventId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getConferenceChatId(int $eventId): ?array
	{
		$result = [];

		if (!$this->isAvailable())
		{
			return null;
		}

		/** @var EventLink $eventLink */
		$eventLink = self::getLinkFactory()->getEventLinkByEventId($eventId);
		if (!$eventLink)
		{
			return null;
		}

		$chatId = (new SharingConference($eventLink))->getConferenceChatId();
		if (!$chatId)
		{
			return null;
		}

		$result['chatId'] = $chatId;

		return $result;
	}

	/**
	 * cancels event participation
	 *
	 * @param int $eventId
	 * @return bool
	 * @throws LoaderException
	 */
	public function cancelMeeting(int $eventId): bool
	{
		if (!$this->isAvailable())
		{
			return false;
		}

		$event = \CCalendarEvent::GetList([
			'arFilter' => [
				'ID' => $eventId,
			],
			'fetchAttendees' => true,
			'checkPermissions' => false,
		]);

		$event = $event[0] ?? false;
		if (!$event || ($event['EVENT_TYPE'] ?? null) !== Dictionary::EVENT_TYPE['shared_crm'])
		{
			return false;
		}

		$attendees = $event['ATTENDEE_LIST'] ?? [];
		foreach ($attendees as $attendee)
		{
			if (($attendee['status'] ?? null) === Dictionary::MEETING_STATUS['Yes'])
			{
				\CCalendarEvent::SetMeetingStatus([
					'eventId' => $eventId,
					'userId' => $attendee['id'] ?? 0,
					'status' => 'N'
				]);
			}
		}

		return true;
	}

	/**
	 * completes crm deal calendar sharing activity
	 *
	 * @param int $activityId
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @param string $status
	 * @return bool
	 */
	public function completeActivityWithStatus(int $activityId, int $ownerTypeId, int $ownerId, string $status): bool
	{
		$activity = $this->loadActivity($activityId, $ownerTypeId, $ownerId);
		if (!$activity)
		{
			return false;
		}

		return (new ActivityHandler($activity, $ownerTypeId, $ownerId))
			->completeWithStatus($status)
		;
	}

	/**
	 * @return Helper
	 */
	public static function getInstance(): Helper
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return Factory
	 */
	private static function getLinkFactory(): Factory
	{
		if (!self::$linkFactory)
		{
			self::$linkFactory = new Factory();
		}

		return self::$linkFactory;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function isAvailable(): bool
	{
		if (!Loader::includeModule('calendar'))
		{
			return false;
		}

		if (Loader::includeModule('intranet') && !\Bitrix\Intranet\Util::isIntranetUser())
		{
			return false;
		}

		return true;
	}

	/**
	 * @param int $activityId
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 * @return array|mixed|null
	 */
	private function loadActivity(int $activityId, int $ownerTypeId, int $ownerId)
	{
		if (!$ownerId || !\CCrmOwnerType::IsDefined($ownerTypeId))
		{
			return null;
		}

		$activity = \CCrmActivity::GetByID($activityId);
		if (!$activity || $activity['PROVIDER_ID'] !== 'CRM_CALENDAR_SHARING')
		{
			return null;
		}

		return $activity;
	}
}