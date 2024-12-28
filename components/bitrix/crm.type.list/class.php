<?php

use Bitrix\Crm\Filter;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('crm');

class CrmTypeListComponent extends Bitrix\Crm\Component\Base
{
	protected const GRID_ID = 'crm-type-list';
	protected const EXTERNAL_GRID_ID = 'crm-type-list-external';
	protected const DEFAULT_PAGE_SIZE = 10;

	protected const INTERNAL_DYNAMIC_TYPES_TITLE = 'CRM_TYPE_LIST_TITLE_MSGVER_1';
	protected const EXTERNAL_DYNAMIC_TYPES_TITLE = 'CRM_TYPE_LIST_EXTERNAL_DYNAMIC_TYPES_TITLE';

	/** @var Filter\TypeDataProvider */
	protected $provider;
	/** @var Filter\Filter */
	protected $filter;
	protected $defaultGridSort = [
		'ID' => 'desc',
	];
	protected $navParamName = 'page';
	protected $users;
	protected $isExternalDynamicTypes;
	protected $gridId;
	protected ?int $filteredByAutomatedSolutionId = null;
	protected bool $showAllFromAutomatedSolutions = false;

	protected function init(): void
	{
		parent::init();

		$consistentUrl = Container::getInstance()->getRouter()->getConsistentUrlFromPartlyDefined(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri());
		if ($consistentUrl)
		{
			LocalRedirect($consistentUrl->getUri());
			return;
		}

		$isExternalDynamicTypes =
			str_starts_with(Container::getInstance()->getRouter()->getRoot(), '/automation')
			|| isset($this->arParams['isExternal']) && $this->arParams['isExternal'] === true
		;
		$this->gridId = $isExternalDynamicTypes ? static::EXTERNAL_GRID_ID : static::GRID_ID;

		/** @var Filter\TypeSettings $settings */
		$settings = new Filter\TypeSettings([
			'ID' => $this->gridId,
			'IS_EXTERNAL_DYNAMICAL_TYPES' => $isExternalDynamicTypes,
		]);

		$this->provider = new Filter\TypeDataProvider($settings);
		$this->filter = new Filter\Filter($settings->getID(), $this->provider);

		$this->isExternalDynamicTypes = $this->provider->getSettings()->getIsExternalDynamicalTypes();
		[$this->filteredByAutomatedSolutionId, $this->showAllFromAutomatedSolutions] = $this->getAutomatedSolutionIdFromFilter();

		if (!$this->checkPermissions())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'));
		}
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$title = $this->isExternalDynamicTypes && !\Bitrix\Crm\Settings\Crm::isAutomatedSolutionListEnabled()
			? Loc::getMessage(static::EXTERNAL_DYNAMIC_TYPES_TITLE)
			: Loc::getMessage(static::INTERNAL_DYNAMIC_TYPES_TITLE)
		;
		$this->getApplication()->SetTitle($title);

