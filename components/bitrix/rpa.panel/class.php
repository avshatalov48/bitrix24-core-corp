<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Filter;
use Bitrix\Rpa\Integration\Bizproc\TaskManager;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\UrlManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('rpa');

class RpaPanelComponent extends Bitrix\Rpa\Components\Base implements Controllerable
{
	protected const GRID_ID = 'rpa-panel';

	protected const DEFAULT_PAGE_SIZE = 11;

	/** @var Filter\Type\Provider */
	protected $provider;
	/** @var \Bitrix\Main\Filter\Filter */
	protected $filter;
	protected $defaultGridSort = [
		'ID' => 'desc',
	];
	protected $navParamName = 'page';
	protected $users;

	protected function init(): void
	{
		parent::init();

		$settings = new Filter\Type\Settings(['ID' => static::GRID_ID]);
		$this->provider = new Filter\Type\Provider($settings);
		$this->filter = new \Bitrix\Main\Filter\Filter($settings->getID(), $this->provider);
	}

	protected function getGridMode(): string
	{
		return Driver::getInstance()->getUrlManager()->getCurrentTypeListView();
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['taskCountersPullTag'] = Driver::getInstance()->getPullManager()->subscribeOnTaskCounters();
		$this->getApplication()->SetTitle(Loc::getMessage('RPA_TOP_PANEL_PANEL'));

		$this->arResult['messages'] = self::loadBaseLanguageMessages();
		if($this->getGridMode() === UrlManager::TYPES_LIST_VIEW_GRID)
		{
			$this->arResult[UrlManager::TYPES_LIST_VIEW_GRID] = $this->prepareGrid();
		}
		else
		{
			$gridOptions = new Bitrix\Main\Grid\Options(static::GRID_ID);
			$navParams = $gridOptions->getNavParams(['nPageSize' => static::DEFAULT_PAGE_SIZE]);
			$pageSize = (int)$navParams['nPageSize'];
			$pageNavigation = $this->getPageNavigation($pageSize);

			$this->arResult['panelParams'] = $this->preparePanelAction($pageNavigation);
			$this->arResult['pageNavigation'] = $pageNavigation;
			$this->arResult['baseUrl'] = Driver::getInstance()->getUrlManager()->getPanelUrl();
		}

		$this->includeComponentTemplate();
	}

	public function preparePanelAction(PageNavigation $pageNavigation): ?array
	{
		$this->init();

		$panel = null;

		if(!$this->getErrors())
		{
			$listFilter = $this->getListFilter();
			$types = TypeTable::getList([
				'offset' => $pageNavigation->getOffset(),
				'limit' => $pageNavigation->getLimit(),
				'filter' => $listFilter,
			])->fetchCollection();

			$taskManager = Driver::getInstance()->getTaskManager();
			if($taskManager)
			{
				$tasks = $taskManager->getUserIncompleteTasksByType($types->getIdList());
			}
			$bitrix24Manager = Driver::getInstance()->getBitrix24Manager();
			$panel = [
				'id' => static::GRID_ID,
				'isCreateTypeRestricted' => $bitrix24Manager->isCreateTypeRestricted(),
				'items' => [
					[
						'id' => 'rpa-type-new',
					]
				],
			];
			foreach($types as $type)
			{
				$panel['items'][] = $this->getTypeDataForPanelItem($type, $tasks);
			}
			$pageNavigation->setRecordCount(TypeTable::getCount($listFilter));
		}

		return $panel;
	}

