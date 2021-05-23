<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

class VoximplantReportCallActivityGridComponent extends \CBitrixComponent
{
	protected $gridId = 'telephony_report_call_activity_grid';

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

		if (!isset($this->dataProviderResult['data']))
		{
			return [];
		}

		foreach ($this->dataProviderResult['data'] as $resultRow)
		{
			$value = $resultRow['value'];
			$url = $resultRow['url'];

			$row = [
				'id' => 'row-'.$rowId++,
				'columns' => [
					'DATE' => [
						'value' => $value['DATE'],
						'valueFormatted' => $value['DATE_FORMATTED'],
					],
					'INCOMING' => [
						'value' => $value['INCOMING'],
						'url' => $url['INCOMING'],
					],
					'MISSED' => [
						'value' => $value['MISSED'],
						'url' => $url['MISSED'],
					],
				],
				'actions' => []
			];

			$rows[] = $row;
		}

		$sorting = $this->gridOptions->getSorting(['sort' => ['DATE' => 'DESC']]);

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
				'id' => 'DATE',
				'sort' => 'DATE',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_ACTIVITY_DATE'),
				'default' => true,
			],
			[
				'id' => 'INCOMING',
				'sort' => 'INCOMING',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_ACTIVITY_INCOMING'),
				'default' => true,
			],
			[
				'id' => 'MISSED',
				'sort' => 'MISSED',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_ACTIVITY_MISSED'),
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
