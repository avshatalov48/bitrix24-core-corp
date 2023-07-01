<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
use Bitrix\Crm\MessageSender\SenderPicker;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class GoToChat extends Base
{
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

		if (!\Bitrix\Crm\Integration\ImOpenLines\GoToChat::isValidLineId($lineId))
		{
			$this->addError(new Error(Loc::getMessage('CRM_INVITATION_WRONG_LINE')));

			return $result;
		}

		$channel =
			\Bitrix\Crm\MessageSender\Channel\ChannelRepository::create(new ItemIdentifier($ownerTypeId, $ownerId))
				->getById($senderType, $senderChannelId)
		;
		if (!$channel)
		{
			$result->addError(new Error(Loc::getMessage('CRM_INVITATION_CHANNEL_NOT_FOUND')));

			return $result;
		}

		$checkChannelResult = $this->checkChannel($channel);
		if (!$checkChannelResult->isSuccess())
		{
			$result->addErrors($checkChannelResult->getErrors());

			return $result;
		}

		$fromCorrespondent = null;
		$toCorrespondent = null;

		foreach ($channel->getFromList() as $fromListItem)
		{
			if ($fromListItem->getId() === $from)
			{
				$fromCorrespondent = $fromListItem;
				break;
			}
		}

		if (!$fromCorrespondent)
		{
			$this->addError(new Error(Loc::getMessage('CRM_INVITATION_WRONG_FROM')));

			return $result;
		}

		foreach ($channel->getToList() as $toListItem)
		{
			if ($toListItem->getAddress()->getId() === $to)
			{
				$toCorrespondent = $toListItem;
				break;
			}
		}

		if (!$toCorrespondent)
		{
			$result->addError(new Error(Loc::getMessage('CRM_INVITATION_WRONG_TO')));

			return $result;
		}

		$result = (new \Bitrix\Crm\Integration\ImOpenLines\GoToChat())->send(
			$channel,
			$lineId,
			$fromCorrespondent,
			$toCorrespondent
		);

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

	public function getConfigAction(int $entityTypeId, int $entityId): array
	{
		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);

		$availableChannels = $this->getAvailableChannels($itemIdentifier);

		$currentSender = $this->getCurrentSender();
		$currentChannelId = null;

		$data = [];
		/** @var Channel $channel */
		foreach ($availableChannels as $channel)
		{
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

		if ($currentChannelId === null)
		{
			$currentChannelId = $this->getFirstAvailableChannelId($data);
		}

		return [
			'region' => (Application::getInstance()->getLicense()->getRegion() ?? 'ru'),
			'channels' => $data,
			'currentSender' => $this->getCurrentSender(),
			'currentChannelId' => $currentChannelId,

			// @todo check this for all entity types
			'communications' => $this->getCommunications($entityTypeId, $entityId),
			'contactCenterUrl' => (
				Loader::includeModule('bitrix24')
					? '/contact_center/'
					: '/services/contact_center/'
			),
		];
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

		if (empty($communications))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			$item = $factory->getItem($entityId);

			if ($item && $item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
			{
				$contacts = $item->getContacts();
				foreach ($contacts as $contact)
				{
					$communications[] = [
						'entityId' => $contact->getId(),
						'entityTypeId' => \CCrmOwnerType::Contact,
						'caption' => $contact->getFormattedName(),
					];
				}

				$company = $item->getCompany();
				if ($company)
				{
					$communications[] = [
						'entityId' => $company->getId(),
						'entityTypeId' => \CCrmOwnerType::Company,
						'caption' => $company->getTitle(),
					];
				}
			}
		}

		return $communications;
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

		if(is_null($factory->getItem($entityId)))
		{
			$result->addError(ErrorCode::getNotFoundError());
		}

		return $result;
	}
}
