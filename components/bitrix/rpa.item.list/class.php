<?php

use Bitrix\Rpa\Driver;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaItemListComponent extends \Bitrix\Rpa\Components\ItemList
{
	protected $defaultGridSort = [
		'ID' => 'desc',
	];
	protected $navParamName = 'page';

	public function executeComponent()
	{
		$this->init();

		if ($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}
		\Bitrix\Rpa\Driver::getInstance()->getUrlManager()->setUserItemListView($this->type->getId(), \Bitrix\Rpa\UrlManager::ITEMS_LIST_VIEW_LIST);
		$this->getApplication()->setTitle(htmlspecialcharsbx($this->type->getTitle()));
		$this->processGridActions();

		$this->arResult['FILTER'] = $this->prepareFilter();
		$this->arResult['GRID'] = $this->prepareGrid();
		$this->arResult['jsParams'] = [
			'typeId' => $this->type->getId(),
			'kanbanPullTag' => Driver::getInstance()->getPullManager()->subscribeOnKanbanUpdate($this->type->getId()),
			'gridId' => $this->arResult['GRID']['GRID_ID'],
		];

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
			$userPermissions = Driver::getInstance()->getUserPermissions();
			\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($ids);
			if (empty($ids))
			{
				return;
			}
			$items = $this->type->getItems([
				'filter' => [
					'@ID' => $ids,
				],
			]);
			foreach ($items as $item)
			{
				if ($userPermissions->canDeleteItem($item))
				{
					Driver::getInstance()->getFactory()->getDeleteCommand($item)->run();
				}
			}
		}
	}

	protected function prepareGrid(): array
	{
		$grid = [];
		if(!$this->type)
		{
			return $grid;
		}
		$gridId = $this->getGridId();
		$grid['GRID_ID'] = $gridId;
		$grid['COLUMNS'] = array_merge($this->itemProvider->getGridColumns(), $this->itemUfProvider->getGridColumns());

		$userIds = [];

		$gridOptions = new Bitrix\Main\Grid\Options($gridId);
		$navParams = $gridOptions->getNavParams(['nPageSize' => 10]);
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);

		$pageNavigation = new \Bitrix\Main\UI\PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(false)->setPageSize($pageSize)->initFromUri();

		$this->arResult['GRID']['ROWS'] = $buffer = [];
		$listFilter = $this->getListFilter();
		$list = $this->type->getItems([
			'order' => $gridSort['sort'],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'filter' => $listFilter,
		]);
		$totalCount = $this->type->getItemsCount($listFilter);
		if(count($list) > 0)
		{
			foreach($list as $item)
			{
				$this->getDisplay()->addValues($item->getId(), $item->collectValues());
				$userIds += $item->getUserIds();
			}
			$usersData = static::getUsers($userIds);
			foreach($list as $item)
			{
				$data = $item->collectValues();
				$grid['ROWS'][] = [
					'id' => $item->getId(),
					'data' => $data,
					'columns' => $this->getItemColumn($item, $usersData),
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
		$grid['AJAX_ID'] = \CAjax::GetComponentID("bitrix:main.ui.grid", '', '');
		$grid['SHOW_PAGESIZE'] = true;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['SHOW_ROW_CHECKBOXES'] = true;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = true;
		$grid['SHOW_ACTION_PANEL'] = true;
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

		return $grid;
	}

	protected function getItemColumn(\Bitrix\Rpa\Model\Item $item, array $usersData): array
	{
		$data = $item->collectValues();
		$stage = $item->getStage();
		if($stage)
		{
			$data['STAGE_ID'] = htmlspecialcharsbx($stage->getName());
		}

		$displayedValues = $this->getDisplay()->getValues($item->getId());
		if(!empty($displayedValues))
		{
			$data = array_merge($data, $displayedValues);
		}
		$tasks = 0;
		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager)
		{
			$tasks = $taskManager->getUserItemIncompleteCounter($item);
		}
		$titleColumn = 'ID';
		$titleFieldName = $item->getType()->getItemUfNameFieldName();
		if(array_key_exists($titleFieldName, $data))
		{
			$titleColumn = $titleFieldName;
		}
		$urlManager = Driver::getInstance()->getUrlManager();
		$detailUrl = htmlspecialcharsbx($urlManager->getItemDetailUrl($item->getType()->getId(), $item->getId()));
		$tasksUrl = htmlspecialcharsbx($urlManager->getTaskUrl($item->getType()->getId(), $item->getId()));
		$counter = '';
		if($tasks > 0)
		{
			$counter = $tasks;
		}
		$titleColumnData = '<span class="rpa-title-indicators"><a href="'.$detailUrl.'">'.$data[$titleColumn].'</a>';
		if($counter > 0)
		{
			$titleColumnData .= '<a href="'.$tasksUrl.'" class="rpa-item-waiting-tasks"><span class="rpa-counter" id="rpa-grid-item-counter-'.$item->getType()->getId().'-'.$item->getId().'">'.$counter.'</span></a>';
		}
		$titleColumnData .= '</span>';
		$data[$titleFieldName] = $titleColumnData;
		if($data['CREATED_BY'] > 0 && isset($usersData[$data['CREATED_BY']]))
		{
			$data['CREATED_BY'] = $this->prepareUserDataForGrid($usersData[$data['CREATED_BY']]);
		}
		else
		{
			$data['CREATED_BY'] = '';
		}
		if($data['MOVED_BY'] > 0 && isset($usersData[$data['MOVED_BY']]))
		{
			$data['MOVED_BY'] = $this->prepareUserDataForGrid($usersData[$data['MOVED_BY']]);
		}
		else
		{
			$data['MOVED_BY'] = '';
		}
		if($data['UPDATED_BY'] > 0 && isset($usersData[$data['UPDATED_BY']]))
		{
			$data['UPDATED_BY'] = $this->prepareUserDataForGrid($usersData[$data['UPDATED_BY']]);
		}
		else
		{
			$data['UPDATED_BY'] = '';
		}

		return $data;
	}

	protected function getToolbarParameters(): array
	{
		$parameters = parent::getToolbarParameters();
		$parameters['views']['list']['isActive'] = true;

		return $parameters;
	}
}