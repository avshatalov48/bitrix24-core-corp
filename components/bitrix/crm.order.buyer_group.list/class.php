<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CrmBuyerGroupsList extends \CBitrixComponent
{
	protected $action = null;

	protected $errors = [];

	protected function hasErrors()
	{
		return !empty($this->errors);
	}

	protected function showErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function onPrepareComponentParams($params)
	{
		$params['IFRAME'] = isset($params['IFRAME']) && $params['IFRAME'];

		return $params;
	}

	protected function getGridFilter()
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->getGridId());
		$requestFilter = $filterOptions->getFilter($this->getGridFilterResult()['FILTER']);
		$searchString = $filterOptions->getSearchString();

		$filter = [];

		if (!empty($requestFilter['NAME']))
		{
			$filter['NAME'] = '%'.$requestFilter['NAME'].'%';
		}

		if (!empty($requestFilter['DESCRIPTION']))
		{
			$filter['DESCRIPTION'] = '%'.$requestFilter['DESCRIPTION'].'%';
		}

		if ($searchString)
		{
			$filter[] = [
				'LOGIC' => 'OR',
				'NAME' => '%'.$searchString.'%',
				'DESCRIPTION' => '%'.$searchString.'%',
			];
		}

		return $filter;
	}

	protected function getGridOrder()
	{
		$defaultSort = ['ID' => 'DESC'];

		$gridOptions = new \Bitrix\Main\Grid\Options($this->getGridId());
		$this->arResult['GRID_SORT'] = $sorting = $gridOptions->getSorting(['sort' => $defaultSort]);

		$by = key($sorting['sort']);
		$order = mb_strtoupper(current($sorting['sort'])) === 'ASC' ? 'ASC' : 'DESC';

		$list = [];

		foreach ($this->getGridHeader() as $column)
		{
			if (!isset($column['sort']) || !$column['sort'])
			{
				continue;
			}

			$list[] = $column['sort'];
		}

		if (!in_array($by, $list))
		{
			return $defaultSort;
		}

		return [$by => $order];
	}

	protected function getGroups()
	{
		$filter = [
			'LOGIC' => 'OR',
			'=IS_SYSTEM' => 'N',
			'=STRING_ID' => \Bitrix\Crm\Order\BuyerGroup::BUYER_GROUP_NAME,
		];
		$extFilter = $this->getGridFilter();

		if (!empty($extFilter))
		{
			$filter = [$filter, $extFilter];
		}

		$order = $this->getGridOrder();

		$buyerGroupIterator = \Bitrix\Main\GroupTable::getList([
			'filter' => $filter,
			'order' => $order,
		]);

		return $buyerGroupIterator->fetchAll();
	}

	protected function getGridId()
	{
		return 'crmBuyerGroupsEditGrid';
	}

	protected function getGridHeader()
	{
		return [
			[
				'id' => 'ID',
				'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_ID'),
				'default' => false,
				'sort' => 'ID',
			],
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_NAME'),
				'default' => true,
				'sort' => 'NAME',
			],
			[
				'id' => 'DESCRIPTION',
				'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_DESCRIPTION'),
				'default' => true,
				'sort' => 'DESCRIPTION',
			],
			[
				'id' => 'ACTIVE',
				'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_ACTIVE'),
				'default' => true,
			],
			[
				'id' => 'C_SORT',
				'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_C_SORT'),
				'default' => false,
				'sort' => 'C_SORT',
			],
			[
				'id' => 'TIMESTAMP_X',
				'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_TIMESTAMP_X'),
				'default' => false,
				'sort' => 'TIMESTAMP_X',
			],
		];
	}

	protected function getGridRowColumns($group)
	{
		foreach ($group as &$field)
		{
			$field = htmlspecialcharsbx($field);
		}

		$linkToDetail = $this->getDetailUrl($group['ID']);

		$group['NAME'] = "<a href=\"{$linkToDetail}\">{$group['NAME']}</a>";
		$group['ACTIVE'] = $group['ACTIVE'] === 'Y'
			? Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_YES')
			: Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_NO');

		return $group;
	}

	protected function getDetailUrl($groupId)
	{
		return str_replace('#group_id#', $groupId, $this->arParams['PATH_TO_BUYER_GROUP_EDIT']);
	}

	protected function canRemoveGroup($group)
	{
		return isset($group['IS_SYSTEM']) && $group['IS_SYSTEM'] === 'N';
	}

	protected function getGridFilterResult()
	{
		return [
			'FILTER_ID' => $this->getGridId(),
			'FILTER' => [
				[
					'id' => 'NAME',
					'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_NAME'),
					'default' => true,
				],
				[
					'id' => 'DESCRIPTION',
					'name' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_COLUMN_DESCRIPTION'),
					'default' => true,
				],
			],
			'FILTER_PRESETS' => [],
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
		];
	}

	protected function getGridResult()
	{
		$grid = ['ID' => $this->getGridId()];

		$rows = [];

		foreach ($this->arResult['GROUPS'] as $group)
		{
			$actions = [
				[
					'text' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_OPEN_LINK'),
					'href' => $this->getDetailUrl($group['ID']),
				],
			];

			if ($this->canRemoveGroup($group))
			{
				$pathToRemove = CHTTP::urlAddParams(
					$this->arParams['PATH_TO_BUYER_GROUP_LIST'],
					[
						'action' => 'deleteGroupAjax',
						'ID' => $group['ID'],
						'sessid' => bitrix_sessid(),
					]
				);
				$pathToRemove = CUtil::JSEscape($pathToRemove);

				$actions[] = [
					'text' => Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_DELETE_LINK'),
					'onclick' => "BX.CrmUIGridExtension.processMenuCommand(
							'{$this->arResult['MANAGER_ID']}',
							BX.CrmUIGridMenuCommand.remove,
							{pathToRemove: '{$pathToRemove}'}
						)",
				];
			}

			$rows[] = [
				'data' => $group,
				'columns' => $this->getGridRowColumns($group),
				'actions' => $actions,
			];
		}

		$grid['ROWS'] = $rows;
		$grid['TOTAL_ROWS_COUNT'] = count($rows);
		$grid['HEADERS'] = $this->getGridHeader();

		return $grid;
	}

	protected function initialLoadAction()
	{
		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_ORDER_BUYER_GROUP_LIST_TITLE'));

		$this->arResult['GROUPS'] = $this->getGroups();
		$this->arResult['MANAGER_ID'] = $this->getGridId().'_MANAGER';
		$this->arResult['GRID'] = $this->getGridResult();
		$this->arResult['FILTER'] = $this->getGridFilterResult();
		$this->arResult['PATH_TO_CREATE_GROUP'] = $this->getDetailUrl(0);

		$this->includeComponentTemplate();
	}

	protected function checkGroupForDelete($groupId)
	{
		$group = \Bitrix\Main\GroupTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=ID' => $groupId,
				'=IS_SYSTEM' => 'N',
			],
		]);

		return $group !== null;
	}

	protected function deleteGroupAjaxAction()
	{
		if (!$this->checkModules() || !$this->checkPermissions())
		{
			$this->showErrors();

			return;
		}

		$groupId = $this->request->get('ID');

		if (!empty($groupId) && $this->checkGroupForDelete($groupId))
		{
			\Bitrix\Main\GroupTable::delete($groupId);
		}

		$this->initialLoadAction();
	}

	protected function checkModules()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');

			return false;
		}

		if (!CAllCrmInvoice::installExternalEntities())
		{
			return false;
		}

		if (!CCrmQuote::LocalComponentCausedUpdater())
		{
			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');

			return false;
		}

		return true;
	}

	protected function checkPermissions()
	{
		$crmPerms = new CCrmPerms(\Bitrix\Main\Engine\CurrentUser::get()->getId());

		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');

			return false;
		}

		$this->arResult['PERM_CAN_EDIT'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

		return true;
	}

	protected function prepareAction()
	{
		$action = (string)$this->request->get('action');

		if (empty($action))
		{
			$action = 'initialLoad';
		}

		return $action;
	}

	protected function doAction($action)
	{
		$funcName = $action.'Action';

		if (is_callable([$this, $funcName]))
		{
			$this->{$funcName}();
		}
	}

	public function executeComponent()
	{
		if (!$this->checkModules() || !$this->checkPermissions())
		{
			$this->showErrors();

			return;
		}

		$this->action = $this->prepareAction();
		$this->doAction($this->action);
	}
}