<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main;
use Bitrix\Bitrix24\Feature;

use Bitrix\Crm\Tracking;
use Bitrix\Crm\Settings\LeadSettings;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmTrackingReportSourceComponent extends \CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` not installed.'));
		}
		return true;
	}

	protected function initParams()
	{
		$this->arParams['GRID_ID'] = $this->arParams['GRID_ID'] ?? $this->request->get('gridId') ?? 'crm-tracking-report-ad';
		$this->arParams['SOURCE_ID'] = (int) $this->arParams['SOURCE_ID'] ?? $this->request->get('SOURCE_ID');
		$this->arParams['LEVEL'] = (int) $this->arParams['LEVEL'] ?? Tracking\Source\Level\Type::Campaign;
		$this->arParams['PARENT_ID'] = (int) $this->arParams['PARENT_ID'] ?? 0;
		$this->arParams['PERIOD_FROM'] = (new Main\Type\Date($this->arParams['PERIOD_FROM'] ?? $this->request->get('PERIOD_FROM')));
		$this->arParams['PERIOD_TO'] = (new Main\Type\Date($this->arParams['PERIOD_TO'] ?? $this->request->get('PERIOD_TO')));
	}

	protected function prepareResult()
	{
		$builder = (new Tracking\Ad\ReportBuilder())
			->setSourceId($this->arParams['SOURCE_ID'])
			->setPeriod($this->arParams['PERIOD_FROM'], $this->arParams['PERIOD_TO'])
		;

		$this->arResult['SIBLINGS'] = [
			'CURRENT' => null,
			'LIST' => [],
		];
		if ($this->arParams['PARENT_ID'])
		{
			$parent = Tracking\Internals\SourceChildTable::getRowById($this->arParams['PARENT_ID']);
			$siblings = Tracking\Internals\SourceChildTable::getList([
				'select' => ['ID', 'LEVEL', 'TITLE'],
				'filter' => [
					'=SOURCE_ID' => $this->arParams['SOURCE_ID'],
					'=PARENT_ID' => $parent['PARENT_ID'],
				],
			]);
			foreach ($siblings as $sibling)
			{
				$this->arResult['SIBLINGS']['LIST'][] = [
					'title' => $sibling['TITLE'],
					'sourceId' => $this->arParams['SOURCE_ID'],
					'level' => $this->arParams['LEVEL'],
					'parentId' => $sibling['ID'],
					'selected' => $sibling['ID'] == $this->arParams['PARENT_ID'],
				];
			}
		}
		else
		{
			$parent = Tracking\Internals\SourceTable::getRowById($this->arParams['SOURCE_ID']);
			foreach (Tracking\Provider::getReadySources() as $sibling)
			{
				if ($parent['CODE'] !== $sibling['CODE'])
				{
					continue;
				}

				$sourceObject = new Bitrix\Crm\Tracking\Analytics\Ad($sibling);
				if (!$sourceObject->isSupportExpensesReport())
				{
					continue;
				}

				$this->arResult['SIBLINGS']['LIST'][] = [
					'title' => $sibling['NAME'],
					'sourceId' => (int) $sibling['ID'],
					'level' => $this->arParams['LEVEL'],
					'parentId' => $this->arParams['PARENT_ID'],
					'selected' => $sibling['ID'] == $this->arParams['SOURCE_ID'],
				];
			}
		}

		foreach ($this->arResult['SIBLINGS']['LIST'] as $sibling)
		{
			if ($sibling['selected'])
			{
				$this->arResult['SIBLINGS']['CURRENT'] = $sibling;
			}
		}

		$this->arResult['PARENT'] = [
			'NAME' => $this->arParams['LEVEL']
				? Tracking\Source\Level\Type::getCaption(Tracking\Source\Level\Type::getPrevId($this->arParams['LEVEL']))
				: Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_AD_ACCOUNT')
		];

		$this->arResult['ERRORS'] = array();

		$this->arResult['FEATURE_CODE'] = Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled("crm_tracking_reports")
			? "crm_tracking_reports"
			: null
		;

		$this->arResult['ROWS'] = $this->arResult['FEATURE_CODE']
			? []
			: $builder->getRows($this->arParams['LEVEL'], $this->arParams['PARENT_ID'])
		;

		$this->setUiGridColumns();

		return true;
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

	protected function getGridOrder()
	{
		$defaultSort = array('ID' => 'DESC');

		$gridOptions = new Main\Grid\Options($this->arParams['GRID_ID']);
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
		$list = [
			[
				'id' => 'code',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_CODE'),
				'default' => false,
			],
			[
				'id' => 'title',
				'name' => Tracking\Source\Level\Type::getCaption((int) $this->arParams['LEVEL']),
				"default" => true,
				'class' => 'crm-tracking-report-source-grid-column-code',
			],
			[
				'id' => 'impressions',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_IMPRESSIONS'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'actions',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_ACTIONS'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'ctr',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_CTR'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'outcome',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_OUTCOME'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'cpc',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_CPC'),
				'default' => false,
				'align' => 'right',
			],
			[
				'id' => 'leads',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_LEADS'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'deals',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_DEALS'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'successDeals',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_SUCCESS_DEALS'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'income',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_INCOME'),
				'default' => true,
				'align' => 'right',
			],
			[
				'id' => 'roi',
				'name' => Loc::getMessage('CRM_TRACKING_REPORT_SOURCE_COLUMN_ROI'),
				'default' => true,
			],
		];

		if (!LeadSettings::isEnabled())
		{
			return array_filter(
				$list,
				function ($item)
				{
					return $item['id'] !== 'leads';
				}
			);
		}

		return $list;
	}

	protected function setUiFilter()
	{
		$this->arResult['FILTERS'] = [];
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errors = new ErrorCollection();
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
}