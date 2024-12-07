<?php

namespace Bitrix\CrmMobile\Kanban;

use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\CrmMobile\Kanban\ControllerStrategy\KanbanStrategy;
use Bitrix\CrmMobile\Kanban\ControllerStrategy\ListStrategy;
use Bitrix\CrmMobile\Kanban\ControllerStrategy\StrategyInterface;
use Bitrix\CrmMobile\Kanban\Dto\Item;
use Bitrix\CrmMobile\Kanban\Dto\ItemData;
use Bitrix\CrmMobile\Kanban\ItemPreparer\Base;
use Bitrix\CrmMobile\Kanban\ItemPreparer\CompanyPreparer;
use Bitrix\CrmMobile\Kanban\ItemPreparer\ContactPreparer;
use Bitrix\CrmMobile\Kanban\ItemPreparer\KanbanPreparer;
use Bitrix\CrmMobile\Kanban\ItemPreparer\ListPreparer;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;

/**
 * Class Entity
 *
 * @package Bitrix\CrmMobile\Kanban
 */
final class Entity
{
	protected StrategyInterface $controllerStrategy;
	protected Base $itemPreparer;

	protected array $params = [];
	protected ?PageNavigation $pageNavigation = null;

	protected const USE_ONLY_LIST_STRATEGY = [
		\CCrmOwnerType::ContactName,
		\CCrmOwnerType::CompanyName,
	];

	protected const USE_ONLY_KANBAN_STRATEGY = [
		\CCrmOwnerType::LeadName,
		\CCrmOwnerType::DealName,
		\CCrmOwnerType::QuoteName,
		\CCrmOwnerType::SmartInvoiceName,
	];

	private string $entityTypeName;

	public static function getInstance(string $entityTypeName): Entity
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
		if (in_array($entityTypeName, self::USE_ONLY_LIST_STRATEGY, true))
		{
			return self::getListInstance($entityTypeId);
		}

