<?php

namespace Bitrix\Crm\Service\Communication\Channel\Event;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\ItemIdentifierCollection;
use Bitrix\Crm\Service\Communication\Channel\ChannelFactory;
use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesManager;
use Bitrix\Crm\Service\Communication\Channel\Property\Property;
use Bitrix\Crm\Service\Communication\Channel\Queue\Queue;
use Bitrix\Crm\Service\Communication\Controller\EventController;
use Bitrix\Crm\Service\Communication\Controller\RuleController;
use Bitrix\Crm\Service\Communication\Result\ErrorCode;
use Bitrix\Crm\Service\Communication\Result\RegisterTouchResult;
use Bitrix\Crm\Service\Communication\Result\RoutingQueueResult;
use Bitrix\Crm\Service\Communication\Result\TouchedItemIdentifier;
use Bitrix\Crm\Service\Communication\Route\ConditionsChecker;
use Bitrix\Crm\Service\Communication\Route\EntityReuseMode;
use Bitrix\Crm\Service\Communication\Search\DuplicateFinder;
use Bitrix\Crm\Service\Communication\Search\EntityFinder;
use Bitrix\Crm\Service\Communication\Search\Ranking\RankingTypes;
use Bitrix\Crm\Service\Communication\Search\SearchEntityTypesConfig;
use Bitrix\Crm\Service\Communication\Search\TouchedEntityConfig;
use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;

final class ChannelEventRegistrar
{
	private const BOUND_ENTITY_TYPE_IDS = [
		\CCrmOwnerType::Contact,
		\CCrmOwnerType::Company,
	];

	private array $firstSuitableRule = [];
	private array $existedEntities = [];
	private array $entityFields = [];
	private ItemIdentifierCollection $resultItems;
	private EntityFinder $entityFinder;

	public function __construct(
		private readonly ChannelEvent $channelEvent,
		private readonly RuleController $ruleController,
		private readonly EventController $eventController
	)
	{
		$this->saveChannelEvent();
	}

	public function addPreferredExistedEntity(ItemIdentifier $itemIdentifier): self
	{
		if (!in_array($itemIdentifier, $this->existedEntities, true))
		{
			$this->existedEntities[] = $itemIdentifier;
		}

		return $this;
	}

	public function setEntityFields(array $fields): self
	{
		$this->entityFields = $fields;

		return $this;
	}

	public function getUsersQueue(): RoutingQueueResult
	{
		$result = new RoutingQueueResult();

		$rules = $this->findSuitableRules();
		if (empty($rules))
		{
			$result->addError(new Error("No routes found", ErrorCode::NOT_FOUND));

			return $result;
		}

		$queueConfigId = $rules[0]['QUEUE_CONFIG_ID'] ?? null; // @toto: use first rule from list
		if (is_null($queueConfigId))
		{
			$result->addError(
				new Error("No queues found for route with ID {$rules[0]['ID']}",
					ErrorCode::NOT_FOUND
				)
			);

			return $result;
		}

		$this->firstSuitableRule = $rules[0];

		$result->setQueue(new Queue($queueConfigId));

		return $result;
	}

	public function registerTouch(?int $userId = null): RegisterTouchResult
	{
		if (empty($this->existedEntities))
		{
			$rules = $this->findSuitableRules();
			$touchedItemIdentifiers = $this->touchEntitiesByRules($rules);
		}
		else
		{
			$touchedItemIdentifiers = $this->updateExistedEntities();
		}

		$itemIdentifierCollection = new ItemIdentifierCollection();
		foreach ($touchedItemIdentifiers as $touchedItemIdentifier)
		{
			$itemIdentifierCollection->append($touchedItemIdentifier->getItemIdentifier());
		}

		$this->resultItems = $itemIdentifierCollection;

		$this->saveChannelEvent($touchedItemIdentifiers, $userId);

		return (new RegisterTouchResult())->setTouchedItemsCollection($this->resultItems);
	}

	public function searchClientResponsibleUserId(): ?int
	{
		if (empty($this->firstSuitableRule))
		{
			return null;
		}

		$touchedEntityConfigs = $this->getPreparedCreateEntitiesData($this->firstSuitableRule['ENTITIES']);
		$searchEntityTypesConfig = $this->getEntityTypesConfig(
			$this->firstSuitableRule['SEARCH_TARGETS'],
			$touchedEntityConfigs
		);
		if (!$searchEntityTypesConfig->hasClientsEntityTypes())
		{
			return null;
		}

		$existedEntities = $this->searchExistedEntities(
			$searchEntityTypesConfig->getSearchEntityTypeIds(),
			$touchedEntityConfigs
		);

		$existedClient = null;
		foreach ($existedEntities as $existedEntity)
		{
			$bindings = $existedEntity['bindings'] ?? [];
			if (!empty($bindings))
			{
				$existedClient = $bindings[0]; // @toto: use first binding from list

				break;
			}
		}

		if ($existedClient === null)
		{
			return null;
		}

		return Container::getInstance()
			->getFactory($existedClient->getEntityTypeId())
			?->getItem($existedClient->getEntityId())
			?->getAssignedById()
			;
	}

