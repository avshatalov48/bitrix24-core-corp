<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

class VoximplantReportCallDurationGridComponent extends \CBitrixComponent
{
	protected $gridId = 'telephony_report_call_duration_grid';

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
					'EMPLOYEE' => [
						'value' => $value['USER_ID'],
						'valueFormatted' => $value['USER_NAME'],
						'icon' => $value['USER_ICON'],
					],
					'INCOMING_DURATION' => [
						'value' => $value['INCOMING_DURATION'],
						'valueFormatted' => $value['INCOMING_DURATION_FORMATTED'],
						'url' => $url['INCOMING_DURATION'],
					],
					'INCOMING_DYNAMICS' => [
						'value' => $value['INCOMING_DYNAMICS'],
					],
					'OUTGOING_DURATION' => [
						'value' => $value['OUTGOING_DURATION'],
						'valueFormatted' => $value['OUTGOING_DURATION_FORMATTED'],
						'url' => $url['OUTGOING_DURATION'],
					],
					'OUTGOING_DYNAMICS' => [
						'value' => $value['OUTGOING_DYNAMICS'],
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
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_DURATION_EMPLOYEE'),
				'default' => true,
			],
			[
				'id' => 'INCOMING_DURATION',
				'sort' => 'INCOMING_DURATION',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_DURATION_INCOMING_DURATION'),
				'default' => true,
			],
			[
				'id' => 'INCOMING_DYNAMICS',
				'sort' => 'INCOMING_DYNAMICS',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_DURATION_INCOMING_DYNAMICS'),
				'default' => true,
			],
			[
				'id' => 'OUTGOING_DURATION',
				'sort' => 'OUTGOING_DURATION',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_DURATION_OUTGOING_DURATION'),
				'default' => true,
			],
			[
				'id' => 'OUTGOING_DYNAMICS',
				'sort' => 'OUTGOING_DYNAMICS',
				'name' => Loc::getMessage('TELEPHONY_REPORT_CALL_DURATION_OUTGOING_DYNAMICS'),
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
