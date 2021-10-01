<?php

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Restriction\ItemsMutator;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('crm');

class CrmItemListComponent extends Bitrix\Crm\Component\ItemList
{
	protected const DEFAULT_PAGE_SIZE = 10;

	protected $defaultGridSort = [
		'ID' => 'desc',
	];
	/** @var Bitrix\Main\Grid\Options */
	protected $gridOptions;
	protected $visibleColumns;
	protected $parentEntityTypeId;
	protected $parentEntityId;
	protected $parents = [];
	protected $webForms = [];

	protected function init(): void
	{
		parent::init();

		if($this->getErrors())
		{
			return;
		}

		if (isset($this->arParams['parentEntityTypeId']))
		{
			$this->parentEntityTypeId = (int) $this->arParams['parentEntityTypeId'];
		}

		if (isset($this->arParams['parentEntityId']))
		{
			$this->parentEntityId = (int) $this->arParams['parentEntityId'];
		}

		$this->gridOptions = new Bitrix\Main\Grid\Options($this->getGridId());
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->getApplication()->SetTitle(htmlspecialcharsbx($this->getTitle()));

		$this->arResult['grid'] = $this->prepareGrid();
		$this->arResult['interfaceToolbar'] = $this->prepareInterfaceToolbar();
		$this->arResult['jsParams'] = [
			'entityTypeId' => $this->entityTypeId,
			'entityTypeName' => \CCrmOwnerType::ResolveName($this->entityTypeId),
			'categoryId' => $this->category ? $this->category->getId() : 0,
			'gridId' => $this->getGridId(),
			'backendUrl' => $this->arParams['backendUrl'] ?? null,
		];

		$this->includeComponentTemplate();
	}

	protected function getGridId(): string
	{
		$gridId = parent::getGridId();

		if ($this->parentEntityTypeId > 0)
		{
			$gridId .= 'parent_' . $this->parentEntityTypeId;
		}

		return $gridId;
	}

	protected function prepareGrid(): array
	{
		$grid = [];
		$grid['GRID_ID'] = $this->getGridId();
		$grid['COLUMNS'] = array_merge($this->provider->getGridColumns(), $this->ufProvider->getGridColumns());

		$navParams = $this->gridOptions->getNavParams(['nPageSize' => static::DEFAULT_PAGE_SIZE]);
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $this->gridOptions->getSorting(['sort' => $this->defaultGridSort]);
		$pageNavigation = $this->getPageNavigation($pageSize);
		$listFilter = $this->getListFilter();

		if (isset($listFilter['@ID']) && empty($listFilter['@ID']))
		{
			$rows = [];
			$totalCount = 0;
		}
		else
		{
			$order = $this->validateOrder($gridSort['sort']);
			$list = $this->factory->getItemsFilteredByPermissions([
				'select' => $this->getSelect(),
				'order' => $order,
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit(),
				'filter' => $listFilter,
			]);
			$rows = $this->prepareGridRows($list);
			$totalCount = $this->factory->getItemsCountFilteredByPermissions($listFilter);
		}

		$grid['ROWS'] = $rows;
		$pageNavigation->setRecordCount($totalCount);
		$grid['NAV_PARAM_NAME'] = $this->navParamName;
		$grid['CURRENT_PAGE'] = $pageNavigation->getCurrentPage();
		$grid['NAV_OBJECT'] = $pageNavigation;
		$grid['TOTAL_ROWS_COUNT'] = $totalCount;
		$grid['AJAX_MODE'] = ($this->arParams['ajaxMode'] ?? 'Y');
		$grid['ALLOW_ROWS_SORT'] = false;
		$grid['AJAX_OPTION_JUMP'] = "N";
		$grid['AJAX_OPTION_STYLE'] = "N";
		$grid['AJAX_OPTION_HISTORY'] = "N";
		$grid['SHOW_PAGESIZE'] = true;
		$grid['PAGE_SIZES'] = [['NAME' => 10, 'VALUE' => 10], ['NAME' => 20, 'VALUE' => 20], ['NAME' => 50, 'VALUE' => 50]];
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ACTION_PANEL'] = false;
		$grid['SHOW_PAGINATION'] = true;
		$grid['ALLOW_CONTEXT_MENU'] = false;
		$grid['SHOW_SELECTED_COUNTER'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_ROW_ACTIONS_MENU'] = true;

		return $grid;
	}

	protected function prepareInterfaceToolbar(): array
	{
		$toolbar = [];
		if($this->parentEntityTypeId > 0)
		{
			$entityTypeDescription = $this->factory->getEntityDescription();

			$url = Container::getInstance()
				->getRouter()
				->getItemDetailUrl(
					$this->entityTypeId,
					0,
					null,
					$this->getParentItemIdentifier()
				)
			;

			$toolbar['id'] = $this->getGridId() . '_toolbar';
			$addButton = [
				'TEXT' => $entityTypeDescription,
				'TITLE' => Loc::getMessage(
					'CRM_ITEM_LIST_ADD_CHILDREN_ELEMENT',
					[
						'#CHILDREN_ELEMENT#' => $entityTypeDescription,
					]
				),
				'LINK' => $url,
				'ICON' => 'btn-new',
			];
			$toolbar['buttons'] = [$addButton];
		}

		return $toolbar;
	}

	protected function getPageNavigation(int $pageSize): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(true)->setPageSize($pageSize)->initFromUri();

		return $pageNavigation;
	}

