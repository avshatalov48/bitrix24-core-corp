<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Intranet;

use Bitrix\Crm\Tracking;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmTrackingExpensesComponent extends \CBitrixComponent implements Controllerable
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		return true;
	}

	protected function initParams()
	{
		$this->arParams['GRID_ID'] = isset($this->arParams['GRID_ID']) ? $this->arParams['GRID_ID'] : 'CRM_TRACKING_SOURCE_EXPENSES_GRID';
		$this->arParams['FILTER_ID'] = isset($this->arParams['FILTER_ID']) ? $this->arParams['FILTER_ID'] : $this->arParams['GRID_ID'] . '_FILTER';

		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? $this->arParams['SET_TITLE'] == 'Y' : true;
		$this->arParams['CAN_EDIT'] = isset($this->arParams['CAN_EDIT'])
			?
			$this->arParams['CAN_EDIT']
			:
			true;
	}

	protected function preparePost()
	{
		$ids = $this->request->get('ID');
		$action = $this->request->get('action_button_' . $this->arParams['GRID_ID']);
		switch ($action)
		{
			case 'delete':
				if (!is_array($ids))
				{
					$ids = array($ids);
				}

				foreach ($ids as $id)
				{
					Tracking\Internals\ExpensesPackTable::delete($id);
				}
				break;
		}
	}

	protected function prepareResult()
	{
		$sources = Tracking\Provider::getActualSources();
		$sources = array_combine(
			array_column($sources, 'ID'),
			array_column($sources, 'NAME')
		);

		/* Set title */
		$this->arResult['SOURCE_NAME'] = $sources[$this->arParams['ID']];
		if ($this->arParams['SET_TITLE'])
		{
			/**@var \CAllMain*/
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage(
				'CRM_TRACKING_EXPENSES_COMP_TITLE',
				['%name%' => $this->arResult['SOURCE_NAME']]
			));
		}

		$this->arResult['ERRORS'] = array();
		$this->arResult['ROWS'] = array();

		if ($this->request->isPost() && check_bitrix_sessid() && $this->arParams['CAN_EDIT'])
		{
			$this->preparePost();
		}

		// set ui filter
		$this->setUiFilter();

		// set ui grid columns
		$this->setUiGridColumns();

		// create nav
		$nav = new PageNavigation("page-crm-tracking-expenses");
		$nav->allowAllRecords(true)->setPageSize(10)->initFromUri();

		// get rows
		$list = Tracking\Internals\ExpensesPackTable::getList(array(
			'select' => [
				'ID', 'DATE_INSERT', 'SOURCE_ID', 'DATE_FROM', 'DATE_TO',
				'ACTIONS', 'EXPENSES', 'CURRENCY_ID', 'COMMENT'
			],
			'filter' => $this->getDataFilter(),
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
			'count_total' => true,
			'order' => $this->getGridOrder()
		));
		foreach ($list as $item)
		{
			$item['COMMENT'] = $item['COMMENT'] ?: $item['PACK_COMMENT'];
			$item['EXPENSES'] = \CCrmCurrency::MoneyToString(
				$item['EXPENSES'],
				$item['CURRENCY_ID'] ?: \CCrmCurrency::GetAccountCurrencyID()
			);
			$this->arResult['ROWS'][] = $item;
		}

		$this->arResult['TOTAL_ROWS_COUNT'] = $list->getCount();

		// set rec count to nav
		$nav->setRecordCount($list->getCount());
		$this->arResult['NAV_OBJECT'] = $nav;

		$this->arResult['IS_ADD'] = $this->request->get('add') === 'Y';
		$this->arResult['CURRENCIES'] = \CCrmCurrency::GetAll();

		return true;
	}

	protected function getDataFilter()
	{
		$filterOptions = new FilterOptions($this->arParams['FILTER_ID']);
		$requestFilter = $filterOptions->getFilter($this->arResult['FILTERS']);

		$filter = [
			'=TYPE_ID' => Tracking\Internals\SourceExpensesTable::TYPE_MANUAL,
		];
		if ($this->arParams['ID'])
		{
			$filter['=SOURCE_ID'] = (int) $this->arParams['ID'];
		}
		elseif (isset($requestFilter['SOURCE_ID']) && $requestFilter['SOURCE_ID'])
		{
			$filter['=SOURCE_ID'] = (int) $requestFilter['SOURCE_ID'];
		}

		return $filter;
	}

	protected function getGridOrder()
	{
		$defaultSort = array('ID' => 'DESC');

		$gridOptions = new GridOptions($this->arParams['GRID_ID']);
		$sorting = $gridOptions->getSorting(array('sort' => $defaultSort));

		$by = key($sorting['sort']);
		$order = mb_strtoupper(current($sorting['sort'])) === 'ASC' ? 'ASC' : 'DESC';

		$list = array();
		foreach ($this->getUiGridColumns() as $column)
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

		return array($by => $order);
	}

	protected function setUiGridColumns()
	{
		$this->arResult['COLUMNS'] = $this->getUiGridColumns();
	}

	protected function getUiGridColumns()
	{
		return [
			[
				"id" => "ID",
				"name" => "ID",
				"sort" => "ID",
				"default" => false
			],
			[
				"id" => "SOURCE_ID",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_SOURCE_ID'),
				"sort" => "SOURCE_ID",
			],
			[
				"id" => "PERIOD",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_PERIOD'),
				"sort" => "DATE_FROM",
				"default" => true
			],
				/*
			[
				"id" => "DATE_FROM",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_DATE_FROM'),
				"sort" => "DATE_FROM",
				"default" => true
			],
			[
				"id" => "DATE_TO",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_DATE_TO'),
				"sort" => "DATE_TO",
				"default" => true
			],
			*/
			[
				"id" => "EXPENSES",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_EXPENSES'),
				"default" => true
			],
			[
				"id" => "ACTIONS",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_ACTIONS'),
				"sort" => "ACTIONS",
				"default" => true
			],
			[
				"id" => "COMMENT",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_COMMENT'),
			],
			[
				"id" => "DATE_INSERT",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_DATE_INSERT'),
			],
		];
	}

	protected function setUiFilter()
	{
		$this->arResult['FILTERS'] = array(
			array(
				"id" => "SOURCE_ID",
				"name" => Loc::getMessage('CRM_TRACKING_EXPENSES_COMP_UI_COLUMN_SOURCE_ID'),
				"default" => true
			),
		);
	}

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{
		if (!$this->errors->isEmpty())
		{
			$this->printErrors();
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->printErrors();
		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			return $arParams;
		}

		$this->arParams = $arParams;
		$this->initParams();
		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
		}

		return $this->arParams;
	}

	public function configureActions()
	{
		return [
			'addExpenses' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'remove' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
			'removeList' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
		];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'CAN_EDIT',
		];
	}

	protected function prepareAjaxAnswer(array $data)
	{
		/** @var Error $error */
		$error = $this->errors->current();

		return [
			'data' => $data,
			'error' => !$this->errors->isEmpty(),
			'text' => $error ? $error->getMessage() : ''
		];
	}

	public function removeAction($id)
	{
		Tracking\Internals\ExpensesPackTable::delete($id);
		return $this->prepareAjaxAnswer([]);
	}

	public function removeListAction($list)
	{
		foreach ($list as $id)
		{
			Tracking\Internals\ExpensesPackTable::delete($id);
		}
		return $this->prepareAjaxAnswer([]);
	}

	public function addExpensesAction($sourceId, $from, $to, $sum, $currencyId, $actions, $comment)
	{
		Tracking\Internals\SourceExpensesTable::addExpenses(
			$sourceId,
			new \Bitrix\Main\Type\Date($from),
			new \Bitrix\Main\Type\Date($to),
			$sum, $currencyId, $actions, $comment
		);
		return $this->prepareAjaxAnswer([]);
	}
}