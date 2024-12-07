<?php

namespace Bitrix\Crm\Summary;

use Bitrix\Crm\Activity\Provider\WebForm;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integration\AI\JobRepository;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;

class SummaryFactory
{
	private const CLIENT_SUMMARY_SUPPORTED_ENTITY_TYPES = [\CCrmOwnerType::Contact, \CCrmOwnerType::Company];

	public function getClientSummary(
		ItemIdentifier $client,
		int $summaryEntityTypeId = \CCrmOwnerType::Deal,
	): ClientSummary
	{
		if (!in_array($client->getEntityTypeId(), self::CLIENT_SUMMARY_SUPPORTED_ENTITY_TYPES, true))
		{
			throw new NotSupportedException(
				'Cant create client summary for client '
				. $client
				. '. Only supported types are: ' . implode(', ', self::CLIENT_SUMMARY_SUPPORTED_ENTITY_TYPES)
			);
		}

		$factory = Container::getInstance()->getFactory($summaryEntityTypeId);
		if (!$factory)
		{
			throw new ObjectNotFoundException('Could not find factory for ' . $summaryEntityTypeId);
		}
		if (
			!$factory->isStagesEnabled()
			|| !$factory->isLinkWithProductsEnabled()
			|| !$factory->isClientEnabled()
		)
		{
			throw new InvalidOperationException('Summary only possible by items that have stages, products and clients');
		}

		$items = $this->getClientItems($client, $factory);

		/** @var Item|false $latestItem */
		$latestItem = reset($items);

		$summarizeCallResult = null;
		$latestWebformId = null;
		if ($latestItem)
		{
			$latestCallActivityId = ActivityTable::query()
				->setSelect(['ID'])
				->where('OWNER_TYPE_ID', $latestItem->getEntityTypeId())
				->where('OWNER_ID', $latestItem->getId())
				->where('PROVIDER_ID', \Bitrix\Crm\Activity\Provider\Call::ACTIVITY_PROVIDER_ID)
				->whereNotNull('ORIGIN_ID')
				->addOrder('CREATED', 'DESC')
				->setLimit(1)
				->fetchObject()
				?->getId()
			;

			if ($latestCallActivityId > 0)
			{
				$summarizeCallResult =
					JobRepository::getInstance()->getSummarizeCallTranscriptionResultByActivity($latestCallActivityId)
				;
			}

			$latestWebformId = ActivityTable::query()
				->setSelect(['ID'])
				->where('OWNER_TYPE_ID', $latestItem->getEntityTypeId())
				->where('OWNER_ID', $latestItem->getId())
				->where('PROVIDER_ID', WebForm::getId())
				->addOrder('CREATED', 'DESC')
				->setLimit(1)
				->fetchObject()
				?->getId()
			;
		}

		return new ClientSummary(
			$client,
			$summaryEntityTypeId,
			$items,
			$summarizeCallResult,
			$latestWebformId
		);
	}

	private function getClientItems(ItemIdentifier $client, Factory $itemFactory): array
	{
		static $order = [
			Item::FIELD_NAME_CREATED_TIME => 'DESC',
		];

		if ($client->getEntityTypeId() === \CCrmOwnerType::Contact)
		{
			return $itemFactory->getItems([
				'filter' => [
					'=' . Item::FIELD_NAME_CONTACT_BINDINGS . '.CONTACT_ID' => $client->getEntityId(),
				],
				'order' => $order,
			]);
		}
		elseif ($client->getEntityTypeId() === \CCrmOwnerType::Company)
		{
			return $itemFactory->getItems([
				'filter' => [
					'=' . Item::FIELD_NAME_COMPANY_ID => $client->getEntityId(),
				],
				'order' => $order,
			]);
		}
		else
		{
			throw new ArgumentException('Only contact or company supported');
		}
	}

	public function getDynamicTypeSummary(int $typeId): ?DynamicTypeSummary
	{
		$type = Container::getInstance()->getType($typeId);
		if (!$type)
		{
			return null;
		}

		return new DynamicTypeSummary(Container::getInstance()->getDynamicFactoryByType($type));
	}
}