	protected function getListFilter(): array
	{
		$filter = $this->getBindingFilter();
		if (!empty($filter))
		{
			return $filter;
		}

		$filterOptions = new Options($this->getGridId(), $this->kanbanEntity->getFilterPresets());
		$filterFields = $this->getDefaultFilterFields();
		$requestFilter = $filterOptions->getFilter($filterFields);

		$filter = [];
		$this->provider->prepareListFilter($filter, $requestFilter);
		$this->ufProvider->prepareListFilter($filter, $filterFields, $requestFilter);
		if($this->category)
		{
			$filter = $this->category->getItemsFilter($filter);
		}

		return $filter;
	}

	protected function getBindingFilter(): ?array
	{
		if ($this->parentEntityId && $this->parentEntityTypeId)
		{
			$relationManager = \Bitrix\Crm\Service\Container::getInstance()->getRelationManager();
			$parentItemIdentifier = $this->getParentItemIdentifier();
			$childElements = array_unique($relationManager->getChildElements($parentItemIdentifier));

			$ids = [];
			foreach($childElements as $element)
			{
				if ($element->getEntityTypeId() === $this->entityTypeId)
				{
					$ids[$element->getEntityId()] = $element->getEntityId();
				}
			}

			return [
				'@ID' => $ids
			];
		}

		return null;
	}

	protected function getParentItemIdentifier(): ItemIdentifier
	{
		return new ItemIdentifier($this->parentEntityTypeId, $this->parentEntityId);
	}

