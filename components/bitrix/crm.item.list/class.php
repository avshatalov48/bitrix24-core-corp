<?php

use Bitrix\Crm\Component\EntityList\FieldRestrictionManager;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManagerTypes;
use Bitrix\Crm\Filter\UiFilterOptions;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Restriction\ItemsMutator;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Buttons;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('crm');

class CrmItemListComponent extends Bitrix\Crm\Component\ItemList
{
	protected const DEFAULT_PAGE_SIZE = 10;

	protected $defaultGridSort = [
		'ID' => 'desc',
	];

	protected Grid\Options $gridOptions;
	protected UiFilterOptions $filterOptions;
	protected $visibleColumns;
	protected $parentEntityTypeId;
	protected $parentEntityId;
	protected $parents = [];
	protected $webForms = [];
	protected $exportType;
	protected $notAccessibleFields;
	protected FieldRestrictionManager $fieldRestrictionManager;

	protected function init(): void
	{
		parent::init();

		if($this->getErrors())
		{
			return;
		}

		if (
			isset(
				$this->arParams['EXPORT_TYPE'],
				$this->arParams['STEXPORT_MODE'],
				$this->arParams['STEXPORT_PAGE_SIZE'],
				$this->arParams['PAGE_NUMBER'],
			)
			&& $this->arParams['STEXPORT_MODE'] === 'Y'
		)
		{
			$this->exportType = $this->arParams['EXPORT_TYPE'];
		}

		if (!$this->isExportMode())
		{
			if (isset($this->arParams['parentEntityTypeId']))
			{
				$this->parentEntityTypeId = (int) $this->arParams['parentEntityTypeId'];
			}

			if (isset($this->arParams['parentEntityId']))
			{
				$this->parentEntityId = (int) $this->arParams['parentEntityId'];
			}
		}

		$this->filterOptions = new UiFilterOptions($this->getGridId(), $this->kanbanEntity->getFilterPresets());
		$this->gridOptions = new Grid\Options($this->getGridId());

		$this->fieldRestrictionManager = new FieldRestrictionManager(
			FieldRestrictionManager::MODE_GRID,
			[FieldRestrictionManagerTypes::ACTIVITY]
		);

	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$restriction = RestrictionManager::getItemListRestriction($this->entityTypeId);
		if (!$restriction->hasPermission())
		{
			$this->arResult['restriction'] = $restriction;
			$this->arResult['entityName'] = \CCrmOwnerType::ResolveName($this->entityTypeId);
			$this->includeComponentTemplate('restrictions');
			return;
		}

		if ($this->isExportMode())
		{
			return $this->processExport();
		}

		$this->processGridActions();

		$this->getApplication()->SetTitle(htmlspecialcharsbx($this->getTitle()));

		$listFilter = $this->getListFilter();

		// transform ACTIVITY_COUNTER filter to real filter params
		CCrmEntityHelper::applyCounterFilterWrapper(
			$this->entityTypeId,
			$this->getGridId(),
			\Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
			$listFilter,
			null
		);

		$this->fieldRestrictionManager->removeRestrictedFields($this->filterOptions, $this->gridOptions);

		$navParams = $this->gridOptions->getNavParams(['nPageSize' => static::DEFAULT_PAGE_SIZE]);
		$pageSize = (int)$navParams['nPageSize'];
		$pageNavigation = $this->getPageNavigation($pageSize);
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeId);
		$this->arResult['grid'] = $this->prepareGrid(
			$listFilter,
			$pageNavigation,
			$this->gridOptions->getSorting(['sort' => $this->defaultGridSort])
		);
		$this->arResult['interfaceToolbar'] = $this->prepareInterfaceToolbar();
		$this->arResult['jsParams'] = [
			'entityTypeId' => $this->entityTypeId,
			'entityTypeName' => $entityTypeName,
			'categoryId' => $this->category ? $this->category->getId() : 0,
			'gridId' => $this->getGridId(),
			'backendUrl' => $this->arParams['backendUrl'] ?? null,
			'isUniversalActivityScenarioEnabled' => \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled(),
			'smartActivityNotificationSupported' => $this->factory->isSmartActivityNotificationSupported(),
			'isIframe' => $this->isIframe(),
			'isEmbedded' => ($this->arParams['isEmbedded'] ?? false) === true,
		];
		$this->arResult['entityTypeName'] = $entityTypeName;
		$this->arResult['categoryId'] = $this->category ? $this->category->getId() : 0;
		$this->arResult['entityTypeDescription'] = $this->factory->getEntityDescription();