	protected function prepareGrid(): array
	{
		$grid = [];
		$gridId = static::GRID_ID;
		$grid['GRID_ID'] = $gridId;
		$grid['COLUMNS'] = $this->provider->getGridColumns();
		$grid['MODE'] = $this->getGridMode();
		$grid['TILE_GRID_MODE'] = $this->getGridMode() === UrlManager::TYPES_LIST_VIEW_TILES;
		if($grid['TILE_GRID_MODE'])
		{
			$grid['TILE_SIZE'] = 'xl';
			$grid['TILE_GRID_ITEMS'] = $this->arResult['panelParams']['items'];
			$grid['JS_CLASS_TILE_GRID_ITEM'] = 'BX.Rpa.PanelItem';
		}

		$gridOptions = new Bitrix\Main\Grid\Options($gridId);
		$navParams = $gridOptions->getNavParams(['nPageSize' => static::DEFAULT_PAGE_SIZE]);
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);
		$pageNavigation = $this->getPageNavigation($pageSize);
		$userIds = [];
		$this->arResult[UrlManager::TYPES_LIST_VIEW_GRID]['ROWS'] = [];
		$listFilter = $this->getListFilter();
		$list = TypeTable::getList([
			'order' => $gridSort['sort'],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'filter' => $listFilter,
		])->fetchAll();
		$totalCount = TypeTable::getCount($listFilter);
		if(count($list) > 0)
		{
			foreach($list as $item)
			{
				$userIds[$item['CREATED_BY']] = $item['CREATED_BY'];
			}
			$this->users = static::getUsers($userIds);
			foreach($list as $item)
			{
				$grid['ROWS'][] = [
					'id' => $item['ID'],
					'data' => $item,
					'columns' => $this->getItemColumn($item),
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
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ACTION_PANEL'] = false;
		$grid['SHOW_PAGINATION'] = true;
		$grid['ALLOW_CONTEXT_MENU'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_ROW_ACTIONS_MENU'] = false;

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

		return $filter;
	}

	protected function getToolbarParameters(): array
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		$currentView = $urlManager->getCurrentTypeListView();
		$buttons = [];
		if(
			($currentView === UrlManager::TYPES_LIST_VIEW_GRID)
			&& Driver::getInstance()->getUserPermissions()->canCreateType()
		)
		{
			$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button([
				'color' => Buttons\Color::PRIMARY,
				'text' => Loc::getMessage('RPA_COMMON_ADD'),
				'icon' => Buttons\Icon::ADD,
				'link' => $urlManager->getTypeDetailUrl(0),
				'click' => $this->getAddTypeButtonJsCode(),
			]);
		}
		$urlManager->setUserTypeListView($currentView);
		$tasks = 0;
		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager)
		{
			$tasks = $taskManager->getUserTotalIncompleteCounter();
		}
		return [
			'typeId' => 'all',
			'filter' => $this->prepareFilter(),
			'views' => [
				UrlManager::TYPES_LIST_VIEW_TILES => [
					'title' => Loc::getMessage('RPA_COMMON_TILES'),
					'url' => $urlManager->getPanelUrl(),
					'isActive' => ($this->getGridMode() === UrlManager::TYPES_LIST_VIEW_TILES),
				],
				'list' => [
					'title' => Loc::getMessage('RPA_COMMON_LIST'),
					'url' => $urlManager->getPanelUrl().'?view=grid',
					'isActive' => ($this->getGridMode() === UrlManager::TYPES_LIST_VIEW_GRID),
				],
			],
			'tasks' => $tasks,
			'tasksUrl' => $urlManager->getUserTypesUrlWithTasks(),
			'tasksFilter' => [
				'filterId' => $this->filter->getID(),
				'fields' => [
					TaskManager::TASKS_FILTER_FIELD => TaskManager::TASKS_FILTER_HAS_TASKS_VALUE,
				],
			],
			'buttons' => $buttons,
		];
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

		$typeId = (int) $item['ID'];
		$tasks = 0;
		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager)
		{
			$tasks = count($taskManager->getUserIncompleteTasksForType($typeId));
		}
		$urlManager = Driver::getInstance()->getUrlManager();
		$detailUrl = htmlspecialcharsbx($urlManager->getUserItemsUrl($typeId));
		$tasksUrl = htmlspecialcharsbx($urlManager->getTasksUrl());
		$counter = '';
		if($tasks > 0)
		{
			$counter = $tasks;
		}
		$titleColumnData = '<a href="'.$detailUrl.'">'.htmlspecialcharsbx($item['TITLE']).'</a>';
		$titleColumnData .= '<span class="rpa-title-indicators" id="rpa-type-list-'.(int) $item['ID'].'-counter-container"';
		if($counter <= 0)
		{
			$titleColumnData .= ' style="display: none;"';
		}
		$titleColumnData .= '><a href="'.$tasksUrl.'" class="rpa-item-waiting-tasks">';
		$titleColumnData .= '<span class="rpa-counter" id="rpa-type-list-'.(int) $item['ID'].'-counter"';
		$titleColumnData .= '>'.$counter.'</span>';
		$titleColumnData .= '</a>';
		$titleColumnData .= '</span>';

		$item['TITLE'] = $titleColumnData;

		return $item;
	}

	public function configureActions(): array
	{
		return [];
	}

	protected function getAddTypeButtonJsCode(): Buttons\JsCode
	{
		if (Driver::getInstance()->getBitrix24Manager()->isCreateTypeRestricted())
		{
			return new Buttons\JsCode('BX.Rpa.Manager.Instance.showFeatureSlider();');
		}

		return new Buttons\JsCode('
			BX.Rpa.Manager.Instance.openTypeDetail(0).then(function(slider){
				var sliderData = slider.getData();
				var response = sliderData.get(\'response\');
				if(response && response.status === \'success\')
				{
					BX.Rpa.Manager.Instance.openKanban(response.data.type.id);
				}
				else
				{
					var data = sliderData.get(\'type\');
					if(BX.Type.isPlainObject(data) && data.typeId && data.typeId > 0)
					{
						BX.ajax.runAction(\'rpa.type.delete\', {
							data: {
								id: data.typeId,
							}
						});
					}
				}
			});
		');
	}
}