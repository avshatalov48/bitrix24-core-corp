<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Entity\Booking\Booking;
use Bitrix\Booking\Entity\Booking\Client;
use Bitrix\Booking\Entity\Booking\ClientCollection;
use Bitrix\Booking\Entity\Booking\ClientType;
use Bitrix\Booking\Entity\Booking\ExternalDataCollection;
use Bitrix\Booking\Entity\Booking\ExternalDataItem;
use Bitrix\Booking\Interfaces\DataProviderInterface;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use CCrmOwnerType;

class DataProvider implements DataProviderInterface
{
	public const MODULE_ID = 'crm';

	public function getMoneyStatistics(...$externalDataCollections): array
	{
		$result = [];

		$deals = self::getDeals(
			self::getDealIdsFromCollections($externalDataCollections)
		);

		/** @var Item\Deal $deal */
		foreach ($deals as $deal)
		{
			$currencyId = $deal->getCurrencyId();

			if (!isset($result[$currencyId]))
			{
				$result[$currencyId] = [
					'opportunity' => 0,
					'currencyId' => $currencyId,
				];
			}

			$result[$currencyId]['opportunity'] += $deal->getOpportunity();
		}

		return array_values(
			array_map(static fn ($item): array => [
				'formattedOpportunity' => \CCrmCurrency::MoneyToString(
					$item['opportunity'],
					$item['currencyId']
				),
				'currencyId' => $item['currencyId'],
				'opportunity' => $item['opportunity'],
			], $result)
		);
	}

	public function getBaseCurrencyId(): string|null
	{
		return \CCrmCurrency::GetBaseCurrencyID();
	}

	public function loadDataForCollection(...$externalDataCollections): void
	{
		$dealIds = self::getDealIdsFromCollections($externalDataCollections);
		if (empty($dealIds))
		{
			return;
		}

		$deals = self::getDeals($dealIds);
		if (empty($deals))
		{
			return;
		}

		/** @var ExternalDataCollection $externalDataCollection */
		foreach ($externalDataCollections as $externalDataCollection)
		{
			/** @var ExternalDataItem $externalDataItem */
			foreach ($externalDataCollection as $externalDataItem)
			{
				if (!self::isDeal($externalDataItem))
				{
					continue;
				}

				$dealId = self::getDealId($externalDataItem);
				if (!isset($deals[$dealId]))
				{
					continue;
				}

				/** @var Item\Deal $deal */
				$deal = $deals[$dealId];

				$currencyId = $deal->getCurrencyId();
				$opportunity = $deal->getOpportunity();

				$externalDataItem->setData([
					'currencyId' => $currencyId,
					'opportunity' => $opportunity,
					'formattedOpportunity' =>
						(is_null($currencyId) || is_null($opportunity))
							? null:
							\CCrmCurrency::MoneyToString($opportunity, $currencyId)
					,
					'createdTimestamp' => $deal->getCreatedTime()?->getTimestamp(),
				]);
			}
		}
	}

	public function setClientsData(ClientCollection $clientCollection, ...$externalDataCollections): void
	{
		if (!$clientCollection->isEmpty())
		{
			return;
		}

		$dealIds = self::getDealIdsFromCollections($externalDataCollections);
		if (empty($dealIds))
		{
			return;
		}

		$deals = self::getDeals($dealIds);
		if (empty($deals))
		{
			return;
		}

		/** @var ExternalDataCollection $externalDataCollection */
		foreach ($externalDataCollections as $externalDataCollection)
		{
			/** @var ExternalDataItem $externalDataItem */
			foreach ($externalDataCollection as $externalDataItem)
			{
				if (!self::isDeal($externalDataItem))
				{
					continue;
				}

				$dealId = self::getDealId($externalDataItem);
				if (!isset($deals[$dealId]))
				{
					continue;
				}

				/** @var Item\Deal $deal */
				$deal = $deals[$dealId];

				foreach ($deal->getContacts() as $contact)
				{
					$contactType = (new ClientType())
						->setModuleId(self::MODULE_ID)
						->setCode(CCrmOwnerType::ContactName)
					;

					$contact = (new Client())
						->setId($contact->getId())
						->setType($contactType)
					;

					$clientCollection->add($contact);
				}

				$company = $deal->getCompany();

				if ($company)
				{
					$contactType = (new ClientType())
						->setModuleId(self::MODULE_ID)
						->setCode(CCrmOwnerType::CompanyName)
					;

					$contact = (new Client())
						->setId($company->getId())
						->setType($contactType)
					;

					$clientCollection->add($contact);
				}
			}
		}
	}