		$this->arResult['isExternal'] = $this->isExternalDynamicTypes;
		$this->arResult['grid'] = $this->prepareGrid();
		$this->arResult['isEmptyList'] = !$this->isAtLeastOneDynamicTypeExists();
		$this->arResult['filter'] = $this->prepareFilter();
		$this->arResult['welcome'] = $this->getPreparedEmptyState();
		$this->includeComponentTemplate();
	}

	private function isAtLeastOneDynamicTypeExists(): bool
	{
		$query = \Bitrix\Crm\Model\Dynamic\TypeTable::query();

		$query
			->setSelect(['CUSTOM_SECTION_ID'])
			->setLimit(1)
			->setCacheTtl(60 * 10)
		;

		if ($this->isExternalDynamicTypes)
		{
			$query->whereNotNull('CUSTOM_SECTION_ID');
		}
		else
		{
			$query->whereNull('CUSTOM_SECTION_ID');
		}

		return (bool)$query->fetch();
	}

	protected function getPreparedEmptyState(): array
	{
		if ($this->isExternalDynamicTypes)
		{
			return [
				'title' => Loc::getMessage('CRM_TYPE_LIST_CLASS_EXTERNAL_WELCOME_TITLE'),
				'text' => Loc::getMessage('CRM_TYPE_LIST_CLASS_EXTERNAL_WELCOME_TEXT'),
				'link' => Loc::getMessage('CRM_TYPE_LIST_CLASS_EXTERNAL_WELCOME_LINK'),
				'helpdeskCode' => 18913896,
			];
		}

		return [
			'title' => Loc::getMessage('CRM_TYPE_LIST_CLASS_WELCOME_TITLE'),
			'text' =>  Loc::getMessage('CRM_TYPE_LIST_CLASS_WELCOME_TEXT'),
			'link' => Loc::getMessage('CRM_TYPE_LIST_CLASS_WELCOME_LINK'),
			'helpdeskCode' => 13315798,
		];
	}

	protected function prepareGrid(): array
	{
		$grid = [];
		$grid['GRID_ID'] = $this->gridId;
		$grid['COLUMNS'] = $this->provider->getGridColumns();
		$grid['ROWS'] = [];

		$gridOptions = new Bitrix\Main\Grid\Options($this->gridId);
		$navParams = $gridOptions->getNavParams(['nPageSize' => static::DEFAULT_PAGE_SIZE]);
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);
		$pageNavigation = $this->getPageNavigation($pageSize);
		$userIds = [];
		$listFilter = $this->getListFilter();

		$list = Container::getInstance()->getDynamicTypeDataClass()::getList([
			'order' => $gridSort['sort'],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'filter' => $listFilter,
		])->fetchAll();

		$totalCount = Container::getInstance()->getDynamicTypeDataClass()::getCount($listFilter);

		if(count($list) > 0)
		{
			$format = CCrmDateTimeHelper::getDefaultDateTimeFormat();
			$currentTime = time() + CTimeZone::GetOffset();

			foreach($list as $item)
			{
				$userIds[$item['CREATED_BY']] = $item['CREATED_BY'];
				$userIds[$item['UPDATED_BY']] = $item['UPDATED_BY'];
			}

			$this->users = Container::getInstance()->getUserBroker()->getBunchByIds($userIds);
			foreach($list as $item)
			{
				if (!empty($item['CUSTOM_SECTION_ID']))
				{
					$solution = Container::getInstance()->getAutomatedSolutionManager()->getAutomatedSolution((int)$item['CUSTOM_SECTION_ID']);
					$item['AUTOMATED_SOLUTION'] = $solution;
				}

				$eventData = \CUtil::PhpToJSObject([
					'entityTypeId' => $item['ENTITY_TYPE_ID'],
					'id' => $item['ID'],
				]);
				$isTypeSettingsRestricted = RestrictionManager::getDynamicTypesLimitRestriction()->isTypeSettingsRestricted($item['ENTITY_TYPE_ID']);
				$editAction = [
					'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
					'HREF' => Container::getInstance()->getRouter()->getTypeDetailUrl($item['ENTITY_TYPE_ID'])->addParams([
						'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU,
					]),
				];
				$fieldsAction = null;
				if (!$isTypeSettingsRestricted)
				{
					$fieldsAction = [
						'TEXT' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
						'HREF' => Container::getInstance()->getRouter()->getUserFieldListUrl($item['ENTITY_TYPE_ID']),
					];
				}

				$item['LAST_ACTIVITY_TIME'] = FormatDate($format, CCrmDateTimeHelper::getUserTime($this->getLastActivityTime($item)), $currentTime);
				$item['CREATED_TIME'] = FormatDate($format, CCrmDateTimeHelper::getUserTime($item['CREATED_TIME']), $currentTime);
				$item['UPDATED_TIME'] = FormatDate($format, CCrmDateTimeHelper::getUserTime($item['UPDATED_TIME']), $currentTime);

				$grid['ROWS'][] = [
					'id' => $item['ID'],
					'data' => $item,
					'columns' => $this->getItemColumn($item),
					'actions' => [
						$editAction,
						$fieldsAction,
						[
							'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_DELETE'),
							'ONCLICK' => "BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onClickDelete', ".$eventData.")",
						]
					]
				];
			}
		}
		$pageNavigation->setRecordCount($totalCount);
		$grid['NAV_PARAM_NAME'] = $this->navParamName;
		$grid['CURRENT_PAGE'] = $pageNavigation->getCurrentPage();
		$grid['NAV_OBJECT'] = $pageNavigation;
		$grid['TOTAL_ROWS_COUNT'] = $totalCount;
		$grid['AJAX_MODE'] = 'Y';
		$grid['ALLOW_ROWS_SORT'] = false;
		$grid['AJAX_OPTION_JUMP'] = "N";
		$grid['AJAX_OPTION_STYLE'] = "N";
		$grid['AJAX_OPTION_HISTORY'] = "N";
		$grid['SHOW_PAGESIZE'] = true;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['SHOW_ACTION_PANEL'] = false;
		$grid['SHOW_PAGINATION'] = true;
		$grid['ALLOW_CONTEXT_MENU'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_ROW_ACTIONS_MENU'] = true;
		$grid['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] = true;

		return $grid;
	}

	/**
	 * @param array $item
	 * @return Type\DateTime
	 */
	protected function getLastActivityTime(array $item) : Type\DateTime
	{
		$typeId = (int)$item['ID'];
		$createdTime = $item['CREATED_TIME'];

		return Container::getInstance()->getSummaryFactory()->getDynamicTypeSummary($typeId)?->getLastActivityTime() ?? $createdTime;
	}

	protected function getPageNavigation(int $pageSize): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(true)->setPageSize($pageSize)->initFromUri();

		return $pageNavigation;
	}

	protected function getListFilter(): array
	{
		$filterOptions = new Options($this->gridId);
		$requestFilter = $filterOptions->getFilter($this->filter->getFieldArrays());

		$filter = [];
		$this->provider->prepareListFilter($filter, $requestFilter);
		$filter['!@ENTITY_TYPE_ID'] = \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds();

		return $filter;
	}

	protected function prepareFilter(): array
	{
		return [
			'FILTER_ID' => $this->gridId,
			'GRID_ID' => $this->gridId,
			'FILTER' => $this->filter->getFieldArrays(),
			'DISABLE_SEARCH' => false,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
			'ENABLE_LIVE_SEARCH' => true,
			'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
		];
	}

	protected function getItemColumn(array $item): array
	{
		$createdBy = $item['CREATED_BY'];
		$updatedBy = $item['UPDATED_BY'];

		$item['CREATED_BY'] = isset($this->users[$createdBy])
			? $this->prepareUserDataForGrid($this->users[$createdBy])
			: ''
		;

		$item['UPDATED_BY'] =
			isset($this->users[$updatedBy])
			?  $this->prepareUserDataForGrid($this->users[$updatedBy])
			: ''
		;

		$detailUrl = htmlspecialcharsbx(Container::getInstance()->getRouter()->getItemListUrlInCurrentView($item['ENTITY_TYPE_ID']));
		$item['TITLE'] = '<a href="'.$detailUrl.'">'.htmlspecialcharsbx($item['TITLE']).'</a>';

		if (!empty($item['AUTOMATED_SOLUTION']))
		{
			$item = $this->getAutomatedSolutionItemCell($item);
		}

		return $item;
	}

	private function getAutomatedSolutionItemCell(array $item): array
	{
		$id = (int)$item['AUTOMATED_SOLUTION']['ID'];
		$title = htmlspecialcharsbx($item['AUTOMATED_SOLUTION']['TITLE']);

		$filterOptions = new Options($this->gridId);
		$requestFilter = $filterOptions->getFilter($this->filter->getFieldArrays());

		$activeClass = 'crm-type-grid-custom-section-'
			. (isset($requestFilter['AUTOMATED_SOLUTION']) ? 'active' : 'inactive');

		$item['AUTOMATED_SOLUTION'] = <<<HTML
<div class='crm-type-grid-custom-section-wrapper $activeClass'>
	<span 
		class="crm-type-grid-custom-section-title"
		onclick="BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onFilterByAutomatedSolution', '$id')"
	>$title</span>
	<div 
		class="crm-type-grid-filter-remove"
		onclick="BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onResetFilterByAutomatedSolution')"
	></div>
</div>
HTML;
		return $item;
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];
		if(Container::getInstance()->getUserPermissions()->canAddType($this->filteredByAutomatedSolutionId))
		{
			$eventData = [];
			if ($this->isExternalDynamicTypes)
			{
				$eventData = [
					'queryParams' => [
						'isExternal' => 'Y',
					],
				];
			}

			$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button([
				'color' => Buttons\Color::SUCCESS,
				'text' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
				'onclick' => new Buttons\JsCode(
					"BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onClickCreate', " . \CUtil::PhpToJSObject($eventData) . ')',
				),
			]);
		}

		return array_merge(parent::getToolbarParameters(), [
			'buttons' => $buttons,
			'isWithFavoriteStar' => true,
			'hideBorder' => true,
		]);
	}

	protected function getTopPanelId(int $entityTypeId): string
	{
		return 'DYNAMIC_LIST';
	}

	private function checkPermissions(): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions();

		if ($this->filteredByAutomatedSolutionId === 0)
		{
			return $userPermissions->isCrmAdmin();
		}
		if ($this->filteredByAutomatedSolutionId > 0)
		{
			return $userPermissions->isAutomatedSolutionAdmin($this->filteredByAutomatedSolutionId);
		}
		if ($this->showAllFromAutomatedSolutions)
		{
			return $userPermissions->canEditAutomatedSolutions();
		}

		return $userPermissions->canWriteConfig() && $userPermissions->canEditAutomatedSolutions(); // without filter must be both crm admin and automated solution admin
	}

	private function getAutomatedSolutionIdFromFilter(): array
	{
		$listFilter = $this->getListFilter();

		if (array_key_exists('=CUSTOM_SECTION_ID', $listFilter) && $listFilter['=CUSTOM_SECTION_ID'] === null)
		{
			return [0, false];
		}
		if (array_key_exists('!=CUSTOM_SECTION_ID', $listFilter) && $listFilter['!=CUSTOM_SECTION_ID'] === null)
		{
			return [null, true];
		}

		if (isset($listFilter['=CUSTOM_SECTION_ID']) && (int)$listFilter['=CUSTOM_SECTION_ID'] > 0)
		{
			return [(int)$listFilter['=CUSTOM_SECTION_ID'], false];
		}

		return [null, false];
	}
}
