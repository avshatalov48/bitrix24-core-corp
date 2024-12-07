<?php

namespace Bitrix\Crm\Integration\Calendar\Notification;

use Bitrix\Calendar\Sharing\Link\CrmDealLink;
use Bitrix\Crm\Communication;
use Bitrix\Crm\Integration\MailManager;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\Multifield;

class Manager
{
	/**
	 * @param CrmDealLink $crmDealLink
	 * @return AbstractService
	 */
	public static function getSenderInstance(CrmDealLink $crmDealLink): AbstractService
	{
		$entity = new ItemIdentifier(\CCrmOwnerType::Deal, $crmDealLink->getEntityId());
		$repo = ChannelRepository::create($entity);

		if ($repo->getById(MailManager::getSenderCode(), $crmDealLink->getChannelId()) !== null)
		{
			return new MailService();
		}

		if ($repo->getById(SmsManager::getSenderCode(), $crmDealLink->getChannelId()) !== null)
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

		if (MailManager::canUse())
		{
			$channels = $repo->getListBySender(MailManager::getSenderCode());
			foreach ($channels as $channel)
			{
				$result[] = self::getChannelArray($channel, Communication\Type::EMAIL_NAME);
			}
		}

		$channels = $repo->getListBySender(SmsManager::getSenderCode());
		foreach ($channels as $channel)
		{
			if ($channel->checkChannel()->isSuccess())
			{
				$result[] = self::getChannelArray($channel, Communication\Type::PHONE_NAME);
			}
		}

		$notificationChannel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		if ($notificationChannel && NotificationsManager::canUse())
		{
			$result[] = self::getChannelArray($notificationChannel, Communication\Type::PHONE_NAME);
		}

		return $result;
	}

	public static function getContacts(ItemIdentifier $entity): array
	{
		return self::convertToListToArray(Channel\ChannelRepository::create($entity)->getToList());
	}

	protected static function getChannelArray(Channel $channel, string $typeId): array
	{
		$fromList = [];
		foreach ($channel->getFromList() as $from)
		{
			$fromList[] = [
				'id' => $from->getId(),
				'name' => $from->getName(),
			];
		}

		$toList = self::convertToListToArray($channel->getToList());

		return [
			'id' => $channel->getId(),
			'typeId' => $typeId,
			'name' => $channel->getName(),
			'shortName' => $channel->getShortName(),
			'fromList' => $fromList,
			'contacts' => $toList,
		];
	}

	protected static function convertToListToArray(array $correspondents): array
	{
		$toList = [];

		foreach ($correspondents as $to)
		{
			$entityId = $to->getAddressSource()->getEntityId();
			$entityTypeId = $to->getAddressSource()->getEntityTypeId();
			$typeId = $to->getAddress()->getTypeId();
			$caption = \CCrmOwnerType::GetCaption($entityTypeId, $entityId);

			$multiFieldEntityTypes = \CCrmFieldMulti::GetEntityTypes();
			$toList[] = [
				'id' => $to->getAddress()->getId(),
				'entityId' => $to->getAddressSource()->getEntityId(),
				'entityTypeId' => $to->getAddressSource()->getEntityTypeId(),
				'name' => $caption,
				'value' => $to->getAddress()->getValue(),
				'valueType' => $to->getAddress()->getValueType(),
				'typeId' => $typeId,
				'valueTypeLabel' => $multiFieldEntityTypes[$typeId][$to->getAddress()->getValueType()]['SHORT'],
			];
		}

		return $toList;
	}

	/**
	 * @param ItemIdentifier $entity
	 * @return string|null
	 */
	public static function getOptimalChannelId(ItemIdentifier $entity, int $userId): ?string
	{
		$repo = Channel\ChannelRepository::create($entity);

		$channel = $repo->getBestUsableBySender(SmsManager::getSenderCode());
		if ($channel)
		{
			return $channel->getId();
		}

		$channel = $repo->getBestUsableBySender(MailManager::getSenderCode());
		if ($channel)
		{
			return $channel->getId();
		}

		$channel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		if ($channel)
		{
			return $channel->getId();
		}

		$channels = MailManager::getChannelsList([Multifield\Type\Email::ID], $userId);
		if (!empty($channels) && $channels[0])
		{
			return $channels[0]->getId();
		}

		return null;
	}
}