	public function updateBindings(Booking $updatedBooking, Booking|null $prevBooking): void
	{
		$isNewClientLinked = $prevBooking->getPrimaryClient() === null && $updatedBooking->getPrimaryClient() !== null;
		$dealIds = self::getDealIdsFromCollections([$updatedBooking->getExternalDataCollection()]);
		$prevDealIds = self::getDealIdsFromCollections([$prevBooking->getExternalDataCollection()]);
		$hasDealBindingChanged = !empty(array_diff($dealIds, $prevDealIds));

		if (!($isNewClientLinked || $hasDealBindingChanged))
		{
			return;
		}

		$clients = $updatedBooking->getClientCollection();

		if ($clients->isEmpty())
		{
			return;
		}

		$dealIds = self::getDealIdsFromCollections([$updatedBooking->getExternalDataCollection()]);

		if (empty($dealIds))
		{
			return;
		}

		$deals = self::getDeals($dealIds);

		if (empty($deals))
		{
			return;
		}

		/** @var ExternalDataItem $externalDataItem */
		foreach ($updatedBooking->getExternalDataCollection() as $externalDataItem)
		{
			if (!self::isDeal($externalDataItem))
			{
				continue;
			}

			$dealId = self::getDealId($externalDataItem);

			if (!isset($deals[$dealId]))
			{
				continue;
			}

			/** @var Item\Deal $deal */
			$deal = $deals[$dealId];

			self::bindDealContacts($deal, $clients);
		}
	}

	private static function bindDealContacts(Item\Deal $deal, ClientCollection $clients): void
	{
		$isDealToUpdate = false;

		// setting contacts
		if (empty($deal->getContacts()))
		{
			$contactIds = [];

			foreach ($clients as $client)
			{
				if ($client->getType()->getModuleId() !== self::MODULE_ID)
				{
					continue;
				}

				if ($client->getType()->getCode() !== CCrmOwnerType::ContactName)
				{
					continue;
				}

				$contactIds[] = $client->getId();
			}

			if (!empty($contactIds))
			{
				$deal->setContactIds($contactIds);
				$isDealToUpdate = true;
			}
		}

		// setting company
		if ($deal->getCompany() === null)
		{
			foreach ($clients as $client)
			{
				if (
					$client->getType()->getModuleId() === self::MODULE_ID
					&& $client->getType()->getCode() === CCrmOwnerType::CompanyName
				)
				{
					$deal->setCompanyId($client->getId());
					$isDealToUpdate = true;
					break;
				}
			}
		}

		if ($isDealToUpdate)
		{
			$factory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);
			$factory->getUpdateOperation($deal)
				->disableCheckFields()
				->disableCheckRequiredUserFields()
				->launch();;
		}
	}

	private static function getDealId(ExternalDataItem $externalDataItem): int
	{
		return (int)$externalDataItem->getValue();
	}

	private static function isDeal(ExternalDataItem $externalDataItem): bool
	{
		if (
			$externalDataItem->getModuleId() === self::MODULE_ID
			&& $externalDataItem->getEntityTypeId() === 'DEAL'
		)
		{
			return true;
		}

		return false;
	}

	private static function getDealIdsFromCollections($externalDataCollections): array
	{
		$result = [];

		/** @var ExternalDataCollection $externalDataCollection */
		foreach ($externalDataCollections as $externalDataCollection)
		{
			/** @var ExternalDataItem $externalDataItem */
			foreach ($externalDataCollection as $externalDataItem)
			{
				if (!self::isDeal($externalDataItem))
				{
					continue;
				}

				$dealId = self::getDealId($externalDataItem);
				$result[$dealId] = true;
			}
		}

		return array_keys($result);
	}

	private static function getDeals(array $dealIds): array
	{
		if (empty($dealIds))
		{
			return [];
		}

		$dealFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		if (!$dealFactory)
		{
			return [];
		}

		$deals = $dealFactory->getItems([
			'select' => [
				Item::FIELD_NAME_ID,
				Item::FIELD_NAME_OPPORTUNITY,
				Item::FIELD_NAME_CURRENCY_ID,
				Item::FIELD_NAME_CREATED_TIME,
				Item::FIELD_NAME_COMPANY,
				Item::FIELD_NAME_CONTACT_IDS,
			],
			'filter' => [
				'=ID' => $dealIds,
			],
		]);

		$result = [];

		foreach ($deals as $deal)
		{
			$result[$deal->getId()] = $deal;
		}

		return $result;
	}

	public static function getDealFromExternalDataCollection(
		ExternalDataCollection $externalDataCollection
	): Item\Deal|null
	{
		$deals = array_values(
			self::getDeals(
				self::getDealIdsFromCollections([$externalDataCollection])
			)
		);

		return empty($deals) ? null : $deals[0];
	}
}
