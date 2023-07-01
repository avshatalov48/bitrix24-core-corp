<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Calendar\Sharing\Link\CrmDealLink;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;

class Manager
{
	/**
	 * @param CrmDealLink $crmDealLink
	 * @return AbstractService
	 */
	public static function getSenderInstance(CrmDealLink $crmDealLink): AbstractService
	{
		if (
			$crmDealLink->getChannelId()
			&& $crmDealLink->getSenderId()
			&& $crmDealLink->getChannelId() !== NotificationsManager::getSenderCode()
		)
		{
			return new SmsService();
		}

		return new NotificationService();

	}

	/**
	 * @param ItemIdentifier $entity
	 * @return array
	 */
	public static function getCommunicationChannels(ItemIdentifier $entity): array
	{
		$result = [];
		$repo = Channel\ChannelRepository::create($entity);

		$channels = $repo->getListBySender(SmsManager::getSenderCode());

		foreach ($channels as $channel)
		{
			if ($channel->canSendMessage())
			{
				$fromList = [];
				foreach ($channel->getFromList() as $from)
				{
					$fromList[] = [
						'id' => $from->getId(),
						'name' => $from->getName(),
					];
				}

				$result[] = [
					'id' => $channel->getId(),
					'name' => $channel->getName(),
					'shortName' => $channel->getShortName(),
					'fromList' => $fromList,
				];
			}
		}

		$notificationChannel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		if ($notificationChannel && $notificationChannel->canSendMessage())
		{
			$fromList = [];

			foreach ($notificationChannel->getFromList() as $from)
			{
				$fromList[] = [
					'id' => $from->getId(),
					'name' => $from->getName(),
				];
			}

			$result[] = [
				'id' => $notificationChannel->getId(),
				'name' => $notificationChannel->getName(),
				'shortName' => $notificationChannel->getShortName(),
				'fromList' => $fromList,
			];
		}

		return $result;
	}

	/**
	 * @param ItemIdentifier $entity
	 * @return string|null
	 */
	public static function getOptimalChannelId(ItemIdentifier $entity): ?string
	{
		$repo = Channel\ChannelRepository::create($entity);
		$channel = $repo->getBestUsableBySender(SmsManager::getSenderCode());
		if ($channel)
		{
			return $channel->getId();
		}

		$channel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		if ($channel)
		{
			return $channel->getId();
		}

		return null;
	}
}