	/**
	 * @return TouchedItemIdentifier[]
	 */
	private function updateExistedEntities(): array
	{
		$result = [];

		foreach ($this->existedEntities as $existedEntity)
		{
			$itemIdentifier = $this->updateEntity($existedEntity);

			if ($itemIdentifier)
			{
				$result[] = new TouchedItemIdentifier($itemIdentifier, false);
			}
		}

		return $result;
	}

	private function findSuitableRules(): array
	{
		$rules = $this->ruleController->findRules($this->channelEvent->getChannel());

		$suitableRules = [];

		$conditionsChecker = new ConditionsChecker();
		$eventPropertiesCollection = $this->channelEvent->getPropertiesCollection();
		foreach ($rules as $item)
		{
			if ($conditionsChecker->isSuitableCondition($item['RULES'], $eventPropertiesCollection))
			{
				$suitableRules[] = $item;
			}
		}

		return $suitableRules;
	}

	/**
	 * @param array $rules
	 * @return TouchedItemIdentifier[]
	 */
	private function touchEntitiesByRules(array $rules): array
	{
		$result = [];

		foreach ($rules as $item)
		{
			$touchedItemIdentifiers = $this->executeChannelRule($item['SEARCH_TARGETS'], $item['ENTITIES']);
			foreach ($touchedItemIdentifiers as $touchedItemIdentifier)
			{
				$result[] = $touchedItemIdentifier;
			}

			if ($this->isSkipNextRules($item))
			{
				break;
			}
		}

		return $result;
	}

	private function getNewEntityFields(): array
	{
		$data = [];

		foreach ($this->channelEvent->getPropertiesCollection()->getEventProperties() as $param)
		{
			$property = $this->getChannelPropertyInfoByCode($param->getCode());

			if ($property === null)
			{
				continue;
			}

			$instance = PropertiesManager::getTypeInstance($property->getType(), $param->getValue());

			if ($instance?->canUsePreparedValue() && $param->isProcessAccordingType())
			{
				$data = array_merge_recursive($data, $instance->getPreparedValue());
			}
		}

		return array_merge($data, $this->entityFields);
	}

	// @todo may be move to separate class
	private function getChannelPropertyInfoByCode(string $code): ?Property
	{
		$channelProvider = ChannelFactory::getInstance()->getChannelHandlerInstance(
			$this->channelEvent->getChannel()
		);

		if (!$channelProvider)
		{
			return null;
		}

		return $channelProvider->getPropertiesCollection()->getProperty($code);
	}

	/**
	 * @param array $searchTargets
	 * @param array $createEntities
	 * @return TouchedItemIdentifier[]
	 */
	private function executeChannelRule(array $searchTargets, array $createEntities): array
	{
		$touchedEntityConfigs = $this->getPreparedCreateEntitiesData($createEntities);
		$searchEntityTypesConfig = $this->getEntityTypesConfig($searchTargets, $touchedEntityConfigs);

		$existedEntities = $this->searchExistedEntities(
			$searchEntityTypesConfig->getSearchEntityTypeIds(),
			$touchedEntityConfigs
		);

		$createdItemsData = [];
		$updatedItemsData = [];

		foreach ($touchedEntityConfigs as $touchedEntityConfig)
		{
			$entityTypeId = $touchedEntityConfig->getEntityTypeId();
			$searchStrategy = $touchedEntityConfig->getSearchStrategy();

			$boundItemsCollection = $this->getBoundItemIdentifiers($entityTypeId, $existedEntities);
			if ($touchedEntityConfig->isAlwaysCreateNewEntity() || $boundItemsCollection->count() === 0 )
			{
				$resultItemIdentifier = $this->createEntity($entityTypeId, $touchedEntityConfig);
				if ($resultItemIdentifier !== null)
				{
					$createdItemsData[] = [
						'itemIdentifier' => $resultItemIdentifier,
						'searchStrategy' => $searchStrategy,
					];
				}
			}
			else
			{
				foreach ($boundItemsCollection as $boundItemIdentifier)
				{
					$resultItemIdentifier = $this->updateEntity($boundItemIdentifier);
					if ($resultItemIdentifier !== null)
					{
						$updatedItemsData[] = [
							'itemIdentifier' => $resultItemIdentifier,
							'searchStrategy' => $searchStrategy,
						];
					}
				}
			}
		}

		if (!empty($createdItemsData))
		{
			$rankedClients = $this->entityFinder->rankClients([
				...$updatedItemsData,
				...$createdItemsData,
			]);

			if ($searchEntityTypesConfig->hasClientsEntityTypes())
			{
				$this->bindCreatedEntities($createdItemsData, $updatedItemsData, $rankedClients);
			}
		}

		$result = [];
		foreach ($updatedItemsData as $itemData)
		{
			$result[] = new TouchedItemIdentifier($itemData['itemIdentifier'], false);
		}
		foreach ($createdItemsData as $itemData)
		{
			$result[] = new TouchedItemIdentifier($itemData['itemIdentifier'], true);
		}

		return $result;
	}