		if (
			in_array($entityTypeName, self::USE_ONLY_KANBAN_STRATEGY, true)
			|| \CCrmOwnerType::isDynamicTypeBasedStaticEntity($entityTypeId)
		)
		{
			return self::getKanbanInstance($entityTypeId);
		}

		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return self::getDynamicEntityInstance($entityTypeId);
		}

		throw new EntityNotFoundException('EntityType: ' . $entityTypeName . ' unknown');
	}

	private static function getDynamicEntityInstance(int $entityTypeId): Entity
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		if ($factory)
		{
			if ($factory->isStagesEnabled())
			{
				return self::getKanbanInstance($entityTypeId);
			}

			return self::getListInstance($entityTypeId);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

		throw new EntityNotFoundException('EntityType: ' . $entityTypeName . ' unknown');
	}

	private static function getKanbanInstance(int $entityTypeId): Entity
	{
		$strategy = new KanbanStrategy();
		$preparer = new KanbanPreparer();

		return self::createAndInitInstance(\CCrmOwnerType::ResolveName($entityTypeId), $strategy, $preparer);
	}

	private static function getListInstance(int $entityTypeId): Entity
	{
		$strategy = new ListStrategy();
		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			$preparer = new CompanyPreparer();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Contact)
		{
			$preparer = new ContactPreparer();
		}
		else
		{
			$preparer = new ListPreparer();
		}

		return self::createAndInitInstance(\CCrmOwnerType::ResolveName($entityTypeId), $strategy, $preparer);
	}

	private static function createAndInitInstance(
		string $entityTypeName,
		StrategyInterface $strategy,
		Base $preparer
	): self
	{
		return (new self())
			->setEntityTypeName($entityTypeName)
			->setControllerStrategy($strategy)
			->setItemPreparer($preparer)
		;
	}

	public function setEntityTypeName(string $entityTypeName): Entity
	{
		$this->entityTypeName = $entityTypeName;

		return $this;
	}

	public function setControllerStrategy(StrategyInterface $strategy): Entity
	{
		$this->controllerStrategy = $strategy;

		return $this;
	}

	public function setItemPreparer(Base $preparer): Entity
	{
		$this->itemPreparer = $preparer;

		return $this;
	}

	public function getList(): array
	{
		$strategy = $this->getPreparedControllerStrategy();

		$entity = \Bitrix\Crm\Kanban\Entity::getInstance($this->getEntityTypeName());
		if (!$entity)
		{
			return [
				'items' => [],
			];
		}

		$defaultPresets = $entity->getFilterPresets();
		$filterOptions = new \Bitrix\Main\UI\Filter\Options(
			$strategy->getGridId(),
			$defaultPresets
		);
		$presets = array_merge($defaultPresets, $filterOptions->getPresets());
		$currentFilter = $strategy->getCurrentFilter();
		$currentPreset = $currentFilter['presetId'] ?? null;

		if (
			$currentPreset
			&& $currentPreset !== 'default_filter'
			&& $currentPreset !== 'tmp_filter'
			&& empty($presets[$currentPreset])
		)
		{
			return [
				'items' => [],
				'event' => 'refreshPresets',
			];
		}

		$itemsList = $strategy->getList($this->pageNavigation);

		$items = [];
		$itemParams = $strategy->getItemParams($itemsList);

		$itemPreparer = $this->itemPreparer
			->setParams($this->params)
			->setEntityTypeId($this->getEntityTypeId())
		;

		foreach ($itemsList as $item)
		{
			$preparedItem = $itemPreparer->execute($item, $itemParams);
			$items[] = $this->buildItemDto($preparedItem);
		}

		return [
			'items' => $items,
		];
	}

	public function getEntityTypeId(): int
	{
		return \CCrmOwnerType::ResolveID($this->getEntityTypeName());
	}

	public function getEntityTypeName(): string
	{
		return $this->entityTypeName;
	}

	public function prepare(array $params): Entity
	{
		$this->params = $params;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getColumns(): array
	{
		return [];
	}

	public function updateItemStage(int $id, int $stageId): Result
	{
		return $this->getPreparedControllerStrategy()->updateItemStage($id, $stageId);
	}

	public function deleteItem(int $id, array $params = []): Result
	{
		return $this->getPreparedControllerStrategy()->deleteItem($id, $params);
	}

	public function changeCategory(array $ids, int $categoryId): Result
	{
		return $this->getPreparedControllerStrategy()->changeCategory($ids, $categoryId);
	}

	protected function getPreparedControllerStrategy(): StrategyInterface
	{
		return $this->controllerStrategy
			->setParams($this->params)
			->setEntityTypeId($this->getEntityTypeId())
		;
	}

	public function setPageNavigation(PageNavigation $pageNavigation): Entity
	{
		$this->pageNavigation = $pageNavigation;

		return $this;
	}

	/**
	 * @return array
	 */
	protected function getFilterParams(): array
	{
		return ($this->params['filterParams'] ?? []);
	}

	protected function buildItemDto(array $data): Item
	{
		$item = Item::make([
			'id' => $data['id'],
		]);
		$item->data = ItemData::make($data['data']);

		return $item;
	}

	public function getSearchPresetsAndCounters(int $userId, ?int $currentCategoryId = null): array
	{
		$presets = $this->getSearchPresets($currentCategoryId);
		$counters = $this->getCounters($userId, $currentCategoryId);

		return [
			'presets' => $presets,
			'counters' => $counters,
		];
	}

	private function getSearchPresets(int $currentCategoryId = 0): array
	{
		$entity = \Bitrix\Crm\Kanban\Entity::getInstance($this->getEntityTypeName());
		if (!$entity)
		{
			return [];
		}

		$entity->setCategoryId($currentCategoryId);

		$defaultPresets = $entity->getFilterPresets();

		$filterOptions = new \Bitrix\Main\UI\Filter\Options(
			$this->getPreparedControllerStrategy()->getGridId(),
			$defaultPresets
		);

		$deletedPresets = $filterOptions->getOptions()['deleted_presets'] ?? [];
		foreach ($defaultPresets as $presetId => $preset)
		{
			if (!empty($deletedPresets[$presetId]))
			{
				unset($defaultPresets[$presetId]);
			}
		}

		return $this->getPreparedControllerStrategy()->prepareFilterPresets(
			$entity,
			array_merge($defaultPresets, $filterOptions->getPresets()),
			$filterOptions->getDefaultFilterId()
		);
	}

	/**
	 * @param int|null $categoryId
	 * @return string
	 */
	public function getDesktopLink(?int $categoryId): string
	{
		$router = Container::getInstance()->getRouter();

		$url = $router->getItemListUrlInCurrentView($this->getEntityTypeId(), $categoryId);
		if ($url)
		{
			return $url->getLocator();
		}

		return $router->getRoot();
	}

	public function getEntityLink(): ?string
	{
		$someMagicNumberToPassIntTypeCheck = 666;

		$url = Container::getInstance()->getRouter()->getItemDetailUrl(
			$this->getEntityTypeId(),
			$someMagicNumberToPassIntTypeCheck
		);
		if ($url)
		{
			return str_replace($someMagicNumberToPassIntTypeCheck, '#ENTITY_ID#', $url->getLocator());
		}

		return null;
	}

	/**
	 * @param int $userId
	 * @param int|null $categoryId
	 * @return array
	 */
	public function getCounters(int $userId, ?int $categoryId): array
	{
		if (
			method_exists(CounterSettings::class, 'getInstance')
			&& !CounterSettings::getInstance()->isEnabled()
		)
		{
			return [];
		}

		$entityTypeId = $this->getEntityTypeId();

		$factory = Container::getInstance()->getFactory($entityTypeId);

		$data = [];
		if (!$factory || !$factory->isCountersEnabled())
		{
			return $data;
		}

		$extra = [];
		if ($categoryId !== null && $factory->isCategoriesEnabled())
		{
			$extra['CATEGORY_ID'] = $categoryId;
		}

		$this->fillCountersData($data, $userId, $extra);

		// @todo remove after creating view mode Activity in the mobile
		$data[] = [
			'typeId' => '999',
			'typeName' => 'MY_PENDING',
			'title' => Loc::getMessage('M_CRM_KANBAN_ENTITY_COUNTER_CURRENT_USER_MY_PENDING'),
			'code' => 'my_pending',
			'value' => 0,
			'showValue' => false,
			'excludeUsers' => false,
		];

		if ($this->canUseOtherCounters($categoryId))
		{
			$extra['EXCLUDE_USERS'] = true;
			$this->fillCountersData($data, $userId, $extra);
		}

		return $data;
	}

	protected function fillCountersData(array &$data, int $userId, array $extra): void
	{
		$entityTypeId = $this->getEntityTypeId();

		$allSupportedTypes = EntityCounterType::getAllSupported($entityTypeId, true);
		foreach ($allSupportedTypes as $typeId)
		{
			if (EntityCounterType::isGroupingForArray($typeId, $allSupportedTypes))
			{
				continue;
			}

			$counter = EntityCounterFactory::create($entityTypeId, $typeId, $userId, $extra);
			$code = $counter->getCode();
			$value = $counter->getValue(false);
			$typeName = EntityCounterType::resolveName($typeId);
			$title = !empty($extra['EXCLUDE_USERS'])
				? Loc::getMessage("M_CRM_KANBAN_ENTITY_COUNTER_OTHER_USERS_$typeName")
				: Loc::getMessage("M_CRM_KANBAN_ENTITY_COUNTER_CURRENT_USER_$typeName");

			if (!$title)
			{
				continue;
			}

			$data[] = [
				'typeId' => $typeId,
				'typeName' => $typeName,
				'title' => $title,
				'code' => $code,
				'value' => $value,
				'showValue' => true,
				'excludeUsers' => ($extra['EXCLUDE_USERS'] ?? false),
			];
		}
	}

	// @todo fix code duplicate
	protected function canUseOtherCounters(?int $categoryId): bool
	{
		$entityTypeId = $this->getEntityTypeId();

		$uPermissions = Container::getInstance()->getUserPermissions();
		$permissionEntityType = $uPermissions::getPermissionEntityType($entityTypeId, (int)$categoryId);

		if ($uPermissions->isAdmin())
		{
			return true;
		}

		$permissions = $uPermissions->getCrmPermissions()->GetPermType($permissionEntityType);

		return $permissions >= $uPermissions::PERMISSION_ALL;
	}

	public function prepareFilter(\Bitrix\Crm\Kanban\Entity $entity): void
	{
		$this->getPreparedControllerStrategy()->prepareFilter($entity);
	}
}
