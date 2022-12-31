<?php

use Bitrix\Crm\Filter;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('crm');

class CrmTypeListComponent extends Bitrix\Crm\Component\Base
{
	protected const GRID_ID = 'crm-type-list';
	protected const DEFAULT_PAGE_SIZE = 10;

	/** @var Filter\TypeDataProvider */
	protected $provider;
	/** @var Filter\Filter */
	protected $filter;
	protected $defaultGridSort = [
		'ID' => 'desc',
	];
	protected $navParamName = 'page';
	protected $users;

	protected function init(): void
	{
		parent::init();

		$consistentUrl = Container::getInstance()->getRouter()->getConsistentUrlFromPartlyDefined(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri());
		if ($consistentUrl)
		{
			LocalRedirect($consistentUrl->getUri());
			return;
		}

		if (!Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'));
			return;
		}

		/** @var Filter\TypeSettings $settings */
		$settings = new Filter\TypeSettings(['ID' => static::GRID_ID]);
		$this->provider = new Filter\TypeDataProvider($settings);
		$this->filter = new Filter\Filter($settings->getID(), $this->provider);
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->getApplication()->SetTitle(Loc::getMessage('CRM_TYPE_LIST_TITLE'));

		$this->arResult['grid'] = $this->prepareGrid();
		$this->arResult['isEmptyList'] = empty($this->arResult['grid']['ROWS']);

		$this->includeComponentTemplate();
	}

	protected function prepareGrid(): array
	{
		$grid = [];
		$gridId = static::GRID_ID;
		$grid['GRID_ID'] = $gridId;
		$grid['COLUMNS'] = $this->provider->getGridColumns();
		$grid['ROWS'] = [];

		$gridOptions = new Bitrix\Main\Grid\Options($gridId);
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
			foreach($list as $item)
			{
				$userIds[$item['CREATED_BY']] = $item['CREATED_BY'];
			}
			$this->users = Container::getInstance()->getUserBroker()->getBunchByIds($userIds);
			foreach($list as $item)
			{
				$eventData = \CUtil::PhpToJSObject([
					'entityTypeId' => $item['ENTITY_TYPE_ID'],
					'id' => $item['ID'],
				]);
				$isTypeSettingsRestricted = RestrictionManager::getDynamicTypesLimitRestriction()->isTypeSettingsRestricted($item['ENTITY_TYPE_ID']);
				$editAction = [
					'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
					'HREF' => Container::getInstance()->getRouter()->getTypeDetailUrl($item['ENTITY_TYPE_ID']),
				];
				$fieldsAction = null;
				if (!$isTypeSettingsRestricted)
				{
					$fieldsAction = [
						'TEXT' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
						'HREF' => Container::getInstance()->getRouter()->getUserFieldListUrl($item['ENTITY_TYPE_ID']),
					];
				}
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

		return $grid;
	}

	protected function getPageNavigation(int $pageSize): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(true)->setPageSize($pageSize)->initFromUri();

		return $pageNavigation;
	}

	protected function getListFilter(): array
	{
		$filterOptions = new Options(static::GRID_ID);
		$requestFilter = $filterOptions->getFilter($this->filter->getFieldArrays());

		$filter = [];
		$this->provider->prepareListFilter($filter, $requestFilter);
		$filter['!@ENTITY_TYPE_ID'] = \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds();

		return $filter;
	}

	protected function prepareFilter(): array
	{
		return [
			'FILTER_ID' => static::GRID_ID,
			'GRID_ID' => static::GRID_ID,
			'FILTER' => $this->filter->getFieldArrays(),
			'DISABLE_SEARCH' => false,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
			'ENABLE_LIVE_SEARCH' => true,
		];
	}

	protected function getItemColumn(array $item): array
	{
		$createdBy = $item['CREATED_BY'];
		if(isset($this->users[$createdBy]))
		{
			$item['CREATED_BY'] = $this->prepareUserDataForGrid($this->users[$createdBy]);
		}
		else
		{
			$item['CREATED_BY'] = '';
		}

		$detailUrl = htmlspecialcharsbx(Container::getInstance()->getRouter()->getItemListUrlInCurrentView($item['ENTITY_TYPE_ID']));
		$item['TITLE'] = '<a href="'.$detailUrl.'">'.htmlspecialcharsbx($item['TITLE']).'</a>';

		return $item;
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];
		if(Container::getInstance()->getUserPermissions()->canAddType())
		{
			$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button([
				'color' => Buttons\Color::PRIMARY,
				'text' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
				'link' => Container::getInstance()->getRouter()->getTypeDetailUrl(0)->getUri(),
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
}