	/**
	 * @param array $searchEntityTypeIds
	 * @param TouchedEntityConfig[] $touchedEntityConfigs
	 * @return array
	 */
	private function searchExistedEntities(array $searchEntityTypeIds, array $touchedEntityConfigs): array
	{
		$this->initEntityFinder($searchEntityTypeIds, $touchedEntityConfigs);

		return $this->entityFinder->search();
	}

	private function initEntityFinder(array $searchEntityTypeIds, array $touchedEntityConfigs): void
	{
		$duplicateFinder = new DuplicateFinder();

		$this->entityFinder = new EntityFinder($searchEntityTypeIds, $touchedEntityConfigs, $duplicateFinder);
		$this->appendSearchCriteria($this->entityFinder);
	}

	private function bindCreatedEntities(
		array $createdItemsData,
		array $updatedItemsData,
		array $rankedClients
	): void
	{
		if (empty($rankedClients))
		{
			return;
		}

		foreach ([...$createdItemsData, ...$updatedItemsData] as $data)
		{
			$itemIdentifier = $data['itemIdentifier'];
			$searchStrategy = $data['searchStrategy'];

			$entityTypeId = $itemIdentifier->getEntityTypeId();
			if (Common::isClientEntityTypeId($entityTypeId)) // @todo need bind contact with company?
			{
				continue;
			}

			$factory = Container::getInstance()->getFactory($itemIdentifier->getEntityTypeId());
			if ($factory === null || !$factory->isClientEnabled())
			{
				continue;
			}

			$item = $factory->getItem($itemIdentifier->getEntityid(), ['CONTACT_ID', 'COMPANY_ID']);
			if ($item === null)
			{
				continue;
			}

			$updateItem = false;

			$clientsItemCollection = null;
			foreach ($rankedClients as $rankedClientItemData)
			{
				if ($rankedClientItemData['searchStrategy'] === $searchStrategy)
				{
					$clientsItemCollection = $rankedClientItemData['clientItemsCollection'];
					break;
				}
			}

			foreach ($clientsItemCollection as $clientItem)
			{
				if ($clientItem->getEntityTypeId() === \CCrmOwnerType::Contact && $item->getContactId() <= 0)
				{
					$item->setContactId($clientItem->getEntityId());
					$updateItem = true;
				}
				elseif ($clientItem->getEntityTypeId() === \CCrmOwnerType::Company && $item->getCompanyId() <= 0)
				{
					$item->setCompanyId($clientItem->getEntityId());
					$updateItem = true;
				}

				if ($updateItem)
				{
					$factory
						->getUpdateOperation($item)
						->disableAllChecks()
						->disableAutomation()
						->launch()
					;
				}
			}
		}
	}

	/**
	 * @param array $createEntities
	 * @return TouchedEntityConfig[]
	 */
	private function getPreparedCreateEntitiesData(array $createEntities): array
	{
		$results = [];
		foreach ($createEntities as $createEntity)
		{
			if ($createEntity['type'] === 'entity')
			{
				$data = $createEntity['data'];

				$results[] = new TouchedEntityConfig(
					CategoryIdentifier::createByParams($data['entityTypeId'], $data['categoryId'] ?? null),
					RankingTypes::getValueById($data['searchStrategy']),
					EntityReuseMode::getInstanceByValue((int)($data['entityReuseMode'] ?? 0)),
				);
			}
		}

		return $results;
	}

