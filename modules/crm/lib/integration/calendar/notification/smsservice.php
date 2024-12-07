<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender;
use Bitrix\Calendar\Sharing;
use Bitrix\Main\Localization\Loc;

class SmsService extends AbstractService
{
	/**
	 * @param ItemIdentifier $entity
	 * @return bool
	 */
	public static function canSendMessage(ItemIdentifier $entity): bool
	{
		$repo = MessageSender\Channel\ChannelRepository::create($entity);

		$channels = $repo->getListBySender(SmsManager::getSenderCode());
		foreach ($channels as $channel)
		{
			if ($channel->checkChannel()->isSuccess())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $message
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function sendMessage(string $message): bool
	{
		$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $this->crmDealLink->getEntityId());
		$channel = $this->getEntityChannel($entity);
		if (is_null($channel))
		{
			return false;
		}

		$from = $this->getFromEntity($channel, $this->crmDealLink->getSenderId());
		if (!$from)
		{
			return false;
		}

		$to = $this->getToEntity($channel, $this->crmDealLink->getContactId(), $this->crmDealLink->getContactType());
		if (!$to)
		{
			return false;
		}

		return (new MessageSender\SendFacilitator\Sms($channel))
			->setFrom($from)
			->setTo($to)
			->setMessageBody($message)
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

		$message = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_INVITATION', [
			'#FIRST_NAME#' => $manager['name'],
			'#URL#' => Sharing\Helper::getShortUrl($this->crmDealLink->getUrl()),
		]);

		return $this->sendMessage($message);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingAutoAccepted(): bool
	{
		$message = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_AUTO_ACCEPTED', [
			'#EVENT_URL#' => Sharing\Helper::getShortUrl($this->eventLink->getUrl()),
		]);

		return $this->sendMessage($message);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingCancelled(): bool
	{
		$message = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_CANCELLED', [
			'#EVENT_URL#' => Sharing\Helper::getShortUrl($this->eventLink->getUrl()),
		]);

		return $this->sendMessage($message);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function sendCrmSharingEdited(): bool
	{
		$message = Loc::getMessage('CRM_CALENDAR_SHARING_EVENT_EDITED', [
			'#EVENT_URL#' => Sharing\Helper::getShortUrl($this->eventLink->getUrl()),
		]);

		return $this->sendMessage($message);
	}

	/**
	 * @param ItemIdentifier $entity
	 * @return MessageSender\Channel|null
	 */
	protected function getEntityChannel(ItemIdentifier $entity): ?MessageSender\Channel
	{
		$repo = MessageSender\Channel\ChannelRepository::create($entity);
		$channel = $repo->getById(SmsManager::getSenderCode(), $this->crmDealLink->getChannelId());
		if (is_null($channel))
		{
			return null;
		}

		return $channel;
	}
}