<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\MessageSender\SenderPicker;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Intranet\ContactCenter;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\Result;

class GoToChat extends Base
{
	public function bindClientAction(Factory $factory, Item $entity, int $clientId, int $clientTypeId): ?array
	{
		if (!Container::getInstance()->getUserPermissions()->canUpdateItem($entity))
		{
			$this->addError(new Error(Loc::getMessage('CRM_ACCESS_DENIED')));

			return null;
		}

		if ($clientTypeId === \CCrmOwnerType::Contact)
		{
			$contact = Container::getInstance()->getFactory(\CCrmOwnerType::Contact)->getItem($clientId);
			if (!$contact)
			{
				$this->addError(new Error(Loc::getMessage('CRM_INVITATION_WRONG_CONTACT')));

				return null;
			}

			$bindings = EntityBinding::prepareEntityBindings($clientTypeId, [$clientId]);
			$entity->bindContacts($bindings);
		}
		else if ($clientTypeId === \CCrmOwnerType::Company)
		{
			$company = Container::getInstance()->getFactory(\CCrmOwnerType::Company)->getItem($clientId);
			if (!$company)
			{
				$this->addError(new Error(Loc::getMessage('CRM_INVITATION_WRONG_COMPANY')));

				return null;
			}

			$entity->setCompanyId($clientId);
		}

		$saveResult = $factory->getUpdateOperation($entity)->launch();

		if ($saveResult->isSuccess())
		{
			$channels = $this->getChannels($entity->getEntityTypeId(), $entity->getId());

			return [
				'communications' => $this->getCommunications($entity->getEntityTypeId(), $entity->getId()),
				'channels' => $channels,
				'currentChannelId' => $this->getCurrentChannelId($channels),
			];
		}

		$errors = $saveResult->getErrorCollection()->getValues();
		if (empty($errors))
		{
			$this->addError(new Error(Loc::getMessage('CRM_INVITATION_CLIENT_BIND_ERROR')));
		}
		else
		{
			$this->addErrors($errors);
		}

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
		$lineId = (string)($params['lineId'] ?? '');

		$owner = new ItemIdentifier($ownerTypeId, $ownerId);

		$goToChat = new \Bitrix\Crm\Integration\ImOpenLines\GoToChat($senderType, $senderChannelId);
		$result = $goToChat->setOwner($owner)->send($from, $to, $lineId);

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

		return [
			'region' => Application::getInstance()->getLicense()->getRegion() ?? Context::getCurrent()->getLanguage(),
			'channels' => $channels,
			'currentSender' => $this->getCurrentSender(),
			'currentChannelId' => $currentChannelId,
			'communications' => $this->getCommunications($entityTypeId, $entityId),
			'openLineItems' => $this->getOpenLineItems(),
			'contactCenterUrl' => Container::getInstance()->getRouter()->getContactCenterUrl(),
		];
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
			if ($channel['default'])
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

		// @todo maybe leave only supported items?
		$itemsList = (new ContactCenter())->imopenlinesGetItems()->getData();

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
		$communications = SmsManager::getEntityPhoneCommunications($entityTypeId, $entityId);

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$item = $factory->getItem($entityId);

		$this->addPhonesFromContacts($communications, $item);
		$this->addPhonesFromCompanies($communications, $item);
		$this->addPhonesFromCompany($communications, $item);

		return $communications;
	}

	private function addPhonesFromContacts(array &$communications, ?Item $item): void
	{
		if (!$item || !$item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
		{
			return;
		}

		$contacts = $item->getContacts();
		foreach ($contacts as $contact)
		{
			$clientEntityTypeId = \CCrmOwnerType::Contact;
			$clientEntityId = $contact->getId();
			$canReadClient = Container::getInstance()->getUserPermissions()->checkReadPermissions($clientEntityTypeId, $clientEntityId);

			if (!$canReadClient)
			{
				continue;
			}

			$this->appendClientPhones($communications, \CCrmOwnerType::Contact, $contact);
		}
	}

	private function addPhonesFromCompanies(array &$communications, ?Item $item): void
	{
		if (
			!$item
			|| $item->getEntityTypeId() !== \CCrmOwnerType::Contact
			|| !$item->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS)
		)
		{
			return;
		}

		/** @var Item\Contact $item */
		$companies = $item->getCompanies();
		foreach ($companies as $company)
		{
			$clientEntityTypeId = \CCrmOwnerType::Company;
			$clientEntityId = $company->getId();
			$canReadClient = Container::getInstance()->getUserPermissions()->checkReadPermissions($clientEntityTypeId, $clientEntityId);

			if (!$canReadClient)
			{
				continue;
			}

			$this->appendClientPhones($communications, \CCrmOwnerType::Company, $company);
		}
	}

	private function addPhonesFromCompany(array &$communications, ?Item $item): void
	{
		if (!$item || !$item->hasField(Item::FIELD_NAME_COMPANY))
		{
			return;
		}

		$company = $item->getCompany();
		if($company)
		{
			$clientEntityTypeId = \CCrmOwnerType::Contact;
			$clientEntityId = $company->getId();
			$canReadCompany = Container::getInstance()->getUserPermissions()->checkReadPermissions($clientEntityTypeId, $clientEntityId);

			if($canReadCompany)
			{
				$this->appendClientPhones($communications, \CCrmOwnerType::Company, $company);
			}
		}
	}

	private function appendClientPhones(array &$communications, int $entityTypeId, $client): void
	{
		$clientId = $client->getId();
		$clientTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		$clientInfo = \CCrmEntitySelectorHelper::PrepareEntityInfo($clientTypeName, $clientId);

		if (isset($clientInfo['ADVANCED_INFO']['MULTI_FIELDS']))
		{
			$communication = [
				'entityId' => $clientId,
				'entityTypeId' => $entityTypeId,
				'entityTypeName' => $clientTypeName,
				'caption' => (
					$entityTypeId === \CCrmOwnerType::Contact
						? $client->getFormattedName()
						: $client->getTitle()
				),
			];

			$multiFieldEntityTypes = \CCrmFieldMulti::GetEntityTypes();
			foreach ($clientInfo['ADVANCED_INFO']['MULTI_FIELDS'] as $mf)
			{
				if ($mf['TYPE_ID'] !== Phone::ID)
				{
					continue;
				}

				$communication['phones'][] = [
					'value' => $mf['VALUE'],
					'valueFormatted' => Parser::getInstance()->parse($mf['VALUE'])->format(),
					'type' => $mf['VALUE_TYPE'],
					'typeLabel' => $multiFieldEntityTypes[Phone::ID][$mf['VALUE_TYPE']]['SHORT'],
					'id' => $mf['ENTITY_ID'],
				];
			}
			$communications[] = $communication;
		}
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
