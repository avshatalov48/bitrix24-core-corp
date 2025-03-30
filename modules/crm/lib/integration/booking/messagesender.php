<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\Client;
use Bitrix\Booking\Entity\Message\MessageStatus;
use Bitrix\Booking\Entity\Message\MessageTemplateBased;
use Bitrix\Booking\Provider\NotificationsAvailabilityProvider;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\MessageSender\Channel\Correspondents\To;
use Bitrix\Crm\Multifield\Value;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use CCrmOwnerType;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\SendFacilitator;
use Bitrix\Notifications;
use Bitrix\Crm\Multifield\Type\Phone;

class MessageSender implements \Bitrix\Booking\Interfaces\MessageSender
{
	public function getModuleId(): string
	{
		return 'crm';
	}

	public function getCode(): string
	{
		return NotificationsManager::getSenderCode();
	}

	public function createMessage(): MessageTemplateBased
	{
		return new MessageTemplateBased();
	}

	public function send(Booking $booking, $message): Result
	{
		if (!$message instanceof MessageTemplateBased)
		{
			throw new ArgumentException('Message should be instance of MessageTemplateBased');
		}

		$result = new Result();

		$primaryClient = $booking->getPrimaryClient();
		if (!$primaryClient)
		{
			return $result->addError(new Error('Primary client has not been found'));
		}

		$senderCode = $channelId = NotificationsManager::getSenderCode();

		$channelItemIdentifier = self::createItemIdentifierForChannel(
			$primaryClient,
			DataProvider::getDealFromExternalDataCollection($booking->getExternalDataCollection())
		);
		$channel = ChannelRepository::create($channelItemIdentifier)->getById($senderCode, $channelId);

		if (!$channel)
		{
			return $result->addError(new Error('Channel has not been found'));
		}

		$facilitator = (new SendFacilitator\Notifications($channel))
			->setTo($this->makeTo($channelItemIdentifier, $primaryClient))
			->setTemplateCode($message->getTemplateCode())
			->setPlaceholders($message->getPlaceholders())
		;

		return $facilitator->send();
	}

	private function makeTo(ItemIdentifier $rootSource, Client $primaryClient): To
	{
		$defaultTo = new To(
			$rootSource,
			new ItemIdentifier(
				CCrmOwnerType::ResolveID($primaryClient->getType()?->getCode()),
				$primaryClient->getId()
			),
			new Value()
		);

		$primaryClientTypeCode = $primaryClient->getType()?->getCode();
		if (!$primaryClientTypeCode)
		{
			return $defaultTo;
		}

		$factory = Container::getInstance()->getFactory(
			\CCrmOwnerType::ResolveID($primaryClientTypeCode)
		);
		if (!$factory)
		{
			return $defaultTo;
		}

		$item = $factory->getItem($primaryClient->getId());
		if (!$item)
		{
			return $defaultTo;
		}

		$values = $item->getFm()->filterByType(Phone::ID)->getAll();
		if (empty($values))
		{
			return $defaultTo;
		}

		return new To(
			$rootSource,
			new ItemIdentifier(
				CCrmOwnerType::ResolveID($primaryClient->getType()?->getCode()),
				$primaryClient->getId()
			),
			current($values)
		);
	}

	private function createItemIdentifierForChannel(
		Client $primaryClient,
		Deal|null $deal
	): ItemIdentifier
	{
		if ($deal)
		{
			if (
				(
					$primaryClient->getType()?->getCode() === CCrmOwnerType::CompanyName
					&& $deal->getCompany()?->getId() === $primaryClient->getId()
				)
				|| (
					$primaryClient->getType()?->getCode() === CCrmOwnerType::ContactName
					&& in_array($primaryClient->getId(), $deal->getContactIds(), true)
				)
			)
			{
				return new ItemIdentifier(
					CCrmOwnerType::Deal,
					$deal->getId()
				);
			}
		}

		return new ItemIdentifier(
			CCrmOwnerType::ResolveID($primaryClient->getType()?->getCode()),
			$primaryClient->getId()
		);
	}

	public function getMessageStatus(int $messageId): MessageStatus
	{
		$messageInfo = NotificationsManager::getMessageByInfoId($messageId);
		if (!isset($messageInfo['MESSAGE']['STATUS']))
		{
			return MessageStatus::sending();
		}

		$status = $messageInfo['MESSAGE']['STATUS'];

		$sentStatuses = [
			Notifications\MessageStatus::ENQUEUED_LOCAL,
			Notifications\MessageStatus::ENQUEUED,
			Notifications\MessageStatus::SENT,
			Notifications\MessageStatus::IN_DELIVERY,
		];
		if (in_array($messageInfo['MESSAGE']['STATUS'], $sentStatuses, true))
		{
			return MessageStatus::sent();
		}

		if ($status === Notifications\MessageStatus::DELIVERED)
		{
			return MessageStatus::delivered();
		}

		if ($status === Notifications\MessageStatus::READ)
		{
			return MessageStatus::read();
		}

		return MessageStatus::failed();
	}

	public function canUse(): bool
	{
		return NotificationsAvailabilityProvider::isAvailable() && NotificationsManager::canUse();
	}
}
