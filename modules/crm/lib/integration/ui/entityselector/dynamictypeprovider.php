<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\AutomatedSolution\Support\TypeFilter;
use Bitrix\Crm\Model\Dynamic\Type;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\DynamicTypesMap;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

final class DynamicTypeProvider extends BaseProvider
{
	private const ENTITY_ID = 'dynamic_type';
	private const MAX_ITEMS_COUNT = 20;

	private bool $showAutomatedSolutionBadge = false;
	private bool $isOnlyCrmTypes = false;
	private bool $isOnlyExternalTypes = false;

	private DynamicTypesMap $dynamicTypesMap;
	private AutomatedSolutionManager $automatedSolutionManager;


	public function __construct(array $options = [])
	{
		parent::__construct();

		$showAutomatedSolutionBadge = $options['showAutomatedSolutionBadge'] ?? $this->showAutomatedSolutionBadge;
		$this->showAutomatedSolutionBadge = (bool)$showAutomatedSolutionBadge;

		$isOnlyCrmTypes = $options['isOnlyCrmTypes'] ?? $this->isOnlyCrmTypes;
		$this->isOnlyCrmTypes = (bool)$isOnlyCrmTypes;

		$isOnlyExternalTypes = $options['isOnlyExternalTypes'] ?? $this->isOnlyExternalTypes;
		$this->isOnlyExternalTypes = (bool)$isOnlyExternalTypes;

		$this->dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
		$this->automatedSolutionManager = Container::getInstance()->getAutomatedSolutionManager();
	}

	public function isAvailable(): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();
		if ($this->isOnlyExternalTypes)
		{
			return $userPermissions->canEditAutomatedSolutions();
		}
		if ($this->isOnlyCrmTypes)
		{
			return $userPermissions->isCrmAdmin();
		}

		return $userPermissions->canEditAutomatedSolutions() && $userPermissions->isCrmAdmin();
	}

	public function getItems(array $ids): array
	{
		$items = [];

		foreach ($this->getTypes() as $type)
		{
			if (!in_array($type->getId(), $ids, true))
			{
				continue;
			}

			$item = new Item([
				'id' => $type->getId(),
				'entityId' => self::ENTITY_ID,
				'title' => $type->getTitle(),
			]);

			if ($this->showAutomatedSolutionBadge && $type->getCustomSectionId() > 0)
			{
				$item->addBadges([
					[
						'title' => $this->getAutomatedSolutionTitle($type->getCustomSectionId()),
					],
				]);
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * @return Type[]
	 */
	private function getTypes(): array
	{
		$allTypes = $this->dynamicTypesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		])->getTypes();

		if ($this->isOnlyCrmTypes)
		{
			return array_filter(
				$allTypes,
				fn(Type $type) => !$this->automatedSolutionManager->isTypeBoundToAnyAutomatedSolution($type),
			);
		}

		if ($this->isOnlyExternalTypes)
		{
			return array_filter(
				$allTypes,
				$this->automatedSolutionManager->isTypeBoundToAnyAutomatedSolution(...),
			);
		}

		return $allTypes;
	}

	private function getAutomatedSolutionTitle(int $automatedSolutionId): ?string
	{
		$automatedSolution = $this->automatedSolutionManager->getAutomatedSolution($automatedSolutionId);

		return is_array($automatedSolution) ? $automatedSolution['TITLE'] : null;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$this->fillRecentItemsToMax($dialog);
	}

	private function fillRecentItemsToMax(Dialog $dialog): void
	{
		$howManyItemsStillToAdd = self::MAX_ITEMS_COUNT - $dialog->getRecentItems()->count();
		if ($howManyItemsStillToAdd <= 0)
		{
			return;
		}

		$globalRecentItems = $dialog->getGlobalRecentItems()->getEntityItems(self::ENTITY_ID);
		foreach ($globalRecentItems as $globalRecentItem)
		{
			if ($howManyItemsStillToAdd <= 0)
			{
				return;
			}

			$dialog->getRecentItems()->add($globalRecentItem);
			$howManyItemsStillToAdd--;
		}

		if ($howManyItemsStillToAdd <= 0)
		{
			return;
		}

		$typesToFillRecent = array_slice($this->getTypes(), 0, $howManyItemsStillToAdd);
		$typeIdsToFillRecent = array_map(fn(Type $type) => $type->getId(), $typesToFillRecent);

		$dialog->addRecentItems($this->getItems($typeIdsToFillRecent));
	}

	public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
	{
		$searchString = trim($searchQuery->getQuery());
		if (empty($searchString))
		{
			return;
		}

		$query = TypeTable::query()
			->setSelect(['ID'])
			->setFilter([
				'?TITLE' => $searchString, //substring search is not supported in Query, use a compatible format for this
			])
			->whereNotIn('ENTITY_TYPE_ID', \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds())
			->setLimit(self::MAX_ITEMS_COUNT)
		;

		if ($this->isOnlyCrmTypes)
		{
			$query->where(TypeFilter::getOnlyCrmTypesFilter());
		}
		elseif ($this->isOnlyExternalTypes)
		{
			$query->where(TypeFilter::getOnlyExternalTypesFilter());
		}

		$ids = $query->fetchCollection()->getIdList();

		$wereAllResultsFoundForThisQuery = count($ids) < self::MAX_ITEMS_COUNT;
		$searchQuery->setCacheable($wereAllResultsFoundForThisQuery);

		$dialog->addItems($this->getItems($ids));
	}
}