		$restrictedFields = $this->fieldRestrictionManager->fetchRestrictedFields(
			$this->getGridId() ?? '',
			[],
				$this->filter
		);
		$this->arResult = array_merge($this->arResult, $restrictedFields);

		$this->includeComponentTemplate();
	}

	protected function processGridActions(): void
	{
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		if (
			$request->getRequestMethod() !== 'POST'
			|| !check_bitrix_sessid()
		)
		{
			return;
		}
		$removeActionButtonParamName = 'action_button_' . $this->getGridId();
		if ($request->getPost($removeActionButtonParamName) === 'delete')
		{
			$ids = $request->getPost('ID');
			if (!is_array($ids))
			{
				return;
			}
			\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($ids);
			if (empty($ids))
			{
				return;
			}
			$items = $this->factory->getItemsFilteredByPermissions(
				[
					'filter' => [
						'@ID' => $ids,
					]
				],
				null,
				\Bitrix\Crm\Service\UserPermissions::OPERATION_DELETE
			);
			foreach ($items as $item)
			{
				$operation = $this->factory->getDeleteOperation($item);
				// permissions have been checked above
				$operation->disableCheckAccess();
				$operation->launch();
			}
		}
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

	protected function getNotAccessibleFieldNames(): array
	{
		if ($this->notAccessibleFields === null)
		{
			$this->notAccessibleFields = array_flip(VisibilityManager::getNotAccessibleFields($this->entityTypeId));
		}

		return $this->notAccessibleFields;
	}

	protected function prepareGrid(array $listFilter, PageNavigation $pageNavigation, array $gridSort): array
	{
		$grid = [];
		$grid['GRID_ID'] = $this->getGridId();
		$grid['COLUMNS'] = array_merge($this->provider->getGridColumns(), $this->ufProvider->getGridColumns());
		$notAccessibleFields = $this->getNotAccessibleFieldNames();
		foreach ($grid['COLUMNS'] as $key => $column)
		{
			if (isset($column['id'], $notAccessibleFields[$column['id']]))
			{
				unset($grid['COLUMNS'][$key]);
				$grid['COLUMNS'] = array_values($grid['COLUMNS']);
			}
		}

		if (isset($listFilter['@ID']) && empty($listFilter['@ID']))
		{
			$rows = [];
			$totalCount = 0;
		}
		else
		{
			$order = $this->validateOrder($gridSort['sort']);
			$list = $this->factory->getItemsFilteredByPermissions(
				[
					'select' => $this->getSelect(),
					'order' => $order,
					'offset' => $pageNavigation->getOffset(),
					'limit' => $pageNavigation->getLimit(),
					'filter' => $listFilter,
				],
				$this->userPermissions->getUserId(),
				$this->isExportMode()
					? \Bitrix\Crm\Service\UserPermissions::OPERATION_EXPORT
					: \Bitrix\Crm\Service\UserPermissions::OPERATION_READ
			);
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
		$grid['DEFAULT_PAGE_SIZE'] = static::DEFAULT_PAGE_SIZE;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['SHOW_PAGINATION'] = true;
		$grid['ALLOW_CONTEXT_MENU'] = false;
		$grid['SHOW_SELECTED_COUNTER'] = false;
		$grid['SHOW_ROW_ACTIONS_MENU'] = true;
		$grid['ENABLE_FIELDS_SEARCH'] = 'Y';
		$grid['HEADERS_SECTIONS'] = $this->getHeaderSections();
		$canDelete = Container::getInstance()->getUserPermissions()->checkDeletePermissions(
			$this->factory->getEntityTypeId(),
			0,
			(int)$this->getCategoryId()
		);
		$grid['SHOW_ROW_CHECKBOXES'] = $canDelete;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = $canDelete;
		$grid['SHOW_ACTION_PANEL'] = $canDelete;
		if ($canDelete)
		{
			$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
			$grid['ACTION_PANEL'] = [
				'GROUPS' => [
					[
						'ITEMS' => [
							$snippet->getRemoveButton(),
						],
					],
				]
			];
		}

		return $grid;
	}

	protected function prepareInterfaceToolbar(): array
	{
		$toolbar = [];
		if ($this->parentEntityTypeId > 0 && $this->entityTypeId !== CCrmOwnerType::SmartDocument) // disable direct creation of smart documents from grid
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

			$relation = Container::getInstance()->getRelationManager()->getRelation(new \Bitrix\Crm\RelationIdentifier(
				$this->parentEntityTypeId,
				$this->entityTypeId
			));
			if ($relation && $relation->getSettings()->isConversion())
			{
				$addButton['ATTRIBUTES'] = [
					'data-role' => 'add-new-item-button-' . $this->getGridId(),
				];
			}
			$toolbar['buttons'] = [$addButton];
		}

		return $toolbar;
	}

	protected function getPageNavigation(int $pageSize): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(false)->setPageSize($pageSize)->initFromUri();

		return $pageNavigation;
	}

	protected function getListFilter(): array
	{
		$filter = $this->getBindingFilter();
		if (!empty($filter))
		{
			return $filter;
		}

		$filterFields = $this->getDefaultFilterFields();
		$requestFilter = $this->filterOptions->getFilter($filterFields);

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
			$relationManager = Container::getInstance()->getRelationManager();
			$relation = $relationManager->getRelation(
				new \Bitrix\Crm\RelationIdentifier(
					$this->parentEntityTypeId,
					$this->entityTypeId,
				)
			);
			if (!$relation)
			{
				return null;
			}
			$parentItemIdentifier = $this->getParentItemIdentifier();
			$childElements = array_unique($relation->getChildElements($parentItemIdentifier));

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

	protected function isExportMode(): bool
	{
		return $this->exportType !== null;
	}

	protected function processExport(): array
	{
		if ($this->getErrors())
		{
			return ['ERROR' => implode("", $this->getErrorMessages())];
		}

		$listFilter = $this->getListFilter();
		if (!isset($this->arParams['STEXPORT_TOTAL_ITEMS']) || $this->arParams['STEXPORT_TOTAL_ITEMS'] <= 0)
		{
			$totalCount = $this->factory->getItemsCountFilteredByPermissions(
				$listFilter,
				$this->userPermissions->getUserId(),
				$this->userPermissions::OPERATION_EXPORT
			);
			$lastExportedId = -1;
		}
		else
		{
			$totalCount = $this->arParams['STEXPORT_TOTAL_ITEMS'];
			$lastExportedId = $this->arParams['STEXPORT_LAST_EXPORTED_ID'];
			$listFilter['>ID'] = $lastExportedId;
		}

		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation
			->allowAllRecords(false)
			->setPageSize($this->arParams['STEXPORT_PAGE_SIZE'])
			->setCurrentPage(1);

		$this->setTemplateName($this->exportType);

		$grid = $this->prepareGrid(
			$listFilter,
			$pageNavigation,
			['sort' => ['ID' => 'asc']]
		);

		$this->arResult['HEADERS'] = [];
		$columns = array_flip(array_column($grid['COLUMNS'], 'id'));
		foreach ($this->getVisibleColumns() as $columnId)
		{
			if (isset($columns[$columnId]))
			{
				$this->arResult['HEADERS'][] = $grid['COLUMNS'][$columns[$columnId]];
			}
		}
		$items = array_column($grid['ROWS'], 'columns');
		$this->arResult['ITEMS'] = $items;

		$lastExportedId = end($items)['ID'];

		$pageNumber = $this->arParams['PAGE_NUMBER'];
		$lastPageNumber = ceil((int) $totalCount / (int) $this->arParams['STEXPORT_PAGE_SIZE']);

		$this->arResult['FIRST_EXPORT_PAGE'] = $pageNumber <= 1;
		$this->arResult['LAST_EXPORT_PAGE'] = $pageNumber >= $lastPageNumber;
		$this->includeComponentTemplate();

		$returnValues = [
			'PROCESSED_ITEMS' => count($items),
			'LAST_EXPORTED_ID' => $lastExportedId ?? 0,
			'TOTAL_ITEMS' => $totalCount,
		];

		return $returnValues;
	}

	protected function getSelect(): array
	{
		// Some columns use references to compile their display data
		$displayedFieldToDependenciesMap = [
			Item::FIELD_NAME_CONTACT_ID => [Item::FIELD_NAME_COMPANY, /* Item::FIELD_NAME_CONTACTS */],
			Item::FIELD_NAME_COMPANY_ID => [Item::FIELD_NAME_COMPANY],
			'CLIENT_INFO' => [Item::FIELD_NAME_CONTACT_ID, Item::FIELD_NAME_COMPANY_ID, Item::FIELD_NAME_COMPANY],
			Item::FIELD_NAME_MYCOMPANY_ID => [Item::FIELD_NAME_MYCOMPANY],
			'OPPORTUNITY_WITH_CURRENCY' => [Item::FIELD_NAME_OPPORTUNITY, Item::FIELD_NAME_CURRENCY_ID],
			// Item::FIELD_NAME_PRODUCTS . '.PRODUCT_ID' => [Item::FIELD_NAME_PRODUCTS],
			Item::FIELD_NAME_TITLE => [
				Item::FIELD_NAME_ID,
				Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER,
				Item\Quote::FIELD_NAME_BEGIN_DATE,
				Item\Quote::FIELD_NAME_NUMBER,
			],
		];

		$visibleColumns = $this->getVisibleColumns();

		$select = [];
		foreach ($visibleColumns as $columnName)
		{
			if ($this->factory->isFieldExists($columnName))
			{
				$select[] = $columnName;
			}
			elseif (isset($displayedFieldToDependenciesMap[$columnName]))
			{
				foreach ($displayedFieldToDependenciesMap[$columnName] as $dependencyField)
				{
					if ($this->factory->isFieldExists($dependencyField))
					{
						$select[] = $dependencyField;
					}
				}
			}
		}

		return array_unique($select);
	}

	protected function isColumnVisible(string $columnName): bool
	{
		return in_array($columnName, $this->getVisibleColumns(), true);
	}

	protected function getVisibleColumns(): array
	{
		if ($this->visibleColumns === null)
		{
			$this->visibleColumns = $this->gridOptions->getVisibleColumns();
			if (empty($this->visibleColumns))
			{
				$this->visibleColumns = array_filter(
					array_merge(
						$this->provider->getGridColumns(),
						$this->ufProvider->getGridColumns()
					),
					static function($column) {
						return isset($column['default']) && $column['default'] === true;
					}
				);

				$this->visibleColumns = array_column($this->visibleColumns, 'id');
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
			$this->webForms = Bitrix\Crm\WebForm\Manager::getListNamesEncoded();

			$isExportEventEnabled = HistorySettings::getCurrent()->isExportEventEnabled();
			$notAccessibleFields = $this->getNotAccessibleFieldNames();
			$itemsData = [];
			foreach($list as $item)
			{
				$itemData = $item->getData();
				$itemData = array_diff_key($itemData, $notAccessibleFields);
				$itemsData[$itemData['ID']] = $itemData;

				if ($isExportEventEnabled && $this->isExportMode())
				{
					$trackedObject = $this->factory->getTrackedObject($item);
					Container::getInstance()->getEventHistory()->registerExport($trackedObject);
				}
			}

			$displayOptions =
				(new Display\Options())
					->setMultipleFieldsDelimiter($this->isExportMode() ? ', ' : '<br />')
					->setGridId($this->getGridId())
			;
			$restrictedItemIds = [];
			$itemIds = array_column($itemsData, 'ID');
			$itemsMutator = null;
			$restriction = RestrictionManager::getWebFormResultsRestriction();
			if (!$restriction->hasPermission())
			{
				$restriction->prepareDisplayOptions($this->entityTypeId, $itemIds, $displayOptions);
				$restrictedItemIds = $restriction->filterRestrictedItemIds(
					$this->entityTypeId,
					$itemIds
				);
				$restrictedItemIds = array_flip($restrictedItemIds);
				if (!empty($restrictedItemIds))
				{
					$itemsMutator = new ItemsMutator(array_merge(
						$displayOptions->getRestrictedFieldsToShow(),
						[]
					));
				}
			}
			$this->parents = Container::getInstance()->getParentFieldManager()->getParentFields(
				$itemIds,
				$this->getVisibleColumns(),
				$this->entityTypeId
			);
			$displayFields = $this->getDisplayFields();
			$displayValues =
				(new Display($this->entityTypeId, $displayFields, $displayOptions))
					->setItems($itemsData)
					->getAllValues()
			;
			$itemColumns = $itemsData;
			foreach ($displayValues as $itemId => $itemDisplayValues)
			{
				foreach ($itemDisplayValues as $fieldId => $fieldValue)
				{
					if (isset($displayFields[$fieldId]) && $displayFields[$fieldId]->isUserField())
					{
						$itemColumns[$itemId][$fieldId] = $fieldValue;
					}
					else
					{
						$itemColumns[$itemId][$fieldId] = $displayFields[$fieldId]->wasRenderedAsHtml()
							? $fieldValue
							: htmlspecialcharsbx($fieldValue)
						;
					}
				}
			}
			$isLoadProducts = $this->isColumnVisible(Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID');
			$itemProducts = [];
			if ($isLoadProducts)
			{
				foreach($list as $item)
				{
					$itemProducts[$item->getId()] = [];
				}
				if (!empty($itemProducts))
				{
					$products = \Bitrix\Crm\ProductRowTable::getList([
						'select' => [
							'PRODUCT_NAME',
							'OWNER_ID'
						],
						'filter' => [
							'=OWNER_TYPE' => \CCrmOwnerTypeAbbr::ResolveByTypeID($this->factory->getEntityTypeId()),
							'@OWNER_ID' => array_keys($itemProducts),
						],
					]);
					foreach ($products->fetchCollection() as $product)
					{
						$itemProducts[$product->getOwnerId()][] = $product;
					}
				}
			}
			foreach($list as $item)
			{
				$itemId = $item->getId();
				$itemData = $itemsData[$itemId];
				$itemColumn = $itemColumns[$itemId];
				if (isset($restrictedItemIds[$itemId]))
				{
					$valueReplacer = $this->isExportMode()
						? $displayOptions->getRestrictedValueTextReplacer()
						: $displayOptions->getRestrictedValueHtmlReplacer()
					;
					$itemData = $itemsMutator->processItem($itemData, $valueReplacer);
					$itemColumn = $itemsMutator->processItem($itemColumn, $valueReplacer);
				}
				else
				{
					$this->appendParentColumns($item, $itemColumn);
					$this->appendOptionalColumns($item, $itemColumn);
					if (!empty($itemProducts[$item->getId()]))
					{
						$itemColumn[Item::FIELD_NAME_PRODUCTS.'.PRODUCT_ID'] = $this->getProductsItemColumn($itemProducts[$item->getId()]);
					}
				}

				$result[] = [
					'id' => $itemId,
					'data' => $itemData,
					'columns' => $itemColumn,
					'actions' => $this->getContextActions($item),
				];
			}
		}

		return $result;
	}

	protected function getContextActions(Item $item): array
	{
		$jsEventData = CUtil::PhpToJSObject(['entityTypeId' => $this->entityTypeId, 'id' => $item->getId()]);

		$userPermissions = Container::getInstance()->getUserPermissions();

		$itemDetailUrl = Container::getInstance()->getRouter()->getItemDetailUrl($this->entityTypeId, $item->getId());
		$actions = [
			[
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_SHOW'),
				'HREF' => $itemDetailUrl,
			],
		];
		if ($userPermissions->canUpdateItem($item) && $itemDetailUrl)
		{
			$editUrl = clone $itemDetailUrl;
			$editUrl->addParams([
				'init_mode' => 'edit',
			]);
			$actions[] = [
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
				'HREF' => $editUrl,
			];
		}
		if ($userPermissions->canAddItem($item))
		{
			$copyUrl = clone $itemDetailUrl;
			$copyUrl->addParams([
				'copy' => '1',
			]);
			$actions[] = [
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_COPY'),
				'HREF' => $copyUrl,
			];
		}
		if ($userPermissions->canDeleteItem($item))
		{
			$actions[] = [
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_DELETE'),
				'ONCLICK' => "BX.Event.EventEmitter.emit('BX.Crm.ItemListComponent:onClickDelete', {$jsEventData})",
			];
		}

		return array_merge($actions, Integration\Intranet\BindingMenu::getGridContextActions($this->entityTypeId));
	}

	/**
	 * @return Display\Field[]
	 */
	protected function getDisplayFields(): array
	{
		$displayFields = [];

		$fieldsCollection = $this->factory->getFieldsCollection();

		$visibleColumns = $this->getVisibleColumns();
		if (in_array('CLIENT_INFO', $visibleColumns, true))
		{
			$visibleColumns[] = Item::FIELD_NAME_CONTACT_ID;
			$visibleColumns[] = Item::FIELD_NAME_COMPANY_ID;
		}

		$context = ($this->isExportMode() ? Display\Field::EXPORT_CONTEXT : Display\Field::GRID_CONTEXT);
		foreach ($visibleColumns as $fieldName)
		{
			$baseField = $fieldsCollection->getField($fieldName);
			if ($baseField)
			{
				if ($baseField->isUserField())
				{
					$displayField = Display\Field::createFromUserField($baseField->getName(), $baseField->getUserField());
				}
				else
				{
					$displayField = Display\Field::createFromBaseField($baseField->getName(), $baseField->toArray());
				}

				$displayField->setContext($context);

				$displayFields[$baseField->getName()] = $displayField;
			}
		}

		return $displayFields;
	}

	//todo move rendering of all fields to Display (even these fields that don't exist in reality)
	protected function appendOptionalColumns(Item $item, array &$columns): void
	{
		if ($this->factory->isClientEnabled())
		{
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
			if ($this->isColumnVisible('OPPORTUNITY_WITH_CURRENCY'))
			{
				$columns['OPPORTUNITY_WITH_CURRENCY'] = Bitrix\Crm\Format\Money::format(
					(float)$item->getOpportunity(),
					(string)$item->getCurrencyId()
				);
			}
			if ($this->isColumnVisible(Item::FIELD_NAME_OPPORTUNITY))
			{
				$columns[Item::FIELD_NAME_OPPORTUNITY] = number_format(
					(float)$item->getOpportunity(),
					2,
					'.',
					''
				);
			}
			if ($this->isColumnVisible(Item::FIELD_NAME_CURRENCY_ID))
			{
				$columns[Item::FIELD_NAME_CURRENCY_ID] = htmlspecialcharsbx(
					\Bitrix\Crm\Currency::getCurrencyCaption((string)$item->getCurrencyId())
				);
			}
		}

		// if ($this->factory->isCrmTrackingEnabled())
		// {
		// 	if ($this->isColumnVisible(\Bitrix\Crm\Tracking\UI\Grid::COLUMN_TRACKING_PATH))
		// 	{
		// 		\Bitrix\Crm\Tracking\UI\Grid::appendRows($this->factory->getEntityTypeId(), $item->getId(), $columns);
		// 	}
		//
		// 	$utmColumns = UtmTable::getCodeList();
		// 	if (array_intersect($this->getVisibleColumns(), $utmColumns))
		// 	{
		// 		/** @noinspection AdditionOperationOnArraysInspection */
		// 		$columns += $item->getUtm();
		// 	}
		// }

		if ($this->factory->isStagesEnabled())
		{
			$userPermissions = Container::getInstance()->getUserPermissions();
			$isReadOnly = !$userPermissions->canUpdateItem($item);
			if ($this->isColumnVisible(Item::FIELD_NAME_STAGE_ID))
			{
				if ($this->isExportMode())
				{
					$stage = $this->factory->getStage($item->get(Item::FIELD_NAME_STAGE_ID));
					$columns[Item::FIELD_NAME_STAGE_ID] = $stage ? htmlspecialcharsbx($stage->getName()) : null;
				}
				else
				{
					$stageRender = CCrmViewHelper::RenderItemStageControl(
						[
							'ENTITY_ID' => $item->getId(),
							'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
							'CATEGORY_ID' => $item->getCategoryId(),
							'CURRENT_ID' => $item->getStageId(),
							'SERVICE_URL' => 'crm.controller.item.update',
							'READ_ONLY' => $isReadOnly,
						]
					);

					$columns[Item::FIELD_NAME_STAGE_ID] = $stageRender;
				}
			}
			if ($this->isColumnVisible(Item::FIELD_NAME_PREVIOUS_STAGE_ID))
			{
				$stage = $this->factory->getStage($item->get(Item::FIELD_NAME_PREVIOUS_STAGE_ID));
				$columns[Item::FIELD_NAME_PREVIOUS_STAGE_ID] = $stage ? htmlspecialcharsbx($stage->getName()) : null;
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

		if ($this->isColumnVisible(Item::FIELD_NAME_TITLE) && !$this->isExportMode())
		{
			$detailUrl = htmlspecialcharsbx(Container::getInstance()->getRouter()->getItemDetailUrl($this->entityTypeId, $item->getId()));
			$columns[Item::FIELD_NAME_TITLE] = '<a href="'.$detailUrl.'">'.htmlspecialcharsbx($item->getHeading()).'</a>';
		}
	}

	protected function appendParentColumns(Item $item, array &$columns): void
	{
		$isExport = $this->isExportMode();

		if (isset($this->parents[$item->getId()]))
		{
			foreach ($this->parents[$item->getId()] as $parentEntityTypeId => $parent)
			{
				$columns[$parent['code']] = $isExport
					? $parent['title']
					: $parent['value'];
			}
		}
	}

	protected function getProductsItemColumn(array $products): string
	{
		$productNames = [];
		foreach ($products as $product)
		{
			$productNames[] = htmlspecialcharsbx($product->getProductName());
		}

		return implode(', ', $productNames);
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

	protected function getToolbarSettingsItems(): array
	{
		$settingsItems = parent::getToolbarSettingsItems();

		$permissions = Container::getInstance()->getUserPermissions();

		if ($permissions->checkExportPermissions($this->entityTypeId, 0, $this->getCategoryId()))
		{
			$settingsItems[] = ['delimiter' => true];
			$settingsItems[] = [
				'text' => Loc::getMessage('CRM_TYPE_ITEM_EXPORT_CSV'),
				'href' => '',
				'onclick' => new Buttons\JsCode("BX.Crm.Router.Instance.closeSettingsMenu();BX.Event.EventEmitter.emit('BX.Crm.ItemListComponent:onStartExportCsv');"),
			];
			$settingsItems[] = [
				'text' => Loc::getMessage('CRM_TYPE_ITEM_EXPORT_EXCEL'),
				'href' => '',
				'onclick' => new Buttons\JsCode("BX.Crm.Router.Instance.closeSettingsMenu();BX.Event.EventEmitter.emit('BX.Crm.ItemListComponent:onStartExportExcel');",
				),
			];
		}

		return $settingsItems;
	}
}