	protected function getSelect(): array
	{
		// Some columns use references to compile their display data
		$referenceToDependantColumnsMap = [
			Item::FIELD_NAME_COMPANY => [Item::FIELD_NAME_CONTACT_ID, Item::FIELD_NAME_COMPANY_ID, 'CLIENT_INFO'],
			Item::FIELD_NAME_CONTACTS => [Item::FIELD_NAME_CONTACT_ID, 'CLIENT_INFO'],
			Item::FIELD_NAME_MYCOMPANY => [Item::FIELD_NAME_MYCOMPANY_ID],
			Item::FIELD_NAME_PRODUCTS => [Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'],
		];

		$visibleColumns = $this->getVisibleColumns();

		$select = ['*'];
		foreach ($referenceToDependantColumnsMap as $referenceName => $columnNames)
		{
			if (array_intersect($visibleColumns, $columnNames) && $this->factory->isFieldExists($referenceName))
			{
				$select[] = $referenceName;
			}
		}

		return $select;
	}

	protected function isColumnVisible(string $columnName): bool
	{
		return in_array($columnName, $this->getVisibleColumns(), true);
	}

	protected function getVisibleColumns(): array
	{
		if($this->visibleColumns === null)
		{
			$this->visibleColumns = $this->gridOptions->getVisibleColumns();
			if(empty($this->visibleColumns))
			{
				$this->visibleColumns = array_column(
					array_merge(
						$this->provider->getGridColumns(),
						$this->ufProvider->getGridColumns()
					),
					'id'
				);
			}
		}

		return $this->visibleColumns;
	}

	/**
	 * @param Item[] $list
	 *
	 * @return array
	 */
	protected function prepareGridRows(array $list): array
	{
		$result = [];
		if(count($list) > 0)
		{
			$userIds = [];
			$itemIds = [];
			foreach($list as $item)
			{
				foreach ($this->provider->getFieldNamesByType(ItemDataProvider::TYPE_USER, ItemDataProvider::DISPLAY_IN_GRID) as $columnName)
				{
					/** @var int|null $userId */
					$userId = $item->get($columnName);
					$userIds[$userId] = $userId;
				}
				$itemIds[] = $item->getId();
			}
			$this->users = Container::getInstance()->getUserBroker()->getBunchByIds($userIds);
			$this->webForms = Bitrix\Crm\WebForm\Manager::getListNames();

			$this->parents = Container::getInstance()->getParentFieldManager()->getParentFields(
				$itemIds,
				$this->getVisibleColumns(),
				$this->entityTypeId
			);

			$itemsData = [];
			$itemsColumns = [];
			foreach($list as $item)
			{
				$itemData = $this->getItemData($item);
				$itemsData[$itemData['ID']] = $itemData;
				$itemsColumns[$itemData['ID']] = array_merge($itemData, $this->getItemColumn($item));
			}
			$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getWebFormResultsRestriction();
			if (!$restriction->hasPermission())
			{
				$itemIds = array_column($itemsData, 'ID');
				$restrictedItemIds = $restriction->filterRestrictedItemIds(
					$this->entityTypeId,
					$itemIds
				);
				$restrictedItemIds = array_flip($restrictedItemIds);
				if (!empty($restrictedItemIds))
				{
					$mutator = new ItemsMutator($restriction->getFieldsToShow());
					foreach ($itemsData as &$item)
					{
						if (isset($restrictedItemIds[$item['ID']]))
						{
							$item = $mutator->processItem($item, '<img onclick="if(BX && BX.onCustomEvent){BX.onCustomEvent(window, \'onCrmRestrictedValueClick\')}" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyIiB2aWV3Qm94PSIwIDAgMTI4IDEyIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHg9IjQyIiB3aWR0aD0iMjIiIGhlaWdodD0iMTIiIGZpbGw9IiNFREVFRUYiLz48cmVjdCB4PSI2NCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjEyIiBmaWxsPSIjRTdFOEVBIi8+PHJlY3QgeD0iODQiIHdpZHRoPSIyMiIgaGVpZ2h0PSIxMiIgZmlsbD0iI0VCRUNFRSIvPjxyZWN0IHg9IjEwNiIgd2lkdGg9IjIyIiBoZWlnaHQ9IjEyIiBmaWxsPSIjRjdGN0Y4Ii8+PHJlY3Qgd2lkdGg9IjQ0IiBoZWlnaHQ9IjEyIiBmaWxsPSIjRUFFQkVEIi8+PHJlY3Qgd2lkdGg9IjQ0IiBoZWlnaHQ9IjEyIiBmaWxsPSIjRUFFQkVEIi8+PC9zdmc+Cg=="/>');
						}
					}
					foreach ($itemsColumns as &$item)
					{
						if (isset($restrictedItemIds[$item['ID']]))
						{
							$item = $mutator->processItem($item, '<img onclick="if(BX && BX.onCustomEvent){BX.onCustomEvent(window, \'onCrmRestrictedValueClick\')}" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyIiB2aWV3Qm94PSIwIDAgMTI4IDEyIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHg9IjQyIiB3aWR0aD0iMjIiIGhlaWdodD0iMTIiIGZpbGw9IiNFREVFRUYiLz48cmVjdCB4PSI2NCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjEyIiBmaWxsPSIjRTdFOEVBIi8+PHJlY3QgeD0iODQiIHdpZHRoPSIyMiIgaGVpZ2h0PSIxMiIgZmlsbD0iI0VCRUNFRSIvPjxyZWN0IHg9IjEwNiIgd2lkdGg9IjIyIiBoZWlnaHQ9IjEyIiBmaWxsPSIjRjdGN0Y4Ii8+PHJlY3Qgd2lkdGg9IjQ0IiBoZWlnaHQ9IjEyIiBmaWxsPSIjRUFFQkVEIi8+PHJlY3Qgd2lkdGg9IjQ0IiBoZWlnaHQ9IjEyIiBmaWxsPSIjRUFFQkVEIi8+PC9zdmc+Cg=="/>');
						}
					}
					unset($item);
					$this->arResult['RESTRICTED_VALUE_CLICK_CALLBACK'] = $restriction->prepareInfoHelperScript();
				}
			}
			foreach($list as $item)
			{
				$result[] = [
					'id' => $item->getId(),
					'data' => $itemsData[$item->getId()],
					'columns' => $itemsColumns[$item->getId()],
					'actions' => $this->getContextActions($item),
				];
			}
		}

		return $result;
	}

	protected function getContextActions(Item $item): array
	{
		$jsEventData = CUtil::PhpToJSObject(['entityTypeId' => $this->entityTypeId, 'id' => $item->getId()]);

		$actions = [
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
				'HREF' => Container::getInstance()->getRouter()->getItemDetailUrl($this->entityTypeId, $item->getId()),
			],
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_DELETE'),
				'ONCLICK' => "BX.Event.EventEmitter.emit('BX.Crm.ItemListComponent:onClickDelete', {$jsEventData})",
			],
		];

