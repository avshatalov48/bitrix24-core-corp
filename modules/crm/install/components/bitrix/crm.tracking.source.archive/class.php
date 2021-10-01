<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Intranet;
use Bitrix\Crm\Tracking;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmTrackingSourceArchiveComponent extends CBitrixComponent implements Controllerable
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			return false;
		}
		return true;
	}

	protected function initParams()
	{
		$this->arParams['PATH_TO_LIST'] = isset($this->arParams['PATH_TO_LIST']) ? $this->arParams['PATH_TO_LIST'] : '';
		$this->arParams['PATH_TO_IMPORT'] = isset($this->arParams['PATH_TO_IMPORT']) ? $this->arParams['PATH_TO_IMPORT'] : '';

		$this->arParams['GRID_ID'] = isset($this->arParams['GRID_ID']) ? $this->arParams['GRID_ID'] : 'CRM_EXCLUSION_GRID';
		$this->arParams['FILTER_ID'] = isset($this->arParams['FILTER_ID']) ? $this->arParams['FILTER_ID'] : $this->arParams['GRID_ID'] . '_FILTER';

		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? $this->arParams['SET_TITLE'] == 'Y' : true;
		$this->arParams['CAN_EDIT'] = isset($this->arParams['CAN_EDIT']) ? $this->arParams['CAN_EDIT'] : true;

		return $this->arParams;
	}

	protected function preparePost()
	{
		$ids = $this->request->get('ID');
		$action = $this->request->get('action_button_' . $this->arParams['GRID_ID']);
		switch ($action)
		{
			case 'unarchive':
				if (!is_array($ids))
				{
					$ids = array($ids);
				}

				foreach ($ids as $id)
				{
					Tracking\Internals\SourceTable::update($id, ['ACTIVE' => 'Y']);
				}
				break;
		}
	}

	protected function prepareResult()
	{
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
		$nav = new PageNavigation("page-crm-tracking-source-archive");
		$nav->allowAllRecords(false)->setPageSize(10)->initFromUri();

		// get rows
		$list = Tracking\Internals\SourceTable::getList(array(
			'select' => array(
				'ID', 'DATE_CREATE', 'NAME',
			),
			'filter' => $this->getDataFilter(),
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
			'count_total' => true,
			'order' => $this->getGridOrder()
		));
		foreach ($list as $item)
		{
			$item['URLS'] = [
				'EDIT' => str_replace(
					'#id#',
					$item['ID'],
					$this->arParams['PATH_TO_EDIT']
				)
			];
			$this->arResult['ROWS'][] = $item;
		}

		$this->arResult['TOTAL_ROWS_COUNT'] = $list->getCount();

		// set rec count to nav
		$nav->setRecordCount($list->getCount());
		$this->arResult['NAV_OBJECT'] = $nav;

		return true;
	}

	protected function getDataFilter()
	{
		$filterOptions = new FilterOptions($this->arParams['FILTER_ID']);
		$requestFilter = $filterOptions->getFilter($this->arResult['FILTERS']);

		$filter = ['=ACTIVE' => 'N'];
		if (isset($requestFilter['NAME']) && $requestFilter['NAME'])
		{
			$filter['NAME'] = '%' . $requestFilter['NAME'] . '%';
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
				"id" => "NAME",
				"name" => Loc::getMessage('CRM_TRACKING_ARCHIVE_UI_COLUMN_NAME'),
				"sort" => "NAME",
				"default" => true
			],
			[
				"id" => "DATE_CREATE",
				"name" => Loc::getMessage('CRM_TRACKING_ARCHIVE_UI_COLUMN_DATE_CREATE'),
				"sort" => "DATE_CREATE",
				"default" => true
			],
		];
	}

	protected function setUiFilter()
	{
		$this->arResult['FILTERS'] = [
			[
				"id" => "NAME",
				"name" => Loc::getMessage('CRM_TRACKING_ARCHIVE_UI_COLUMN_NAME'),
				"default" => true,
			],
		];
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
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		$this->arParams = $arParams;

		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
		}

		$this->initParams();

		/* Set title */
		if ($this->arParams['SET_TITLE'])
		{
			/**@var CAllMain*/
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_TRACKING_ARCHIVE_TITLE'));
		}

		return $this->arParams;
	}

	public function configureActions()
	{
		return [
			'unarchive' => [
				'+prefilters' => [
					new Intranet\ActionFilter\IntranetUser(),
				]
			],
		];
	}

	public function unarchiveAction($sourceId)
	{
		if (!$this->arParams['CAN_EDIT'])
		{
			$this->errors->setError(new Error(''));
			return;
		}

		if (!$this->errors->isEmpty())
		{
			return;
		}

		Tracking\Internals\SourceTable::update($sourceId, ['ACTIVE' => 'Y']);
	}
}