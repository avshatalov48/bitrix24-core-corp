<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Entity\ExternalData\ExternalDataItem;
use Bitrix\Booking\Internals\Service\DataLoader\DataLoaderInterface;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

class CrmDealDataLoader implements DataLoaderInterface
{
	public function loadForCollection(BaseEntityCollection $collection): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$dealIds = $this->getDealIdsFromCollections([$collection]);
		if (empty($dealIds))
		{
			return;
		}

		$deals = $this->getDeals($dealIds);
		if (empty($deals))
		{
			return;
		}

		$this->processDealData($collection, $deals);
	}

	private function isAvailable(): bool
	{
		return Loader::includeModule('crm');
	}

	private function processDealData(BaseEntityCollection $collection, array $deals): void
	{
		/** @var ExternalDataItem $externalDataItem */
		foreach ($collection as $externalDataItem)
		{
			if (!$this->isDeal($externalDataItem))
			{
				continue;
			}

			$dealId = $this->getDealId($externalDataItem);
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
						? null :
						\CCrmCurrency::MoneyToString($opportunity, $currencyId)
				,
				'createdTimestamp' => $deal->getCreatedTime()?->getTimestamp(),
			]);
		}
	}

	private function getDealId(ExternalDataItem $externalDataItem): int
	{
		return (int)$externalDataItem->getValue();
	}

	private function isDeal(ExternalDataItem $externalDataItem): bool
	{
		return $externalDataItem->getModuleId() === 'crm'
			&& $externalDataItem->getEntityTypeId() === 'DEAL'
		;
	}

	private function getDealIdsFromCollections($externalDataCollections): array
	{
		$result = [];

		/** @var ExternalDataCollection $externalDataCollection */
		foreach ($externalDataCollections as $externalDataCollection)
		{
			/** @var ExternalDataItem $externalDataItem */
			foreach ($externalDataCollection as $externalDataItem)
			{
				if (!$this->isDeal($externalDataItem))
				{
					continue;
				}

				$dealId = $this->getDealId($externalDataItem);
				$result[$dealId] = true;
			}
		}

		return array_keys($result);
	}

	private function getDeals(array $dealIds): array
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
}