	/**
	 * @param array $searchTargets
	 * @param TouchedEntityConfig[] $touchedEntityConfigs
	 * @return SearchEntityTypesConfig
	 */
	private function getEntityTypesConfig(array $searchTargets, array $touchedEntityConfigs): SearchEntityTypesConfig
	{
		$touchedEntityIds = [];
		foreach ($touchedEntityConfigs as $touchedEntityConfig)
		{
			$touchedEntityIds[] = $touchedEntityConfig->getEntityTypeId();
		}

		return new SearchEntityTypesConfig($searchTargets['entityTypeIds'], $touchedEntityIds);
	}

	private function appendSearchCriteria(EntityFinder $entityFinder): void
	{
		$channelProvider = ChannelFactory::getInstance()->getChannelHandlerInstance($this->channelEvent->getChannel());
		if (!$channelProvider)
		{
			return;
		}

		$channelPropertiesCollection = $channelProvider->getPropertiesCollection();

		$eventPropertiesCollection = $this->channelEvent->getPropertiesCollection();

		foreach ($eventPropertiesCollection->getEventProperties() as $eventProperty)
		{
			$value = $eventProperty->getValue();
			if ($value === null || !$eventProperty->isProcessAccordingType())
			{
				continue;
			}

			$property = $channelPropertiesCollection->getProperty($eventProperty->getCode());
			if (!$property)
			{
				continue;
			}

			$propertyInstance = PropertiesManager::getTypeInstance($property->getType(), $value);
			if ($propertyInstance === null)
			{
				continue;
			}

			$propertyInstance->appendSearchCriterion($entityFinder);
		}
	}

	private function getBoundItemIdentifiers(int $entityTypeId, array $existedEntities): ItemIdentifierCollection
	{
		$result = new ItemIdentifierCollection();

		// search by ranked entities
		foreach ($existedEntities as $existedEntity)
		{
			if ($existedEntity['item']->getEntityTypeId() === $entityTypeId)
			{
				$result->append($existedEntity['item']);

				return $result;
			}
		}

		foreach ($existedEntities as $existedEntity)
		{
			$bindings = $existedEntity['bindings'] ?? [];
			foreach ($bindings as $binding)
			{
				if ($binding->getEntityTypeId() === $entityTypeId)
				{
					$result->append(
						new ItemIdentifier($entityTypeId, $binding->getEntityId())
					);
				}
			}
		}

		return $result;
	}

	private function createEntity(int $entityTypeId, TouchedEntityConfig $touchedEntityConfig): ?ItemIdentifier
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		$item = $factory->createItem();
		$item->setFromCompatibleData($this->getNewEntityFields());

		if ($factory->isCategoriesSupported())
		{
			$categoryId = $touchedEntityConfig->getCategoryId();
			if ($categoryId !== null)
			{
				$item->setCategoryId($categoryId);
			}
		}

		$result = $factory->getAddOperation($item)->disableAllChecks()->launch();

		if (!$result->isSuccess())
		{
			return null;
		}

		return new ItemIdentifier(
			$entityTypeId,
			$item->getId(),
			$factory->isCategoriesSupported() ? $item->getCategoryId() : null,
		);
	}

	private function updateEntity(ItemIdentifier $entity): ?ItemIdentifier
	{
		$factory = Container::getInstance()->getFactory($entity->getEntityTypeId());
		if (!$factory)
		{
			return null;
		}

		$item = $factory->getItem($entity->getEntityId());
		if (!$item)
		{
			return null;
		}

		$data = array_merge_recursive($item->getCompatibleData(), $this->getNewEntityFields());
		$item->setFromCompatibleData($data);

		$updateResult = $factory
			->getUpdateOperation($item)
			->disableAllChecks()
			->launch()
		;

		return $updateResult->isSuccess() ? $entity : null;
	}

	private function isSkipNextRules(array $item): bool
	{
		return (
			isset($item['SETTINGS']['skipNextRules']) && $item['SETTINGS']['skipNextRules'] === 'Y'
		);
	}

	/**
	 * @param TouchedItemIdentifier[] $touchedItemIdentifiers
	 * @param int|null $userId
	 * @return void
	 */
	private function saveChannelEvent(array $touchedItemIdentifiers = [], ?int $userId = null): void
	{
		$this->eventController->register($this->channelEvent, $touchedItemIdentifiers, $userId);
	}

	public function getResultItems(): ItemIdentifierCollection
	{
		return $this->resultItems;
	}

	public function setResultItems(ItemIdentifierCollection $resultItems): ChannelEventRegistrar
	{
		$this->resultItems = $resultItems;

		return $this;
	}

	public function getChannelCode(): string
	{
		return $this->channelEvent->getChannel()->getCode();
	}

	public function getPropertiesCollection(): ChannelEventPropertiesCollection
	{
		return $this->channelEvent->getPropertiesCollection();
	}
}
