<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Filter;
use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Dynamic extends Kanban\Entity
{
	use DynamicInlineEditorFieldsTrait;

	protected $stages;

	public function initFactory(): void
	{
		// automatic factory initialisation is disabled for dynamic types
		// factory should be set after creating kanban object using $this->setFactory(...)
	}

	public function getTypeName(): string
	{
		return $this->factory->getEntityName();
	}

	public function getTitle(): string
	{
		return \CCrmOwnerType::GetCategoryCaption($this->getTypeId());
	}

	public function getItemsSelectPreset(): array
	{
		$select = array_keys($this->factory->getFieldsInfo());

		$select = array_filter($select, static function ($fieldName) {
			return (
				$fieldName !== Item::FIELD_NAME_CONTACTS
				&& $fieldName !== Item::FIELD_NAME_CONTACT_IDS
				&& $fieldName !== Item::FIELD_NAME_CONTACT_BINDINGS
				&& $fieldName !== Item::FIELD_NAME_OBSERVERS
				&& $fieldName !== Item::FIELD_NAME_PRODUCTS
			);
		});

		return $select;
	}

	public function isTotalPriceSupported(): bool
	{
		return $this->factory->isLinkWithProductsEnabled();
	}

	public function isKanbanSupported(): bool
	{
		return $this->factory->isStagesEnabled();
	}

	public function isCustomPriceFieldsSupported(): bool
	{
		return false;
	}

	public function getCloseDateFieldName(): ?string
	{
		if($this->factory->isBeginCloseDatesEnabled())
		{
			return Item::FIELD_NAME_CLOSE_DATE;
		}

		return null;
	}

	public function isActivityCountersSupported(): bool
	{
		return $this->factory->isCountersEnabled();
	}

	public function getStageFieldName(): string
	{
		return Item::FIELD_NAME_STAGE_ID;
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		$fields = [];
		$fields[Item::FIELD_NAME_TITLE] = '';

		if($this->factory->isLinkWithProductsEnabled())
		{
			$fields[Item::FIELD_NAME_OPPORTUNITY] = '';
		}

		if($this->factory->isClientEnabled())
		{
			$fields['CLIENT'] = '';
		}

		return $fields;
	}

	protected function getDetailComponentName(): ?string
	{
		return 'bitrix:crm.item.details';
	}

	protected function getDetailComponent(): ?\CBitrixComponent
	{
		/** @var \CrmItemDetailsComponent $component */
		$component = parent::getDetailComponent();
		if(!$component)
		{
			return null;
		}
		$component->arParams = [
			'ENTITY_TYPE_ID' => $this->factory->getEntityTypeId(),
			'ENTITY_ID' => 0,
			'categoryId' => $this->getCategoryId(),
		];

		$component->init();
		if ($component->getErrors())
		{
			return null;
		}

		return $component;
	}

	protected function getInlineEditorConfiguration(\CBitrixComponent $component): array
	{
		/** @var \CrmItemDetailsComponent $component */
		return $component->getEditorEntityConfig();
	}

	protected function getFilter(): Filter\Filter
	{
		$settings = new Filter\ItemSettings([
			'ID' => $this->getGridId(),
			'categoryId' => $this->getCategoryId(),
		], $this->factory->getType());
		$provider = new ItemDataProvider($settings, $this->factory);
		$ufProvider = new Filter\ItemUfDataProvider($settings);

		return new Filter\Filter($settings->getID(), $provider, [$ufProvider]);
	}

	protected function getTotalSumFieldName(): string
	{
		return 'SUM';
	}

	protected function getDataToCalculateTotalSums(string $fieldSum, array $filter, array $runtime): array
	{
		if ($this->factory->isStagesEnabled())
		{
			ItemDataProvider::processStageSemanticFilter($filter, $filter);
		}
		unset($filter[ItemDataProvider::FIELD_STAGE_SEMANTIC]);

		$userPermissions = Container::getInstance()->getUserPermissions();
		$filter = $userPermissions->applyAvailableItemsFilter(
			$filter,
			[$userPermissions::getPermissionEntityType($this->getTypeId(), $this->getCategoryId())]
		);

		$queryParameters = [
			'filter' => $filter,
			'select' => [
				$this->getStageFieldName(),
				new ExpressionField($fieldSum, 'SUM(%s)', Item::FIELD_NAME_OPPORTUNITY_ACCOUNT),
				new ExpressionField('CNT', 'COUNT(1)'),
			],
		];
		if (!empty($runtime))
		{
			$queryParameters['runtime'] = $runtime;
		}
		$data = [];

		$res = $this->factory->getDataClass()::getList($queryParameters);
		while ($row = $res->fetch())
		{
			$data[] = $row;
		}

		return $data;
	}

	public function fillStageTotalSums(array $filter, array $runtime, array &$stages): void
	{
		if (isset($filter['SEARCH_CONTENT']))
		{
			SearchEnvironment::prepareSearchFilter($this->getTypeId(), $filter, [
				'ENABLE_PHONE_DETECTION' => false,
			]);
			unset($filter['SEARCH_CONTENT']);
		}

		parent::fillStageTotalSums($filter, $runtime, $stages);
	}

	public function getItems(array $parameters): \CDBResult
	{
		$enabledFields = $this->factory->getFieldsCollection()->getFieldNameList();
		$parameters['select'] = array_filter($parameters['select'], static function($field) use ($enabledFields)
		{
			return in_array($field, $enabledFields, true);
		});

		$filter = $parameters['filter'] ?? [];
		if (isset($filter['SEARCH_CONTENT']))
		{
			SearchEnvironment::prepareSearchFilter($this->getTypeId(), $filter, [
				'ENABLE_PHONE_DETECTION' => false,
			]);
			unset($filter['SEARCH_CONTENT']);
		}
		if($this->factory->isStagesEnabled())
		{
			ItemDataProvider::processStageSemanticFilter($filter, $filter);
		}
		unset($filter[ItemDataProvider::FIELD_STAGE_SEMANTIC]);
		$parameters['filter'] = $filter;
		$data = [];
		$items = $this->factory->getItemsFilteredByPermissions($parameters);
		foreach($items as $item)
		{
			$itemData = $item->getData();
			$itemData['LINK'] = Service\Container::getInstance()->getRouter()->getItemDetailUrl(
				$this->factory->getEntityTypeId(),
				$item->getId()
			);
			$itemData[Item::FIELD_NAME_TITLE] = $item->getHeading();
			if(isset($itemData[Item::FIELD_NAME_PREVIOUS_STAGE_ID]))
			{
				$stage = $this->factory->getStage($itemData[Item::FIELD_NAME_PREVIOUS_STAGE_ID]);
				$itemData[Item::FIELD_NAME_PREVIOUS_STAGE_ID] = $stage ? $stage->getName() : $itemData[Item::FIELD_NAME_PREVIOUS_STAGE_ID];
			}

			$data[] = $itemData;
		}

		$result = new \CDBResult();
		$result->InitFromArray($data);

		return $result;
	}

	public function prepareItemCommonFields(array $item): array
	{
		$item['PRICE'] = $item[Item::FIELD_NAME_OPPORTUNITY_ACCOUNT];
		$item['ASSIGNED_BY'] = $item[Item::FIELD_NAME_ASSIGNED];
		$item['DATE'] = $item['CREATED_TIME'];
		$item['DATE_CREATE'] = $item['CREATED_TIME'];

		return parent::prepareItemCommonFields($item);
	}

	public function getCategories(\CCrmPerms $permissions): array
	{
		$result = [];
		if(!$this->factory->isCategoriesSupported())
		{
			return $result;
		}
		$router = Container::getInstance()->getRouter();
		$categories = $this->factory->getCategories();
		foreach($categories as $category)
		{
			$categoryId = $category->getId();
			$result[$categoryId] = $category->getData();
			$result[$categoryId]['url'] = $router->getKanbanUrl($this->getTypeId(), $categoryId);
		}

		return $result;
	}

	public function getItemLastId(): int
	{
		if($this->itemLastId === null)
		{
			$this->itemLastId = 0;

			$items = $this->factory->getItemsFilteredByPermissions([
				'select' => ['ID'],
				'order' => [
					'ID' => 'DESC',
				],
				'limit' => 1,
			]);
			if(!empty($items))
			{
				$this->itemLastId = $items[0]->getId();
			}
		}

		return $this->itemLastId;
	}

	public function isStageEmpty(string $stageId): bool
	{
		return $this->factory->getItemsCount([
			'=STAGE_ID' => $stageId,
		]) === 0;
	}

	public function getFilterLazyLoadParams(): ?array
	{
		return null;
	}

	public function getFilterPresets(): array
	{
		return (new Filter\Preset\Dynamic())
			->setDefaultValues($this->getFilter()->getDefaultFieldIDs())
			->setStagesEnabled($this->factory->isStagesEnabled())
			->setCategoryId($this->categoryId)
			->getDefaultPresets()
		;
	}

	public function getItem(int $id): ?array
	{
		$item = $this->factory->getItem($id);

		if($item)
		{
			$this->loadedItems[$id] = $item;

			return $item->getData();
		}

		return null;
	}

	public function isNeedToRunAutomation(): bool
	{
		return false;
	}

	public function deleteItems(array $ids, bool $isIgnore = false, \CCrmPerms $permissions = null, array $params = []): void
	{
		$items = $this->factory->getItemsFilteredByPermissions([
			'filter' => [
				'@ID' => $ids,
			],
		]);
		foreach($items as $item)
		{
			$this->factory->getDeleteOperation($item)->launch();
		}
	}

	public function setItemsAssigned(array $ids, int $assignedId, \CCrmPerms $permissions): Result
	{
		$result = new Result();

		$items = $this->factory->getItemsFilteredByPermissions([
			'filter' => [
				'@ID' => $ids,
			]
		]);
		foreach($items as $item)
		{
			$item->setAssignedById($assignedId);
			$updateResult = $this->factory->getUpdateOperation($item)->launch();
			if(!$updateResult->isSuccess())
			{
				$result->addErrors($updateResult->getErrors());
			}
			elseif($this->isNeedToRunAutomation())
			{
				$this->runAutomationOnUpdate($item->getId(), $item->getData());
			}
		}

		return $result;
	}

	protected function getPopupFieldsBeforeUserFields(): array
	{
		return [];
	}

	protected function getPopupHiddenFields(): array
	{
		$fields = array_merge(parent::getPopupHiddenFields(), [
			'CONTACT.FULL_NAME', 'COMPANY.TITLE', Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID',
			'CLIENT_INFO', Item::FIELD_NAME_CONTACT_ID, Item::FIELD_NAME_COMPANY_ID,
			Item::FIELD_NAME_MYCOMPANY.'.TITLE',
		]);

		if (!$this->factory->isClientEnabled())
		{
			$fields[] = 'CLIENT';
		}

		if (!$this->factory->isLinkWithProductsEnabled())
		{
			$fields[] = 'OPPORTUNITY_WITH_CURRENCY';
		}

		return $fields;
	}

	public function updateItemsCategory(array $ids, int $categoryId, \CCrmPerms $permissions): Result
	{
		$result = new Result();

		$category = $this->factory->getCategory($categoryId);
		if (!$category)
		{
			return $result->addError(new Error('Category not found'));
		}
		if (!Service\Container::getInstance()->getUserPermissions()->canViewItemsInCategory($category))
		{
			return $result->addError(new Error('Access Denied'));
		}

		foreach($ids as $id)
		{
			$item = $this->factory->getItem($id);
			if(!$item)
			{
				continue;
			}
			$item->setCategoryId($categoryId);
			$operation = $this->factory->getUpdateOperation($item);
			$updateResult = $operation->launch();
			if(!$updateResult->isSuccess())
			{
				$result->addErrors($updateResult->getErrors());
			}
			elseif($this->isNeedToRunAutomation())
			{
				$this->runAutomationOnUpdate($item->getId(), $item->getData());
			}
		}

		return $result;
	}

	public function getRequiredFieldsByStages(array $stages): array
	{
		return static::getRequiredFieldsByStagesByFactory(
			$this->factory,
			$this->getRequiredUserFieldNames(),
			$stages,
			$this->categoryId
		);
	}

	public function getTypeInfo(): array
	{
		return array_merge(
			parent::getTypeInfo(),
			[
				'hasPlusButtonTitle' => true,
				'showPersonalSetStatusNotCompletedText' => true,
				'useFactoryBasedApproach' => true,
			]
		);
	}

	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions, string $semantics = PhaseSemantics::UNDEFINED): bool
	{
		return Container::getInstance()->getUserPermissions()->checkAddPermissions(
			$this->getTypeId(),
			$this->getCategoryId(),
			$stageId
		);
	}

	public function getUrlTemplate(): string
	{
		return Service\Container::getInstance()->getRouter()->getItemDetailUrlCompatibleTemplate($this->getTypeId());
	}

	protected function getUrl(int $id)
	{
		return Service\Container::getInstance()
			->getRouter()
			->getItemDetailUrl($this->getTypeId(), $id)
			->getPath();
	}

	public function getPopupFields(string $viewType): array
	{
		$fields = parent::getPopupFields($viewType);
		if ($viewType === static::VIEW_TYPE_EDIT)
		{
			unset(
				$fields[Item::FIELD_NAME_CREATED_BY],
				$fields[Item::FIELD_NAME_UPDATED_BY],
				$fields[Item::FIELD_NAME_MOVED_BY],
				$fields[Item::FIELD_NAME_CREATED_TIME],
				$fields[Item::FIELD_NAME_UPDATED_TIME],
				$fields[Item::FIELD_NAME_MOVED_TIME]
			);
		}

		return $fields;
	}
}
