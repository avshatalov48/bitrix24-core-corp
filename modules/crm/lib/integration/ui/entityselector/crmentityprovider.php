<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Controller\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;
use Bitrix\UI\EntitySelector\Tab;
use Bitrix\Crm\Integration\UI\EntitySelector as UIEntitySelector;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class CrmEntityProvider extends BaseProvider
{
	public const ENTITY_ID = 'crm-entity';

	protected const TAB_ID = 'crm-entities';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['filterByAutomationOrBizproc'] = (bool)($options['filterByAutomationOrBizproc'] ?? false);
	}

	final public function isAvailable(): bool
	{
		return Loader::includeModule('crm');
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	/**
	 * @param Dialog $dialog
	 *
	 * @return void
	 */
	public function fillDialog(Dialog $dialog): void
	{
		$this->addEntitiesTab($dialog);

		$dataProviders = $this->getDataProviders();

		foreach ($dataProviders as $dataProvider)
		{
			$entityItem = $this->getEntityItem($dialog, $dataProvider);
			if (!$dialog->getItemCollection()->has($entityItem))
			{
				$entityItem->setNodeOptions(['dynamic' => false, 'open' => false]);
				$dialog->addItem($entityItem);
			}
		}

		$this->openItemsTree($dialog);
	}

	protected function addEntitiesTab(Dialog $dialog): void
	{
		$dialog->addTab(new Tab([
			'id' => static::TAB_ID,
			'title' => Loc::getMessage('CRM_ENTITY_SELECTOR_CRM_ENTITY_TAB_TITLE'),
			'itemOrder' => ['sort' => 'asc nulls last'],
			'stub' => true,
		]));
	}

	private function makeItem(array $data, bool $addTab = true): Item
	{
		$item = new Item([
			'id' => $data['id'],
			'entityId' => static::ENTITY_ID,
			'title' => $data['title'],
		]);
		if ($addTab)
		{
			$item->addTab(static::TAB_ID);
		}

		return $item;
	}

	protected function getEntityItem(Dialog $dialog, array $entity): Item
	{
		$entityItem = $dialog->getItemCollection()->get(static::ENTITY_ID, $entity['ENTITY_TYPE_ID']);
		if ($entityItem === null)
		{
			$entityItem = $this->makeItem([
				'id' => $entity['ENTITY_TYPE_ID'],
				'title' => $entity['ENTITY_TYPE_CAPTION'],
			]);
			$entityItem->setCustomData(['entityTypeId' => $entity['ENTITY_TYPE_ID']]);
			$entityItem->setSearchable(false);
		}

		return $entityItem;
	}

	protected function openItemsTree(Dialog $dialog): void
	{
		$context = $dialog->getContext() ?: self::TAB_ID;
		$dataProviders = $this->getDataProviders();

		$itemIds = [];
		$items = [];
		foreach ($dataProviders as $dataProvider)
		{
			$entityTypeId = $dataProvider['ENTITY_TYPE_ID'] ?? 0;
			$provider = new $dataProvider['CLASS'](['entityTypeId' => $entityTypeId]);
			$itemIds[$entityTypeId] = $provider->getRecentItemIds($context);
			array_push($items, ...$this->getElementsByIds($entityTypeId, $itemIds));
		}

		foreach ($items as $item)
		{
			$this->openEntitiesTree($dialog, $item);
		}
	}

	private function getElementsByIds(int $entityTypeId, array $ids): array
	{
		$titleField = $this->getTitleFieldName($entityTypeId);
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$elements = [];

		if ($factory && !empty($ids[$entityTypeId]))
		{
			$items = $factory->getItems([
				'filter' => ['ID' => $ids[$entityTypeId]],
				'select' => [$titleField]
			]);
			foreach ($items as $item)
			{
				$entityName = $this->getEntityName($entityTypeId);
				$itemId = $item->getId();
				$elements[] = [
					'id' => "${entityName}_${itemId}",
					'title' => $item->get($titleField),
					'entityId' => $itemId,
					'entityTypeId' => $entityTypeId,
				];
			}
		}

		return $elements;
	}

	protected function getTitleFieldName(int $entityTypeId): string
	{
		return $entityTypeId === \CCrmOwnerType::Contact
			? \Bitrix\Crm\Item::FIELD_NAME_FULL_NAME
			: \Bitrix\Crm\Item::FIELD_NAME_TITLE
			;
	}

	protected function getProviderEntityTypeId(UIEntitySelector\EntityProvider $provider): ?int
	{
		if (method_exists($provider, 'getEntityTypeId'))
		{
			$entityTypeMethod = new \ReflectionMethod($provider, 'getEntityTypeId');
			$entityTypeMethod->setAccessible(true);

			$entityTypeId = $entityTypeMethod->invoke($provider);

			return $entityTypeId;
		}

		return null;
	}

	protected function openEntitiesTree(Dialog $dialog, array $document): void
	{
		$entityItem = $dialog->getItemCollection()->get(
			static::ENTITY_ID,
			$document['entityTypeId']
		);

		if ($entityItem)
		{
			$entityItem
				->setNodeOptions(['open' => false, 'dynamic' => false, 'itemOrder' => ['sort' => 'asc nulls last']])
				->setSort(1)
			;

			$documentItem = $entityItem->getChildren()->get(
				static::ENTITY_ID,
				$document['id']
			);
			if (!$documentItem)
			{
				$this->fillEntityItem($dialog, $entityItem, $document);
			}
		}
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$context = $dialog->getContext() ?: self::TAB_ID;
		$countFound = 0;
		$maxLimit = 0;
		$items = [];
		$dataProviders = $this->getDataProviders();

		foreach ($dataProviders as $dataProvider)
		{
			$entityTypeId = $dataProvider['ENTITY_TYPE_ID'] ?? 0;
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$searchProvider = new \Bitrix\Crm\Search\Result\Provider\FactoryBased($factory);
				$resultIds[$entityTypeId] = $searchProvider->getSearchResult($searchQuery->getQuery())->getIds();
				array_push($items, ...$this->getElementsByIds($entityTypeId, $resultIds));

				$countFound += count($resultIds);
				$maxLimit = max($maxLimit, $searchProvider->getLimit());
			}
		}

		$foundItems = [];
		foreach ($items as $item)
		{
			$foundItems[] = $this->makeItem([
				'id' => $item['id'],
				'title' => "${item['title']} [${item['entityId']}]",
			], false);

			Entity::addLastRecentlyUsedItems(
				$context,
				$this->getItemEntityId($item['entityTypeId']),
				[
					[
						'ENTITY_TYPE_ID' => $item['entityTypeId'],
						'ENTITY_ID' => $item['entityId'],
					]
				]
			);
		}

		if ($foundItems)
		{
			$dialog->addItems($foundItems);
		}

		$searchQuery->setCacheable($countFound < $maxLimit);
	}

	protected function fillEntityItem(Dialog $dialog, Item $entityItem, array $document): void
	{
		$documentItem = $this->getDocumentItem($dialog, $document);
		$entityItem->addChild($documentItem);
	}

	public function getDataProviders(): array
	{
		static $result;

		if ($result === null)
		{
			$result = [];
			$providerClasses = [
				UIEntitySelector\CompanyProvider::class,
				UIEntitySelector\ContactProvider::class,
				UIEntitySelector\DealProvider::class,
				UIEntitySelector\LeadProvider::class,
			];

			$dynamicEntityTypeIds = $this->getDynamicEntityTypeIds();
			if (!empty($dynamicEntityTypeIds))
			{
				foreach ($dynamicEntityTypeIds as $dynamicEntityTypeId)
				{
					$className = mb_strtolower(UIEntitySelector\DynamicProvider::class);
					$result[] = [
						'ENTITY_TYPE_CAPTION' => \CCrmOwnerType::GetDescription($dynamicEntityTypeId),
						'ENTITY_TYPE_ID' => $dynamicEntityTypeId,
						'CLASS' => $className,
					];
				}
			}

			foreach ($providerClasses as $providerClass)
			{
				$className = mb_strtolower($providerClass);
				$provider = new $providerClass();
				$entityTypeId = $this->getProviderEntityTypeId($provider);

				if (Container::getInstance()->getUserPermissions()->entityType()->canReadItems($entityTypeId))
				{
					$result[] = [
						'ENTITY_TYPE_CAPTION' => \CCrmOwnerType::GetDescription($entityTypeId),
						'ENTITY_TYPE_ID' => $entityTypeId,
						'CLASS' => $className,
					];
				}
			}
		}

		return $result;
	}

	protected function getDynamicEntityTypeIds(): array
	{
		$entityTypeIds = [];

		$typesMap = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadCategories' => false,
			'isLoadStages' => false,
		]);

		foreach ($typesMap->getTypes() as $type)
		{
			$entityTypeId = $type->getEntityTypeId();
			$canRead = Container::getInstance()->getUserPermissions()->entityType()->canReadItems($entityTypeId);
			$needFilter = $this->options['filterByAutomationOrBizproc'];
			if ($canRead && (!$needFilter || $type->getIsAutomationEnabled() || $type->getIsBizProcEnabled()))
			{
				$entityTypeIds[] = $entityTypeId;
			}
		}

		return $entityTypeIds;
	}

	protected function getDocumentItem(Dialog $dialog, array $document): Item
	{
		$documentItem = $dialog->getItemCollection()->get(static::ENTITY_ID, $document['id']);
		if ($documentItem === null)
		{
			$documentItem = $this->makeItem([
				'id' => $document['id'],
				'title' => "${document['title']} [${document['entityId']}]"
			]);
			$documentItem->setCustomData(['entityTypeId' => $document['entityTypeId']]);
		}

		return $documentItem;
	}

	protected function getEntityName(int $entityTypeId): string
	{
		return \CCrmOwnerType::ResolveName($entityTypeId);
	}

	protected function getItemEntityId(int $entityTypeId): string
	{
		return mb_strtolower($this->getEntityName($entityTypeId));
	}
}
