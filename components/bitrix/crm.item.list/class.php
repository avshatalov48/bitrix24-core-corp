<?php

use Bitrix\Crm\Component\EntityList\FieldRestrictionManager;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManagerTypes;
use Bitrix\Crm\Component\EntityList\NearestActivity;
use Bitrix\Crm\Component\EntityList\NearestActivity\ManagerFactory;
use Bitrix\Crm\Filter\FieldsTransform;
use Bitrix\Crm\Filter\UiFilterOptions;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\Analytics\Builder\Entity\CopyOpenEvent;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Restriction\ItemsMutator;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Settings\HistorySettings;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Crm\WebForm\Internals\PageNavigation;
use Bitrix\Main\Grid;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::includeModule('crm');

class CrmItemListComponent extends Bitrix\Crm\Component\ItemList implements \Bitrix\Main\Engine\Contract\Controllerable
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

	private ?array $gridColumns = null;
	private ?Grid\Panel\Panel $panel = null;
	private ?NearestActivity\Manager $nearestActivityManager = null;
	private bool $enableNextPage = false;

	public function configureActions()
	{
		return [];
	}

	public function getTotalCountAction(int $entityTypeId, array $listFilter = [])
	{
		$userPermission = Container::getInstance()->getUserPermissions();
		if (!$userPermission->canReadType($entityTypeId))
		{
			return;
		}
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		$totalCountRow = $factory->getItemsCountFilteredByPermissions($listFilter);

		return new \Bitrix\Main\Engine\Response\Json(['DATA' => ['TEXT' => Loc::getMessage('CRM_LIST_ALL_COUNT', ['#COUNT#' => $totalCountRow])]]);
	}

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
			[FieldRestrictionManagerTypes::OBSERVERS, FieldRestrictionManagerTypes::ACTIVITY],
			$this->entityTypeId
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

		$this->processRequest();

		$this->getApplication()->SetTitle(htmlspecialcharsbx($this->getTitle()));

		$listFilter = $this->getListFilter();

		$this->fieldRestrictionManager->removeRestrictedFields($this->filterOptions, $this->gridOptions);

		$pageNavigation = $this->getPageNavigation();
		$entityTypeName = \CCrmOwnerType::ResolveName($this->entityTypeId);
		$categoryId = $this->category ? $this->category->getId() : 0;
		$this->arResult['grid'] = $this->prepareGrid(
			$listFilter,
			$pageNavigation,
			$this->gridOptions->getSorting(['sort' => $this->defaultGridSort])
		);
		$this->arResult['interfaceToolbar'] = $this->prepareInterfaceToolbar();
		$this->arResult['jsParams'] = [
			'entityTypeId' => $this->entityTypeId,
			'entityTypeName' => $entityTypeName,
			'categoryId' => $categoryId,
			'gridId' => $this->getGridId(),
			'backendUrl' => $this->arParams['backendUrl'] ?? null,
			'isIframe' => $this->isIframe(),
			'isEmbedded' => $this->isEmbedded(),
			'settingsButtonExtenderParams' =>
				\Bitrix\Crm\UI\SettingsButtonExtender\SettingsButtonExtenderParams::createDefaultForGrid($this->entityTypeId, $this->getGridId())
					->setCategoryId($this->getCategoryId())
					->setIsAllItemsCategory($this->category === null)
					->buildParams()
		];
		$this->arResult['entityTypeName'] = $entityTypeName;
		$this->arResult['categoryId'] = $this->category ? $this->category->getId() : 0;
		$this->arResult['entityTypeDescription'] = $this->factory->getEntityDescription();

		$params = [
			$this->getGridId() ?? '',
			[],
			$this->filter
		];
		$this->arResult['restrictedFieldsEngine'] = $this->fieldRestrictionManager->fetchRestrictedFieldsEngine(...$params);
		$this->arResult['restrictedFields'] = $this->fieldRestrictionManager->getFilterFields(...$params);

		$url = $this->arParams['backendUrl'] ?? null;
		if ($url)
		{
			$url->deleteParams(['sessid']);
		}
		$this->arResult['pagination'] = [
			'PAGE_NUM' => $pageNavigation->getCurrentPage(),
			'ENABLE_NEXT_PAGE' => $this->enableNextPage,
			'URL' => $url,
		];

		$this->arResult['extension'] = [
			'ID' => $this->getGridId() . '_MANAGER',
			'CONFIG' => [
				'gridId' => $this->getGridId(),
				'serviceUrl' => \Bitrix\Main\Engine\UrlManager::getInstance()->createByBitrixComponent(
					$this,
					'getTotalCount',
					[
						'entityTypeId' => $this->factory->getEntityTypeId(),
						'listFilter' => $listFilter,
						'sessid' => bitrix_sessid(),
					]
				),
			],
		];

		$this->includeComponentTemplate();
	}

	protected function processRequest(): void
	{
		\CCrmViewHelper::processGridRequest($this->entityTypeId, $this->getGridId(), $this->getPanel(), $this->request);
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

	protected function getPageNavigationId(): string
	{
		return "{$this->getGridId()}_{$this->navParamName}";
	}

	protected function getNotAccessibleFieldNames(): array
	{
		if ($this->notAccessibleFields === null)
		{
			$this->notAccessibleFields = array_flip(VisibilityManager::getNotAccessibleFields($this->entityTypeId));
		}

		return $this->notAccessibleFields;
	}

	private function getGridColumns(): array
	{
		$this->gridColumns ??= array_merge($this->provider->getGridColumns(), $this->ufProvider->getGridColumns());

		$this->gridColumns = array_values($this->gridColumns);

		return $this->gridColumns;
	}

	protected function prepareGrid(array $listFilter, PageNavigation $pageNavigation, array $gridSort): array
	{
		$grid = [];
		$grid['GRID_ID'] = $this->getGridId();
		$grid['COLUMNS'] = $this->getGridColumns();
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
					'limit' => $pageNavigation->getLimit() + 1,
					'filter' => $listFilter,
				],
				$this->userPermissions->getUserId(),
				$this->isExportMode()
					? \Bitrix\Crm\Service\UserPermissions::OPERATION_EXPORT
					: \Bitrix\Crm\Service\UserPermissions::OPERATION_READ
			);
			if (count($list) === $pageNavigation->getLimit() + 1)
			{
				$this->enableNextPage = true;
				unset($list[$pageNavigation->getLimit()]);
			}
			$rows = $this->prepareGridRows($list);
		}
		$getTotalCountActionUrl = \Bitrix\Main\Engine\UrlManager::getInstance()->createByBitrixComponent(
			$this,
			'getTotalCount',
			[
				'entityTypeId' => $this->factory->getEntityTypeId(),
				'listFilter' => $listFilter,
				'sessid' => bitrix_sessid(),
			]
		);
		$rowCountHtml = str_replace(
			['%prefix%', '%all%', '%getTotalCountActionUrl%', '%show%'],
			[
				htmlspecialcharsbx(mb_strtolower($this->getGridId())),
				Loc::getMessage('CRM_LIST_ALL'),
				$getTotalCountActionUrl,
				Loc::getMessage('CRM_LIST_SHOW_ROW_COUNT'),
			],
			'<div id="%prefix%_row_count_wrapper">%all%: <a id="%prefix%_row_count" onclick=\'BX.CrmUIGridExtension.getCountRow("%prefix%", "%getTotalCountActionUrl%")\' style="cursor: pointer">%show%</a></div>'
		);

		$grid['ROWS'] = $rows;
		$grid['TOTAL_ROWS_COUNT_HTML'] = $rowCountHtml;
		$grid['AJAX_MODE'] = ($this->arParams['ajaxMode'] ?? 'Y');
		$grid['ALLOW_ROWS_SORT'] = false;
		$grid['AJAX_OPTION_JUMP'] = 'N';
		$grid['AJAX_OPTION_STYLE'] = 'N';
		$grid['AJAX_OPTION_HISTORY'] = 'N';
		$grid['SHOW_PAGESIZE'] = true;
		$grid['DEFAULT_PAGE_SIZE'] = static::DEFAULT_PAGE_SIZE;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['SHOW_PAGINATION'] = true;
		$grid['ALLOW_CONTEXT_MENU'] = false;
		$grid['SHOW_ROW_ACTIONS_MENU'] = true;
		$grid['ENABLE_FIELDS_SEARCH'] = 'Y';
		$grid['HANDLE_RESPONSE_ERRORS'] = true;
		$grid['HEADERS_SECTIONS'] = $this->getHeaderSections();
		$grid['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] = true;
		$grid['SHOW_ROW_CHECKBOXES'] = true;
		$grid['SHOW_SELECTED_COUNTER'] = true;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = true;

		$actionPanel = $this->getPanel()->getControls();
		$showActionPanel = !empty($actionPanel) && !$this->isEmbedded();

		$grid['SHOW_ACTION_PANEL'] = $showActionPanel;
		if ($showActionPanel)
		{
			$grid['ACTION_PANEL'] = [
				'GROUPS' => [
					[
						'ITEMS' => $actionPanel,
					],
				],
			];
		}

		return $grid;
	}

	private function getPanel(): Grid\Panel\Panel
	{
		if (!$this->panel)
		{
			$settings = new \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings([
				'ID' => $this->getGridId(),
				/**
				 * Could be rewritten in the future to
				 * @see Grid\Export\ExcelExporter::isExportRequest()
				 */
				'MODE' => $this->isExportMode() ? \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings::MODE_EXCEL : \Bitrix\Crm\Component\EntityList\Grid\Settings\ItemSettings::MODE_HTML,
			]);

			$settings->setCategoryId($this->getCategoryId());
			$settings->setIsAllItemsCategory($this->category === null);

			$visibleColumns = $this->getVisibleColumns();
			$editableVisibleColumns = array_filter(
				$this->getGridColumns(),
				fn(array $column) => isset($column['editable']) && in_array($column['id'], $visibleColumns, true)
			);

			$settings->setEditableFieldsWhitelist(array_column($editableVisibleColumns, 'id'));

			$this->panel = new Grid\Panel\Panel(
				new \Bitrix\Crm\Component\EntityList\Grid\Panel\Action\ItemDataProvider(
					$this->factory,
					$this->userPermissions,
					Container::getInstance()->getContext(),
					$settings
				),
			);
		}

		return $this->panel;
	}

	protected function prepareInterfaceToolbar(): array
	{
		$toolbar = [];
		if ($this->parentEntityTypeId > 0 && $this->entityTypeId !== CCrmOwnerType::SmartDocument) // disable direct creation of smart documents from grid
		{
			$entityTypeDescription = $this->factory->getEntityDescription();

			$url = $this->router
				->getItemDetailUrl(
					$this->entityTypeId,
					0,
					null,
					$this->getParentItemIdentifier()
				)
			;

			$toolbar['id'] = $this->getGridId() . '_toolbar';

			if (!empty($this->arParams['ADD_EVENT_NAME']))
			{
				$analyticsBuilder = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault($this->entityTypeId);
				$this->configureAnalyticsEventBuilder($analyticsBuilder);
				$analyticsBuilder->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_CREATE_LINKED_ENTITY_BUTTON);
				$url = $analyticsBuilder->buildUri($url)->getUri();
			}

			$parentParams = [
				'parentTypeId' => $this->getParentItemIdentifier()->getEntityTypeId(),
				'parentId' => $this->getParentItemIdentifier()->getEntityId(),
			];

			if (CCrmOwnerType::SmartInvoice === $this->entityTypeId || CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId))
			{
				if ($this->getParentItemIdentifier()->getEntityTypeId() === CCrmOwnerType::Contact)
				{
					$parentParams['contact_id'] = $this->getParentItemIdentifier()->getEntityId();
				}

				if ($this->getParentItemIdentifier()->getEntityTypeId() === CCrmOwnerType::Company)
				{
					$parentParams['company_id'] = $this->getParentItemIdentifier()->getEntityId();
				}
			}

			$entityIdentity = CCrmOwnerType::ResolveName($this->entityTypeId);
			$addButton = [
				'TEXT' => $entityTypeDescription,
				'TITLE' => Loc::getMessage(
					'CRM_ITEM_LIST_ADD_CHILDREN_ELEMENT',
					[
						'#CHILDREN_ELEMENT#' => $entityTypeDescription,
					]
				),
				'ICON' => 'btn-new',
				'ONCLICK' => "BX.CrmEntityManager.createEntity('" . $entityIdentity . "', { urlParams: " . Json::encode($parentParams) ." })",
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

	protected function getPageNavigation(): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->getPageNavigationId());
		$pageNavigation->allowAllRecords(false)->setPageSize($this->getPageSize())->initFromUri();

		if (isset($this->request['grid_action']) && $this->request['grid_action'] === 'pagination')
		{
			$pageNavigation->setCurrentPage($this->getPageFromRequest($this->request));
		}

		return $pageNavigation;
	}

	private function getPageFromRequest(\Bitrix\Main\Request|\Bitrix\Main\HttpRequest $request)
	{
		if (empty($request->get('page')) || (int)$request->get('page') === 0)
		{
			return 1;
		}
		$pageNum = (int)$request->get('page');

		if ($pageNum < 0)
		{
			//Backward mode
			$pageSize = $this->getPageSize();
			$offset = -($pageNum + 1);
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->entityTypeId);
			$total = $factory->getItemsCountFilteredByPermissions($this->getListFilter());
			$pageNum = (int)(ceil($total / $pageSize)) - $offset;
			if ($pageNum <= 0)
			{
				$pageNum = 1;
			}
		}

		return $pageNum;
	}

	protected function getPageSize(): int
	{
		$navParams = $this->gridOptions->getNavParams([
			'nPageSize' => static::DEFAULT_PAGE_SIZE,
		]);

		return (int)$navParams['nPageSize'];
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

		foreach ($requestFilter as $key => $item)
		{
			if (str_starts_with($key, 'ACTIVITY_FASTSEARCH_'))
			{
				$filter[$key] = $item;
			}
		}

		if($this->category)
		{
			$filter['=CATEGORY_ID'] = $this->category->getId();
		}

		FieldsTransform\UserBasedField::applyTransformWrapper($filter);

		// transform ACTIVITY_COUNTER|ACTIVITY_RESPONSIBLE_IDS filter to real filter params
		CCrmEntityHelper::applySubQueryBasedFiltersWrapper(
			$this->entityTypeId,
			$this->getGridId(),
			\Bitrix\Crm\Counter\EntityCounter::internalizeExtras($_REQUEST),
			$filter,
			null
		);

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
			return ['ERROR' => implode('', $this->getErrorMessages())];
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
			$listFilter['<ID'] = $lastExportedId;
		}

		$pageNavigation = new PageNavigation($this->getPageNavigationId());
		$pageNavigation
			->allowAllRecords(false)
			->setPageSize($this->arParams['STEXPORT_PAGE_SIZE'])
			->setCurrentPage(1)
		;

		$this->setTemplateName($this->exportType);

		$grid = $this->prepareGrid(
			$listFilter,
			$pageNavigation,
			['sort' => ['ID' => 'desc']]
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

		return [
			'PROCESSED_ITEMS' => count($items),
			'LAST_EXPORTED_ID' => $lastExportedId ?? 0,
			'TOTAL_ITEMS' => $totalCount,
		];
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
					$this->getGridColumns(),
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
			$itemsData = $listById = [];
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
				$listById[$item->getId()] = $item;
			}
			$list = $listById;
			unset($listById);

			$displayOptions =
				(new Display\Options())
					->setMultipleFieldsDelimiter($this->isExportMode() ? ', ' : ',<br />')
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

			if (!$this->isExportMode())
			{
				$entityBadges = new Bitrix\Crm\Kanban\EntityBadge($this->entityTypeId, $itemIds);
				$entityBadges->appendToEntityItems($list);
			}

			foreach($list as $item)
			{
				$itemId = $item->getId();
				$itemData = $itemsData[$itemId];
				$itemColumn = $itemColumns[$itemId];

				$this->appendNearestActivityBlockToItem($item, $itemData, $itemColumn);

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

				if ($item->getBadges())
				{
					$itemColumn['TITLE'] .= Bitrix\Crm\Component\EntityList\BadgeBuilder::render($item->getBadges());
				}

				$result[] = [
					'id' => $itemId,
					'data' => $this->prepareItemDataForEdit($itemData),
					'columns' => $itemColumn,
					'actions' => $this->getContextActions($item),
				];
			}
		}

		return $result;
	}

	/**
	 * @param Array<string, mixed> $rawItemData
	 *
	 * @return Array<string, mixed>
	 */
	private function prepareItemDataForEdit(array $rawItemData): array
	{
		$result = [];

		foreach ($rawItemData as $key => $value)
		{
			if ($value instanceof \Bitrix\Main\Type\Date)
			{
				$result[$key] = (string)$value;
			}
			else
			{
				$result[$key] = $value;
			}
		}

		return $result;
	}

	private function appendNearestActivityBlockToItem(Item $item, array &$itemData, array &$itemColumn): void
	{
		if ($this->nearestActivityManager === null)
		{
			$this->nearestActivityManager = ManagerFactory::getInstance()->getManager($this->entityTypeId);
		}

		$itemData['EDIT'] = $this->userPermissions->checkUpdatePermissions(
			$this->entityTypeId,
			$item->getId(),
			$item->getCategoryId(),
		);

		$itemData = $this->nearestActivityManager->appendNearestActivityBlock([$itemData], true)[0];
		$rendered = $itemData['ACTIVITY_BLOCK']->render($this->getGridId());

		$itemColumn['ACTIVITY_BLOCK'] = $rendered;
	}

	protected function getContextActions(Item $item): array
	{
		$jsEventData = CUtil::PhpToJSObject(['entityTypeId' => $this->entityTypeId, 'id' => $item->getId()]);

		$userPermissions = Container::getInstance()->getUserPermissions();

		$itemDetailUrl = $this->router->getItemDetailUrl($this->entityTypeId, $item->getId());
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
		if ($userPermissions->canUpdateItem($item))
		{
			$analyticsEventBuilder = CopyOpenEvent::createDefault($this->entityTypeId);
			$this->configureAnalyticsEventBuilder($analyticsEventBuilder);
			$analyticsEventBuilder->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU);

			$copyUrlParams = [
				'copy' => '1',
			];

			$parentEntityTypeId = (int)($this->arParams['parentEntityTypeId'] ?? 0);
			$parentEntityId = (int)($this->arParams['parentEntityId'] ?? 0);
			if ($parentEntityId > 0 && \CCrmOwnerType::IsDefined($parentEntityTypeId))
			{
				$copyUrlParams['parentTypeId'] = $parentEntityTypeId;
				$copyUrlParams['parentId'] = $parentEntityId;
			}

			$copyUrl = clone $itemDetailUrl;
			$copyUrl->addParams($copyUrlParams);

			$actions[] = [
				'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_COPY'),
				'HREF' => $analyticsEventBuilder->buildUri($copyUrl),
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

					if ($fieldName === Item::FIELD_NAME_OBSERVERS)
					{
						$displayField->setIsMultiple(true);
					}
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
			$detailUrl = htmlspecialcharsbx($this->router->getItemDetailUrl($this->entityTypeId, $item->getId()));
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

	protected function configureAnalyticsEventBuilder(Integration\Analytics\Builder\AbstractBuilder $builder): void
	{
		parent::configureAnalyticsEventBuilder($builder);

		if (!$this->isEmbedded())
		{
			$builder->setSubSection(Integration\Analytics\Dictionary::SUB_SECTION_LIST);
		}
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
