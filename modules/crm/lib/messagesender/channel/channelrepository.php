<?php

namespace Bitrix\Crm\MessageSender\Channel;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\Correspondents\To;
use Bitrix\Crm\MessageSender\SenderRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;

final class ChannelRepository
{
	/** @var Array<string, To[]> */
	private array $toListByType;
	private int $userId;

	/** @var Channel[]|null */
	private ?array $channels = null;

	private function __construct(array $toListByType, int $userId)
	{
		$this->toListByType = $toListByType;
		$this->userId = $userId;
	}

	private function __clone()
	{
	}

	public static function create(ItemIdentifier $source, ?int $userId = null): self
	{
		$toListByType = self::extractToList($source);
		$userId ??= Container::getInstance()->getContext()->getUserId();

		return new self($toListByType, $userId);
	}

	/**
	 * @param ItemIdentifier $source
	 * @return Array<string, To[]>
	 */
	private static function extractToList(ItemIdentifier $source): array
	{
		$holders = self::getCommunicationsHolders($source);
		if (empty($holders))
		{
			return [];
		}

		$storage = Container::getInstance()->getMultifieldStorage();

		$toListByType = [];
		foreach ($holders as $entityTypeId => $multipleEntityIds)
		{
			$multifieldsForMultipleOwners = $storage->getForMultipleOwners($entityTypeId, $multipleEntityIds);

			foreach ($multifieldsForMultipleOwners as $entityId => $multifields)
			{
				foreach ($multifields as $value)
				{
					$toListByType[$value->getTypeId()][] = new To(
						$source,
						new ItemIdentifier($entityTypeId, $entityId),
						$value,
					);
				}
			}
		}

		return $toListByType;
	}

	/**
	 * @param ItemIdentifier $source
	 * @return Array<int, int[]> entityTypeId => itemIds
	 */
	private static function getCommunicationsHolders(ItemIdentifier $source): array
	{
		if ($source->getEntityTypeId() === \CCrmOwnerType::Order)
		{
			return self::getCommunicationsHoldersForOrder($source);
		}

		if (!\CCrmOwnerType::isUseFactoryBasedApproach($source->getEntityTypeId()))
		{
			return [];
		}

		$sourceItem = self::fetchItem($source->getEntityTypeId(), $source->getEntityId());
		if (!$sourceItem)
		{
			return [];
		}

		$result = [
			\CCrmOwnerType::Contact => [],
			\CCrmOwnerType::Company => [],
			\CCrmOwnerType::Lead => [],
		];

		if (isset($result[$source->getEntityTypeId()]))
		{
			$result[$source->getEntityTypeId()][$source->getEntityId()] = $source->getEntityId();
		}

		if ($sourceItem->hasField(Item::FIELD_NAME_COMPANY_ID) && $sourceItem->getCompanyId() > 0)
		{
			$result[\CCrmOwnerType::Company][$sourceItem->getCompanyId()] = $sourceItem->getCompanyId();
		}

		if (
			$sourceItem->hasField(Item\Contact::FIELD_NAME_COMPANY_IDS)
			&& !empty($sourceItem->get(Item\Contact::FIELD_NAME_COMPANY_IDS))
		)
		{
			foreach ($sourceItem->get(Item\Contact::FIELD_NAME_COMPANY_IDS) as $companyId)
			{
				if ((int)$companyId > 0)
				{
					$result[\CCrmOwnerType::Company][$companyId] = (int)$companyId;
				}
			}
		}

		if ($sourceItem->hasField(Item::FIELD_NAME_CONTACT_ID) && $sourceItem->getContactId() > 0)
		{
			$result[\CCrmOwnerType::Contact][$sourceItem->getContactId()] = $sourceItem->getContactId();
		}

		if ($sourceItem->hasField(Item::FIELD_NAME_CONTACT_IDS) && !empty($sourceItem->getContactIds()))
		{
			foreach ($sourceItem->getContactIds() as $contactId)
			{
				if ((int)$contactId > 0)
				{
					$result[\CCrmOwnerType::Contact][$contactId] = (int)$contactId;
				}
			}
		}

		return $result;
	}

