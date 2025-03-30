<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Communications;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Market\Router;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\MessageSender\SenderPicker;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Intranet\ContactCenter;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class GoToChat extends Base
{
	public function bindClientAction(Factory $factory, Item $entity, int $clientId, int $clientTypeId): ?array
	{
		$clientIdentifier = new ItemIdentifier($clientTypeId, $clientId);
		$clientBinder = Container::getInstance()->getClientBinder();
		$result = $clientBinder->bind($factory, $entity, $clientIdentifier);

		if ($result->isSuccess())
		{
			$channels = $this->getChannels($entity->getEntityTypeId(), $entity->getId());

			return [
				'communications' => $this->getCommunications($entity->getEntityTypeId(), $entity->getId()),
				'channels' => $channels,
				'currentChannelId' => $this->getCurrentChannelId($channels),
			];
		}

		$this->addErrors($result->getErrors());

		return null;
	}

	public function sendAction(int $ownerTypeId, int $ownerId, array $params): Result
	{
		$result = new Result();

		if (!\Bitrix\Crm\Integration\ImOpenLines\GoToChat::isActive())
		{
			$result->addError(new Error('Feature is not available'));

			return $result;
		}

		$result = $this->validateEntity($ownerTypeId, $ownerId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$senderType = (string)($params['senderType'] ?? '');
		$senderChannelId = (string)($params['senderId'] ?? '');
		$from = (string)($params['from'] ?? '');
		$to = (int)($params['to'] ?? '');
		$lineId = (int)($params['lineId'] ?? 0);
		$connectorId = (string)($params['connectorId'] ?? '');

		$owner = new ItemIdentifier($ownerTypeId, $ownerId);

		$goToChat = (new \Bitrix\Crm\Integration\ImOpenLines\GoToChat($senderType, $senderChannelId))
			->setOwner($owner)
		;

		if (!empty($connectorId))
		{
			$goToChat->setConnectorId($connectorId);
		}

		$result = $goToChat->send($from, $to, $lineId);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result;
	}

	private function checkChannel(Channel $channel): Result
	{
		$sender = $channel->getSender();
		if ($sender::getSenderCode() === 'bitrix24')
		{
			$result = new Result();

			if ($sender::canUse())
			{
				return $result;
			}

			return $result->addError(new Error(Loc::getMessage('CRM_INVITATION_ERROR')));
		}

		return $channel->checkChannel();
	}

	public function getConfigAction(Item $entity): ?array
	{
		if (!Container::getInstance()->getUserPermissions()->canReadItem($entity))
		{
			$this->addError(new Error(Loc::getMessage('CRM_ACCESS_DENIED')));

			return null;
		}

		$entityTypeId = $entity->getEntityTypeId();
		$entityId = $entity->getId();

		$channels = $this->getChannels($entityTypeId, $entityId);
		$currentChannelId = $this->getCurrentChannelId($channels);

		$isBox = Crm::isBox();

		return [
			'region' => Application::getInstance()->getLicense()->getRegion() ?? Context::getCurrent()->getLanguage(),
			'channels' => $channels,
			'currentSender' => $this->getCurrentSender(),
			'currentChannelId' => $currentChannelId,
			'communications' => $this->getCommunications($entityTypeId, $entityId),
			'openLineItems' => $this->getOpenLineItems(),
			'contactCenterUrl' => Container::getInstance()->getRouter()->getContactCenterUrl(), // @todo for crmmobile
			'marketplaceUrl' => Router::getBasePath() . 'category/crm_robot_sms/',
			'hasClients' => $this->hasClients($entity),
			'services' => [
				'telegrambot' => true,
				'ru-whatsapp' => !$isBox,
				'whatsapp' => !$isBox,
			],
		];
	}

	private function hasClients(Item $entity): bool
	{
		if (
			$entity->hasField(Item::FIELD_NAME_CONTACT_BINDINGS)
			&& !empty($entity->getContactIds())
		)
		{
			return true;
		}

		if (
			$entity->getEntityTypeId() === \CCrmOwnerType::Contact
			|| $entity->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS)
		)
		{
			if (!empty($entity->getCompanies()))
			{
				return true;
			}
		}

		if (
			$entity->hasField(Item::FIELD_NAME_COMPANY)
			&& $entity->getCompanyId() > 0
		)
		{
			return true;
		}

		return false;
	}

	private function getChannels(int $entityTypeId, int $entityId): array
	{
		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);

		$availableChannels = $this->getAvailableChannels($itemIdentifier);

		$currentSender = $this->getCurrentSender();
		$currentChannelId = null;

		$data = [];
		/** @var Channel $channel */
		foreach ($availableChannels as $channel)
		{
			if (
				$channel->getSender() !== NotificationsManager::class
				&& $channel->getSender() !== SmsManager::class
			)
			{
				continue;
			}

			$isDefault = ($channel->isDefault() && !$currentChannelId);
			$id = $channel->getId();

			$senderCode = $channel->getSender()::getSenderCode();
			$fromList = $this->getPreparedFromList($channel);

			$canUse = (
				($senderCode === 'bitrix24' && $channel->getSender()::canUse())
				|| ($senderCode !== 'bitrix24' && $channel->canSendMessage())
			);

			if ($canUse && $isDefault && $currentSender === $senderCode)
			{
				$currentChannelId = $id;
			}

			$data[] = [
				'id' => $id,
				'default' => $isDefault,
				'fromList' => $fromList,
				'toList' => $this->getPreparedToList($channel),
				'name' => $channel->getName(),
				'shortName' => $channel->getShortName(),
				'canUse' => $canUse,
			];
		}

		return $data;
	}

	private function getCurrentChannelId(array $channels): ?string
	{
		foreach ($channels as $channel)
		{
			if ($channel['default'] && $channel['canUse'])
			{
				return $channel['id'];
			}
		}

		return $this->getFirstAvailableChannelId($channels);
	}

	private function getOpenLineItems(): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		$filter = [
			'PRESERVE_NOTIFICATIONS_CONNECTOR' => Crm::isWhatsAppGoToChatEnabled(),
		];
		$itemsList = (new ContactCenter())->imopenlinesGetItems($filter)->getData();

		$result = [];
		foreach ($itemsList as $itemCode => $item)
		{
			$result[$itemCode] = [
				'selected' => $item['SELECTED'],
				'url' => $item['LINK'] ?? '',
				'name' => $item['NAME'],
			];
		}

		return $result;
	}

	private function getAvailableChannels(ItemIdentifier $itemIdentifier): array
	{
		$repo = ChannelRepository::create($itemIdentifier);
		$channels = $repo->getAll();

		return array_values($channels);
	}

	private function getPreparedFromList(Channel $channel): array
	{
		$fromList = $channel->getFromList();

		$result = [];
		foreach ($fromList as $item)
		{
			$result[] = [
				'id' => $item->getId(),
				'name' => $item->getName(),
				'description' => $item->getDescription(),
				'default' => $item->isDefault(),
			];
		}

		return $result;
	}

	private function getFirstAvailableChannelId(array $channels): ?string
	{
		foreach ($channels as $channel)
		{
			if ($channel['canUse'])
			{
				return $channel['id'];
			}
		}

		return null;
	}

	private function getPreparedToList(Channel $channel): array
	{
		$toList = $channel->getToList();

		$result = [];
		foreach ($toList as $item)
		{
			$result[] = [
				'address' => $item->getAddress(),
			];
		}

		return $result;
	}

	private function getCurrentSender(): ?string
	{
		$currentSender = SenderPicker::getCurrentSender();

		return ($currentSender ? $currentSender::getSenderCode() : null);
	}

	private function getCommunications(int $entityTypeId, int $entityId): array
	{
		return (new Communications($entityTypeId, $entityId))->get();
	}

	private function validateEntity(int $entityTypeId, int $entityId): Result
	{
		$result = new Result();
		if (!\CCrmOwnerType::IsDefined($entityTypeId))
		{
			$result->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return $result;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$result->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return $result;
		}

		$item = $factory->getItem($entityId);
		if($item === null)
		{
			$result->addError(ErrorCode::getNotFoundError());
		}

		if (!Container::getInstance()->getUserPermissions()->canReadItem($item))
		{
			$result->addError(ErrorCode::getAccessDeniedError());
		}

		return $result;
	}
}
