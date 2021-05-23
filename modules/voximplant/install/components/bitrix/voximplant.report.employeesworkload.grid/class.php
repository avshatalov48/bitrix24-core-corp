<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

class VoximplantReportEmployeesWorkloadGridComponent extends \CBitrixComponent
{
	protected $gridId = 'telephony_report_employees_workload_grid';

	/** @var \Bitrix\Report\VisualConstructor\Entity\Widget */
	protected $widget;
	protected $dataProviderResult;

	protected $filterOptions;

	/** @var Grid\Options */
	protected $gridOptions;

	public function executeComponent()
	{
		$this->dataProviderResult = $this->arParams['RESULT'] ?? [];
		if (isset($this->dataProviderResult['errors']))
		{
			$this->printErrors($this->dataProviderResult['errors']);
			return false;
		}
		$this->widget = $this->arParams['WIDGET'];
		$this->filterOptions = new Filter\Options($this->widget->getFilterId(), []);
		$this->gridOptions = new Grid\Options($this->gridId);

		$this->prepareResult();
		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function prepareResult()
	{
		$this->arResult['GRID'] = [
			'ID' => $this->gridId,
			'COLUMNS' => $this->getColumns(),
			'ROWS' => $this->prepareRows(),
		];
		$this->arResult['WIDGET'] = [
			'ID' => $this->widget->getGId(),
		];
		$this->arResult['BOARD'] = [
			'ID' => $this->widget->getBoardId(),
		];
	}

	public function sortRows($data, $column, $direction)
	{
		usort($data, $this->getCompareFunc($column, $direction));

		return $data;
	}

	protected function getCompareFunc($column, $direction)
	{
		return function ($a, $b) use ($column, $direction) {
			if ($direction === 'asc')
			{
				return $a['columns'][$column]['value'] <=> $b['columns'][$column]['value'];
			}
			else
			{
				return $b['columns'][$column]['value'] <=> $a['columns'][$column]['value'];
			}
		};
	}

	protected function prepareRows()
	{
		$rows = [];
		$rowId = 0;

		foreach ($this->dataProviderResult['data'] as $resultRow)
		{
			$value = $resultRow['value'];
			$url = $resultRow['url'];

			$row = [
				'id' => 'row-'.$rowId++,
				'columns' => [
					'EMPLOYEE' => [
						'value' => $value['USER_ID'],
						'valueFormatted' => $value['USER_NAME'],
						'icon' => $value['USER_ICON'],
					],
					'INCOMING' => [
						'value' => $value['INCOMING'],
						'url' => $url['INCOMING'],
					],
					'OUTGOING' => [
						'value' => $value['OUTGOING'],
						'url' => $url['OUTGOING'],
					],
					'MISSED' => [
						'value' => $value['MISSED'],
						'dynamics' => $value['MISSED_DYNAMICS'],
						'url' => $url['MISSED'],
					],
					'COUNT' => [
						'value' => $value['COUNT'],
						'url' => $url['COUNT'],
					],
					'DYNAMICS' => [
						'value' => $value['DYNAMICS'],
					],
				],
				'actions' => []
			];

			$rows[] = $row;
		}

		$sorting = $this->gridOptions->getSorting(['sort' => ['EMPLOYEE' => 'ASC']]);

		foreach ($sorting['sort'] as $k => $v)
		{
			$column = $k;
			$direction = $v;
		}

		return $this->sortRows($rows, $column, $direction);
	}

	protected function getColumns()
	{
		$columns = [
			[
				'id' => 'EMPLOYEE',
				'sort' => 'EMPLOYEE',
				'name' => Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_EMPLOYEE'),
				'default' => true,
			],
			[
				'id' => 'INCOMING',
				'sort' => 'INCOMING',
				'name' => Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_INCOMING'),
				'default' => true,
			],
			[
				'id' => 'OUTGOING',
				'sort' => 'OUTGOING',
				'name' => Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_OUTGOING'),
				'default' => true,
			],
			[
				'id' => 'MISSED',
				'sort' => 'MISSED',
				'name' => Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_MISSED'),
				'default' => true,
			],
			[
				'id' => 'COUNT',
				'sort' => 'COUNT',
				'name' => Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_COUNT'),
				'default' => true,
			],
			[
				'id' => 'DYNAMICS',
				'sort' => 'DYNAMICS',
				'name' => Loc::getMessage('TELEPHONY_REPORT_EMPLOYEES_WORKLOAD_DYNAMICS'),
				'default' => true,
			],
		];

		return $columns;
	}

	protected function printErrors(array $errors)
	{
		foreach ($errors as $error)
		{
			ShowError($error);
		}
	}
}