		return $actions;
	}

	protected function getItemData(Item $item): array
	{
		$itemData = $item->getData();

		$this->display->addValues($item->getId(), $itemData);
		$preparedUfData = $this->display->getValues($item->getId());
		if ($preparedUfData)
		{
			$itemData = array_merge($itemData, $preparedUfData);
		}

		return $itemData;
	}

	/**
	 * Prepare data to be displayed in a grid row
	 *
	 * @param Item $item
	 *
	 * @return array
	 */
	protected function getItemColumn(Item $item): array
	{
		$result = [];
		foreach ($this->provider->getFieldNamesByType(ItemDataProvider::TYPE_USER, ItemDataProvider::DISPLAY_IN_GRID) as $columnName)
		{
			$userId = $item->get($columnName);
			if (isset($this->users[$userId]))
			{
				$result[$columnName] = $this->prepareUserDataForGrid($this->users[$userId]);
			}
			else
			{
				$result[$columnName] = '';
			}
		}

		foreach ($this->provider->getFieldNamesByType(ItemDataProvider::TYPE_BOOLEAN, ItemDataProvider::DISPLAY_IN_GRID) as $columnName)
		{
			if ($item->get($columnName))
			{
				$result[$columnName] = Loc::getMessage('CRM_COMMON_GRID_YES');
			}
			else
			{
				$result[$columnName] = Loc::getMessage('CRM_COMMON_GRID_NO');
			}
		}

		$result[Item::FIELD_NAME_WEBFORM_ID] = $this->webForms[$item->getWebformId()] ?? '';

		$detailUrl = htmlspecialcharsbx(Container::getInstance()->getRouter()->getItemDetailUrl($this->entityTypeId, $item->getId()));
		$result['TITLE'] = '<a href="'.$detailUrl.'">'.htmlspecialcharsbx($item->getTitle()).'</a>';

		$this->appendOptionalColumns($item, $result);
		$this->appendParentColumns($item, $result);

		return $result;
	}

	protected function appendOptionalColumns(Item $item, array &$columns): void
	{
		if ($this->factory->isClientEnabled())
		{
			$columns[Item::FIELD_NAME_COMPANY_ID] = $this->getCompanyItemColumn($item->getCompany());
			$columns[Item::FIELD_NAME_CONTACT_ID] = $this->getContactItemColumn($item);
			if (!empty($columns[Item::FIELD_NAME_CONTACT_ID]))
			{
				$columns['CLIENT_INFO'] = $columns[Item::FIELD_NAME_CONTACT_ID];
			}
			elseif (!empty($columns[Item::FIELD_NAME_COMPANY_ID]))
			{
				$columns['CLIENT_INFO'] = $columns[Item::FIELD_NAME_COMPANY_ID];
			}
		}

		if ($this->factory->isLinkWithProductsEnabled())
		{
			if ($this->isColumnVisible(Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'))
			{
				$columns[Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'] = $this->getProductsItemColumn($item);
			}
			$columns['OPPORTUNITY_WITH_CURRENCY'] = Bitrix\Crm\Format\Money::format($item->getOpportunity(), $item->getCurrencyId());
			$columns[Item::FIELD_NAME_OPPORTUNITY] = number_format($item->getOpportunity(), 2, '.', '');
			$columns[Item::FIELD_NAME_CURRENCY_ID] = htmlspecialcharsbx(\Bitrix\Crm\Currency::getCurrencyCaption($item->getCurrencyId()));
		}

		if ($this->factory->isCrmTrackingEnabled())
		{
			if ($this->isColumnVisible(\Bitrix\Crm\Tracking\UI\Grid::COLUMN_TRACKING_PATH))
			{
				\Bitrix\Crm\Tracking\UI\Grid::appendRows($this->factory->getEntityTypeId(), $item->getId(), $columns);
			}

			$utmColumns = \Bitrix\Crm\UtmTable::getCodeList();
			if (array_intersect($this->getVisibleColumns(), $utmColumns))
			{
				/** @noinspection AdditionOperationOnArraysInspection */
				$columns += $item->getUtm();
			}
		}

		if ($this->factory->isMyCompanyEnabled() && $this->isColumnVisible(Item::FIELD_NAME_MYCOMPANY_ID))
		{
			$columns[Item::FIELD_NAME_MYCOMPANY_ID] = $this->getCompanyItemColumn($item->getMycompany());
		}

		if ($this->factory->isStagesEnabled())
		{
			if ($this->isColumnVisible(Item::FIELD_NAME_STAGE_ID))
			{
				$stage = $this->factory->getStage($item->getStageId());
				$columns[Item::FIELD_NAME_STAGE_ID] = $stage ? htmlspecialcharsbx($stage->getName()) : null;
			}
			if ($this->isColumnVisible(Item::FIELD_NAME_PREVIOUS_STAGE_ID))
			{
				$stage = $this->factory->getStage($item->get(Item::FIELD_NAME_PREVIOUS_STAGE_ID));
				$columns[Item::FIELD_NAME_PREVIOUS_STAGE_ID] = $stage ? htmlspecialcharsbx($stage->getName()) : null;
			}
		}

		if ($this->factory->isSourceEnabled())
		{
			if ($this->isColumnVisible(Item::FIELD_NAME_SOURCE_ID))
			{
				$columns[Item::FIELD_NAME_SOURCE_ID] = htmlspecialcharsbx(
					$this->factory->getFieldValueCaption(Item::FIELD_NAME_SOURCE_ID, $item->getSourceId())
					);
			}

			if ($this->isColumnVisible(Item::FIELD_NAME_SOURCE_DESCRIPTION))
			{
				$columns[Item::FIELD_NAME_SOURCE_DESCRIPTION] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($item->getSourceDescription());
			}
		}

		if ($this->factory->isCategoriesEnabled())
		{
			if ($this->isColumnVisible(Item::FIELD_NAME_CATEGORY_ID))
			{
				$columns[Item::FIELD_NAME_CATEGORY_ID] = htmlspecialcharsbx(
					$this->factory->getFieldValueCaption(Item::FIELD_NAME_CATEGORY_ID, $item->getCategoryId())
				);
			}
		}
	}

	protected function appendParentColumns(Item $item, array &$columns): void
	{
		if (isset($this->parents[$item->getId()]))
		{
			foreach ($this->parents[$item->getId()] as $parentEntityTypeId => $parent)
			{
				$columns[$parent['code']] = $parent['value'];
			}
		}
	}

	protected function getProductsItemColumn(Item $item): string
	{
		$productNames = [];
		foreach ($item->getProductRows() as $product)
		{
			$productNames[] = htmlspecialcharsbx($product->getProductName());
		}

		return implode(', ', $productNames);
	}

	protected function getCompanyItemColumn(?\Bitrix\Crm\EO_Company $company): string
	{
		return $this->prepareClientInfo(
			CCrmOwnerType::Company,
			$company ? $company->getId() : 0,
			$company ? $company->getTitle() : ''
		);
	}

	protected function getContactItemColumn(Item $item): string
	{
		if ($item->getContactId() <= 0 || !$item->getContacts())
		{
			return '';
		}

		$contact = $item->getPrimaryContact();
		$company = $item->getCompany();

		/** @noinspection NullPointerExceptionInspection */
		return $this->prepareClientInfo(
			CCrmOwnerType::Contact,
			$contact->getId(),
			$contact->getFormattedName(),
			$company ? $company->getTitle() : ''
		);
	}

	protected function prepareClientInfo(int $entityTypeId, int $id, string $title, string $description = ''): string
	{
		$canReadItem = \Bitrix\Crm\Security\EntityAuthorization::checkReadPermission($entityTypeId, $id);
		return CCrmViewHelper::PrepareClientInfo([
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $id,
			'IS_HIDDEN' => !$canReadItem,
			'TITLE' => $title,
			'PREFIX' => $this->factory->getEntityName().'_'.$id,
			'DESCRIPTION' => $description,
		]);
	}

	protected function getTitle(): string
	{
		return $this->kanbanEntity->getTitle();
	}

	protected function getToolbarCategories(array $categories): array
	{
		$menu = parent::getToolbarCategories($categories);
		array_unshift(
			$menu,
			[
				'id' => 'toolbar-category-all',
				'text' => Loc::getMessage('CRM_TYPE_TOOLBAR_ALL_ITEMS'),
				'href' => $this->getListUrl(0),
			]
		);

		return $menu;
	}

	protected function validateOrder(?array $order): array
	{
		$order = (array)$order;
		$result = [];

		$fakeItem = $this->factory->createItem();
		foreach ($order as $field => $direction)
		{
			$direction = mb_strtolower($direction);
			if ($direction !== 'asc' && $direction !== 'desc')
			{
				continue;
			}
			if ($fakeItem->hasField($field))
			{
				$result[$field] = $direction;
			}
		}

		return $result;
	}

	protected function getListViewType(): string
	{
		return Router::LIST_VIEW_LIST;
	}
}
