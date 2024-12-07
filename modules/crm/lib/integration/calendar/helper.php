<?php

namespace Bitrix\Crm\Integration\Calendar;

use Bitrix\Calendar\Core\Event\Tools\Dictionary;
use Bitrix\Calendar\Sharing\Link\CrmDealLink;
use Bitrix\Calendar\Sharing\Link\CrmDealLinkMapper;
use Bitrix\Calendar\Sharing\Link\EventLink;
use Bitrix\Calendar\Sharing\Link\Factory;
use Bitrix\Calendar\Sharing\SharingConference;
use Bitrix\Calendar\Sharing;
use Bitrix\Crm\Integration\MailManager;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\MessageSender\Channel\Correspondents\To;
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
		string $senderId,
		array $ruleArray,
		array $memberIds,
	): Result
	{
		$result = new Result();
		if (!$this->isAvailable())
		{
			$result->addError(new Error('Sharing is not available', 10010));

			return $result;
		}

		$hasContact = $this->hasContact(
			new ItemIdentifier(\CCrmOwnerType::Deal, $entityId),
			$channelId,
			$contactId,
			$contactTypeId,
		);

		if (!$hasContact)
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

		$factory = self::getLinkFactory();
		/** @var CrmDealLink $crmDealLink */
		$crmDealLink = $factory->getCrmDealLink($entityId, $ownerId, $memberIds, $contactId, $contactTypeId);
		if (!$crmDealLink)
		{
			$crmDealLink = $factory->createCrmDealLink($ownerId, $entityId, $memberIds, $contactId, $contactTypeId, $channelId, $senderId);
		}

		$linkObjectRule = (new Sharing\Link\Rule\Factory())->getLinkObjectRuleByLink($crmDealLink);
		if (!is_null($linkObjectRule))
		{
			$sharingRuleMapper = new Sharing\Link\Rule\Mapper();
			$rule = $sharingRuleMapper->buildRuleFromArray($ruleArray);
			$sharingRuleMapper->saveForLinkObject($rule, $linkObjectRule);
			$crmDealLink->setSharingRule($rule);
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

		Sharing\Analytics::getInstance()->sendLinkSent($crmDealLink);

		return $result;
	}

	public function generateJointLink(int $entityId, int $ownerId, array $memberIds): Result
	{
		$result = new Result();
		if (!$this->isAvailable())
		{
			$result->addError(new Error('Sharing is not available', 10010));

			return $result;
		}

		$crmDealLink = (new Factory())->getCrmDealLink($entityId, $ownerId, $memberIds);
		if ($crmDealLink === null)
		{
			$crmDealLink = (new Factory())->createCrmDealLink($ownerId, $entityId, $memberIds);
		}

		$result->setData([
			'url' => Sharing\Helper::getShortUrl($crmDealLink->getUrl()),
			'hash' => $crmDealLink->getHash(),
		]);

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

		if (
			$eventType === EventData::SHARING_ON_INVITATION_SENT
			&& $crmDealLink->getContactId()
			&& $crmDealLink->getChannelId()
		)
		{
			$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $crmDealLink->getEntityId());

			$channel = $this->getChannelById($entity, $crmDealLink->getChannelId());
			if ($channel !== null)
			{
				$eventData->setChannelName($channel->getName());
			}

			$contact = $this->getContactFromChannel($channel, $crmDealLink->getContactId(), $crmDealLink->getContactType());
			if ($contact !== null)
			{
				$eventData->setContactCommunication($contact->getAddress()->getValue());
			}
		}

		CalendarSharingTimeline::createTimelineEntry($eventData);

		return true;
	}

	/**
	 * Returns whether channel has contact or not
	 *
	 * @param ItemIdentifier $entity
	 * @param string $channelId
	 * @param int $contactId
	 * @param int $contactTypeId
	 * @return boolean
	 */
	public function hasContact(ItemIdentifier $entity, string $channelId, int $contactId, int $contactTypeId): bool
	{
		$channel = $this->getChannelById($entity, $channelId);

		return $this->getContactFromChannel($channel, $contactId, $contactTypeId) !== null;
	}

	public function getChannelById(ItemIdentifier $entity, string $channelId): ?Channel
	{
		$repo = ChannelRepository::create($entity);

		$smsChannel = $repo->getById(SmsManager::getSenderCode(), $channelId);
		$mailChannel = $repo->getById(MailManager::getSenderCode(), $channelId);
		$channel = $smsChannel ?? $mailChannel;

		return $channel ?? $repo->getBestUsableBySender(NotificationsManager::getSenderCode());
	}

	public function getContactFromChannel(?Channel $channel, int $contactId, int $contactTypeId): ?To
	{
		if (is_null($channel))
		{
			return null;
		}

		return current(array_filter($channel->getToList(), static fn ($communication) =>
			$communication->getAddressSource()->getEntityId() === $contactId
			&& $communication->getAddressSource()->getEntityTypeId() === $contactTypeId
		)) ?: null;
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

		/** @var \Bitrix\Calendar\Core\Event\Event $event */
		$event = (new \Bitrix\Calendar\Core\Mappers\Event())->getById($eventId);
		if (!$event instanceof \Bitrix\Calendar\Core\Event\Event || $event->getSpecialLabel() !== Dictionary::EVENT_TYPE['shared_crm'])
		{
			return false;
		}

		(new \Bitrix\Calendar\Core\Mappers\Event())->delete($event);

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
