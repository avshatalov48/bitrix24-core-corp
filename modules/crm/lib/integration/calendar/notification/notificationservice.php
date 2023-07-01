<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\MessageSender;
use Bitrix\Calendar\Sharing;
use Bitrix\Crm\ItemIdentifier;

class NotificationService extends AbstractService
{
	private const TEMPLATE_SHARING_EVENT_INVITATION = 'SHARING_EVENT_INVITATION';
	private const TEMPLATE_SHARING_EVENT_AUTO_ACCEPTED = 'SHARING_EVENT_ACCEPTED_2';
	private const TEMPLATE_SHARING_EVENT_CANCELLED_LINK_ACTIVE = 'SHARING_EVENT_CANCELLED_1';
	private const TEMPLATE_SHARING_EVENT_CANCELLED = 'SHARING_EVENT_CANCELLED_2';

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

		return $channel->checkChannel()->isSuccess();
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
}