	/**
	 * @param ItemIdentifier $source
	 * @return Array<int, int[]> entityTypeId => itemIds
	 * @throws ArgumentException
	 */
	private static function getCommunicationsHoldersForOrder(ItemIdentifier $source): array
	{
		if ($source->getEntityTypeId() !== \CCrmOwnerType::Order)
		{
			throw new ArgumentException('Cant process anything but order in this method');
		}

		$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList([
			'select' => ['ENTITY_ID', 'ENTITY_TYPE_ID'],
			'filter' => [
				'=ORDER_ID' => $source->getEntityId(),
				'IS_PRIMARY' => 'Y'
			]
		]);

		$result = [
			\CCrmOwnerType::Contact => [],
			\CCrmOwnerType::Company => [],
		];

		while ($entity = $dbRes->fetch())
		{
			$entityTypeId = (int)($entity['ENTITY_TYPE_ID'] ?? 0);
			if (!isset($result[$entityTypeId]))
			{
				continue;
			}

			$entityId = (int)($entity['ENTITY_ID'] ?? 0);
			if ($entityId <= 0)
			{
				continue;
			}

			$result[$entityTypeId][$entityId] = $entityId;
		}

		return $result;
	}

	private static function fetchItem(int $entityTypeId, int $id): ?Item
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			return null;
		}

		$possibleSelect = [
			Item::FIELD_NAME_ID,
			Item::FIELD_NAME_CONTACT_ID,
			Item::FIELD_NAME_CONTACT_IDS,
			Item::FIELD_NAME_COMPANY_ID,
			Item\Contact::FIELD_NAME_COMPANY_IDS,
		];

		$filteredSelect = array_filter($possibleSelect, $factory->isFieldExists(...));
		if (empty($filteredSelect))
		{
			throw new InvalidOperationException('We have no fields to select, it should be impossible');
		}

		$items = $factory->getItems([
			'select' => $filteredSelect,
			'filter' => [
				'=ID' => $id,
			],
		]);

		return array_shift($items);
	}

	public function getAll(): array
	{
		if (is_array($this->channels))
		{
			return $this->channels;
		}

		$this->channels = [];

		$senders = SenderRepository::getAllImplementationsList();
		foreach ($senders as $sender)
		{
			foreach ($sender::getChannelsList($this->toListByType, $this->userId) as $channel)
			{
				$this->channels[] = $channel;
			}
		}

		return $this->channels;
	}

	/**
	 * @param string $senderCode
	 * @return Channel[]
	 */
	public function getListBySender(string $senderCode): array
	{
		$result = [];
		foreach ($this->getAll() as $channel)
		{
			if ($channel->getSender()::getSenderCode() === $senderCode)
			{
				$result[] = $channel;
			}
		}

		return $result;
	}

	public function getById(string $senderCode, string $channelId): ?Channel
	{
		foreach ($this->getAll() as $channel)
		{
			if ($channel->getId() === $channelId && $channel->getSender()::getSenderCode() === $senderCode)
			{
				return $channel;
			}
		}

		return null;
	}

	public function getDefaultForSender(string $senderCode): ?Channel
	{
		foreach ($this->getListBySender($senderCode) as $channel)
		{
			if ($channel->isDefault())
			{
				return $channel;
			}
		}

		return null;
	}

	public function getBestUsableBySender(string $senderCode): ?Channel
	{
		$default = $this->getDefaultForSender($senderCode);
		if ($default && $default->canSendMessage())
		{
			return $default;
		}

		foreach ($this->getListBySender($senderCode) as $channel)
		{
			if ($channel->canSendMessage())
			{
				return $channel;
			}
		}

		return null;
	}

	public function getToList(): array
	{
		return array_merge(...array_values($this->toListByType));
	}
}
