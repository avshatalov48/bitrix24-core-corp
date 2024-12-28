<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Intranet\UserTable;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Intranet\User\Grid\UserGrid;

use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Component\UserList;
use Bitrix\Main\Loader;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Filter;
use Bitrix\Main\Filter\UserDataProvider;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Main\PhoneNumber;
use Bitrix\Main\Grid\Export\ExcelExporter;
use Bitrix\Intranet\User\Filter\Presets\FilterPresetManager;
use Bitrix\Intranet\User\Filter\Provider\IntranetUserDataProvider;
use Bitrix\Intranet\User\Filter\IntranetUserSettings;

Loc::loadMessages(__FILE__);

Loader::includeModule('intranet');

class CIntranetUserListComponent extends UserList
{
	protected $gridId = 'INTRANET_USER_GRID_'.SITE_ID;
	protected $filterId = 'INTRANET_USER_LIST_'.SITE_ID;
	private UserGrid $grid;

	private function extranetSite()
	{
		static $result = null;

		if ($result === null)
		{
			$result = (
				Loader::includeModule('extranet')
				&& \CExtranet::isExtranetSite()
			);
		}

		return $result;
	}

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		if (empty($params['PATH_TO_DEPARTMENT']))
		{
			$params['PATH_TO_DEPARTMENT'] = SITE_DIR.'company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#';
		}
		if (empty($params['PATH_TO_USER']))
		{
			$params['PATH_TO_USER'] = Option::get('intranet', 'search_user_url', SITE_DIR.'company/personal/user/#ID#/');
		}

		if (!empty($params['LIST_URL']))
		{
			$oldValue = Option::get('intranet', 'list_user_url', '', SITE_ID);
			if ($params['LIST_URL'] != $oldValue)
			{
				Option::set('intranet', 'list_user_url', $params['LIST_URL'], SITE_ID);
			}
		}

		if (
			!isset($params['EXPORT_MODE'])
			|| $params['EXPORT_MODE'] !== 'Y'
		)
		{
			$params['EXPORT_MODE'] = 'N';
		}
		else
		{
			if (
				!isset($params['EXPORT_TYPE'])
				|| !in_array($params['EXPORT_TYPE'], ['excel'])
			)
			{
				$params['EXPORT_TYPE'] = 'excel';
			}
		}

		if (
			!isset($params['USER_PROPERTY_LIST'])
			|| !is_array($params['USER_PROPERTY_LIST'])
		)
		{
			$params['USER_PROPERTY_LIST'] = $this->getUserPropertyList();
		}
		elseif ($params['EXPORT_MODE'] !== 'Y')
		{
			$this->setUserPropertyList($params['USER_PROPERTY_LIST']);
		}

		return $params;
	}

	private function getGrid(): UserGrid
	{
		if (!isset($this->grid))
		{
			$settings = new UserSettings([
				'ID' => 'INTRANET_USER_LIST_'.SITE_ID,
				'SHOW_ROW_CHECKBOXES' => false,
				'MODE' => $this->arParams['EXPORT_TYPE'] ?? 'html'
			]);

			$this->grid = new UserGrid($settings);

			$this->grid->setTotalCountCalculator(function() {
				$params = $this->grid->getOrmParams();
				unset($params['limit'], $params['offset']);
				$params['count_total'] = true;

				return UserTable::getList($params)->getCount();
			});
		}

		return $this->grid;
	}

	protected function prepareData()
	{
		$result = [];

		$result['GRID_ID'] = $this->filterId;
		$result['FILTER_ID'] = $this->filterId;
		$result['EXTRANET_SITE'] = ($this->extranetSite() ? 'Y' : 'N');
		$result['EXCEL_EXPORT_LIMITED'] = (
			Loader::includeModule('bitrix24')
			&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('intranet_user_export_excel')
		);

		$grid = $this->getGrid();
		$grid->processRequest();

		if ($this->arParams['EXPORT_MODE'] === 'Y')
		{
			$params = $grid->getOrmParams();
			unset($params['limit'], $params['offset']);

			$grid->setRawRows(
				UserTable::getList($params)->fetchAll()
			);
		}
		else
		{
			$grid->setRawRowsWithLazyLoadPagination(function(array $ormParams) {
				return UserTable::getList($ormParams)->fetchAll();
			});
		}

		$result['IS_DEFAULT_SORT'] = $grid->getOrmOrder() === [
			'STRUCTURE_SORT' => 'DESC',
			];
		$result['GET_TOTAL_COUNTER_ID'] = 'intranet-user-list-total-counter';

		$result['GRID_PARAMS'] = \Bitrix\Main\Grid\Component\ComponentParams::get(
			$grid,
			[
				'NAV_COMPONENT_TEMPLATE' => 'modern',
				'TOTAL_ROWS_COUNT_HTML' => $grid->getTotalRowsCountHtml(),
			]
		);

		$result['GRID_FILTER'] = $grid->getFilter();
		$result['FILTER_PRESETS'] = $grid->getFilter()?->getFilterPresets();

		$filterSettings = $grid->getFilter()?->getFilterSettings();
		$result['WAITING_FILTER_AVAILABLE'] = $filterSettings?->isFilterAvailable(IntranetUserSettings::WAIT_CONFIRMATION_FIELD) ?? false;
		$result['INVITE_FILTER_AVAILABLE'] = $filterSettings?->isFilterAvailable(IntranetUserSettings::INVITED_FIELD) ?? false;

		return $result;
	}

	public function executeComponent()
	{
		$this->arResult = $this->prepareData();

		if (Loader::includeModule('pull'))
		{
			\CPullWatch::Add(\Bitrix\Intranet\CurrentUser::get()->getId(), \Bitrix\Intranet\Invitation::PULL_MESSAGE_TAG);
		}

		if ($this->arParams['EXPORT_MODE'] !== 'Y')
		{
			$this->includeComponentTemplate();
		}
		elseif (!$this->arResult['EXCEL_EXPORT_LIMITED'])
		{
			$this->getExcelExporter()->process($this->grid, 'users');
		}
	}

	private function getExcelExporter(): ExcelExporter
	{
		$this->excelExporter ??= new ExcelExporter();

		return $this->excelExporter;
	}
}
