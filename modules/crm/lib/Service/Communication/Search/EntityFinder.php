<?php

namespace Bitrix\Crm\Service\Communication\Search;

use Bitrix\Crm\Communication\Type;
use Bitrix\Crm\Integrity\Duplicate;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateCriterion;
use Bitrix\Crm\Integrity\DuplicatePersonCriterion;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\ItemIdentifierCollection;
use Bitrix\Crm\Service\Communication\Search\Ranking\RankingTypes;
use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Crm\Service\Container;

final class EntityFinder
{
	private array $duplicateCriteria = [];

	/**
	 * @param int[] $searchEntityTypeIds
	 * @param TouchedEntityConfig[] $touchedEntitiesConfig
	 * @param DuplicateFinder $duplicateFinder
	 */
	public function __construct(
		private readonly array $searchEntityTypeIds,
		private array $touchedEntitiesConfig,
		private readonly DuplicateFinder $duplicateFinder
	)
	{
		usort($this->touchedEntitiesConfig, function($a, $b) {
			if ($a->getEntityTypeId() === $b->getEntityTypeId())
			{
				return 0;
			}

			return $this->isClientEntity($a->getEntityTypeId()) ? 1 : -1;
		});
	}

	/**
	 * @return ItemIdentifier[]
	 */
	public function search(): array
	{
		if (empty($this->searchEntityTypeIds))
		{
			return [];
		}

		$duplicates = $this->findDuplicates();

		if (empty($duplicates))
		{
			return [];
		}

		$duplicates = $this->getDuplicatesWithEntityType($duplicates);

		$result = [];
		foreach ($this->touchedEntitiesConfig as $touchedEntityConfig)
		{
			$entityTypeId = $touchedEntityConfig->getEntityTypeId();

			if (!empty($result) && $this->isClientEntity($entityTypeId))
			{
				// not ranking by contacts and companies if was found other entities
				continue;
			}

			$rankingType = $touchedEntityConfig->getSearchStrategy();

			if ($rankingType === RankingTypes::unknown)
			{
				continue;
			}

			$entityRanking = new EntityRanking($rankingType);
			$rankResult = $entityRanking->rank($entityTypeId, $this->searchEntityTypeIds, $duplicates);

			if (!empty($rankResult))
			{
				$result[] = $rankResult;
			}
		}

		return $result;
	}

	/**
	 * @return Duplicate[]
	 */
	private function findDuplicates(): array
	{
		$sortedCriteria = $this->sortCriteria();

		return $this->duplicateFinder->getDuplicates($sortedCriteria);
	}

	/**
	 * @return DuplicateCriterion[]
	 */
	private function sortCriteria(): array
	{
		$sortedCriteria = [];
		foreach ($this->duplicateCriteria as $index => $criterion)
		{
			$sort = 10000;
			if ($criterion instanceof DuplicateCommunicationCriterion)
			{
				$sort = 1000;
			}
			else if ($criterion instanceof DuplicatePersonCriterion)
			{
				$sort = 100000;
			}
			$sortedCriteria[$sort + $index] = $criterion;
		}
		ksort($sortedCriteria);

		return $sortedCriteria;
	}

	private function getDuplicatesWithEntityType(array $duplicates): array
	{
		$list = [];

		foreach ($this->searchEntityTypeIds as $entityTypeId)
		{
			foreach ($duplicates as $duplicate)
			{
				$list[$entityTypeId] = $duplicate->getEntityIDsByType($entityTypeId);
			}
		}

		return $list;
	}

	public function appendPhoneCriterion(string $phone): self
	{
		return $this->appendCommunicationCriterion(Type::PHONE_NAME, $phone);
	}

	public function appendEmailCriterion(string $email): self
	{
		return $this->appendCommunicationCriterion(Type::EMAIL_NAME, $email);
	}

	private function appendCommunicationCriterion(string $communicationType, string $value): self
	{
		$this->duplicateCriteria[] = new DuplicateCommunicationCriterion($communicationType, $value);

		return $this;
	}

	public function rankClients(array $items): array
	{
		$ids = [];

		foreach ($items as $item)
		{
			$itemIdentifier = $item['itemIdentifier'];
			$entityTypeId = $itemIdentifier->getEntityTypeId();
			if (Common::isClientEntityTypeId($entityTypeId))
			{
				$ids[$entityTypeId][] = $itemIdentifier->getEntityId();
			}
		}

		$rankedClients = [];
		$rankingTypes = $this->getRankingTypes();
		foreach ($rankingTypes as $rankingType)
		{
			$result = new ItemIdentifierCollection();

			if ($rankingType === RankingTypes::unknown)
			{
				continue;
			}

			foreach ([\CCrmOwnerType::Contact, \CCrmOwnerType::Company] as $entityTypeId)
			{
				if (empty($ids[$entityTypeId]))
				{
					continue;
				}

				$ranking = Container::getInstance()->getCommunicationRankingFactory()->getRankingInstance($rankingType);
				if ($ranking === null)
				{
					continue;
				}

				$ranking->setDuplicatesByEntityType($entityTypeId, $ids[$entityTypeId]);
				$rankedItem = $ranking->rank($entityTypeId);

				$result->append($rankedItem['item']);
			}

			$rankedClients[] = [
				'searchStrategy' => $rankingType,
				'clientItemsCollection' => $result,
			];
		}

		return $rankedClients;
	}

	private function getRankingTypes(): array
	{
		$searchStrategies = [];
		foreach ($this->touchedEntitiesConfig as $touchedEntityConfig)
		{
			$searchStrategy = $touchedEntityConfig->getSearchStrategy();
			$searchStrategies[$searchStrategy->value] = $touchedEntityConfig->getSearchStrategy();
		}

		return array_values($searchStrategies);
	}

	private function isClientEntity(int $entityTypeId): bool
	{
		return Common::isClientEntityTypeId($entityTypeId);
	}
}
