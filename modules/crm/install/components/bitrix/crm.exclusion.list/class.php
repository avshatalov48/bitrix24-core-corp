<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Crm\Exclusion;
use Bitrix\Crm\Communication;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmExclusionListComponent extends CBitrixComponent implements Controllerable
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
		if (!Exclusion\Access::current()->canRead())
		{
			$this->errors->setError(new Error(Exclusion\Access::getErrorText(Exclusion\Access::READ)));
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
		$this->arParams['CAN_EDIT'] = isset($this->arParams['CAN_EDIT']) ? $this->arParams['CAN_EDIT'] : Exclusion\Access::current()->canWrite();

		return $this->arParams;
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
					Exclusion\Store::remove($id);
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
		$nav = new PageNavigation("page-crm-exclusion");
		$nav->allowAllRecords(false)->setPageSize(10)->initFromUri();

		// get rows
		$list = Exclusion\Store::getList(array(
			'select' => array(
				'ID', 'COMMENT', 'TYPE_ID', 'CODE', 'DATE_INSERT'
			),
			'filter' => $this->getDataFilter(),
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
			'count_total' => true,
			'order' => $this->getGridOrder()
		));
		foreach ($list as $item)
		{
			$item['TYPE_NAME'] = Communication\Type::getCaption($item['TYPE_ID']);
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

		$filter = [];
		if (isset($requestFilter['COMMENT']) && $requestFilter['COMMENT'])
		{
			$filter['COMMENT'] = '%' . $requestFilter['COMMENT'] . '%';
		}
		if (isset($requestFilter['CODE']) && $requestFilter['CODE'])
		{
			$filter['CODE'] = '%' . $requestFilter['CODE'] . '%';
		}
		if (isset($requestFilter['TYPE_ID']) && $requestFilter['TYPE_ID'])
		{
			$filter['=TYPE_ID'] = $requestFilter['TYPE_ID'];
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
		return array(
			array(
				"id" => "ID",
				"name" => "ID",
				"sort" => "ID",
				"default" => false
			),
			array(
				"id" => "DATE_INSERT",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_DATE_INSERT'),
				"sort" => "DATE_INSERT",
				"default" => false
			),
			array(
				"id" => "TYPE_ID",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_TYPE_ID'),
				"sort" => "TYPE_ID",
				"default" => true,
			),
			array(
				"id" => "CODE",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_CODE'),
				"sort" => "CODE",
				"default" => true
			),
			array(
				"id" => "COMMENT",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_COMMENT'),
				"sort" => "COMMENT",
				"default" => true
			),
		);
	}

	protected function setUiFilter()
	{
		$this->arResult['FILTERS'] = array(
			array(
				"id" => "COMMENT",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_COMMENT'),
				"default" => true,
			),
			array(
				"id" => "CODE",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_CODE'),
				"default" => true,
			),
			array(
				"id" => "TYPE_ID",
				"name" => Loc::getMessage('CRM_EXCLUSION_LIST_UI_COLUMN_TYPE_ID'),
				"default" => true,
				"type" => "list",
				"params" => array('multiple' => 'Y'),
				"items" => [
					Communication\Type::EMAIL => Communication\Type::getCaption(Communication\Type::EMAIL),
					Communication\Type::PHONE => Communication\Type::getCaption(Communication\Type::PHONE),
				]
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
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_EXCLUSION_LIST_TITLE'));
		}

		return $this->arParams;
	}

	public function configureActions()
	{
		return array();
	}

	public function removeExclusionAction($exclusionId)
	{
		if (!$this->arParams['CAN_EDIT'])
		{
			$this->errors->setError(new Error(Exclusion\Access::getErrorText(Exclusion\Access::WRITE)));
			return;
		}

		if (!$this->errors->isEmpty())
		{
			return;
		}

		Exclusion\Store::remove($exclusionId);
	}
}