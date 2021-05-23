<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;
use Bitrix\Voximplant\Integration\Report\CallType;

class VoximplantReportPeriodCompareGridComponent extends \CBitrixComponent
{
	protected $gridId = 'telephony_report_period_compare_grid';

	/** @var \Bitrix\Report\VisualConstructor\Entity\Widget */
	protected $widget;
	protected $dataProviderResult;

	protected $filterOptions;
	protected $filter;

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
		$this->filter = $this->filterOptions->getFilter();

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
					'CURRENT_DATE' => [
						'value' => $value['CURRENT_DATE'],
						'valueFormatted' => $value['CURRENT_DATE_FORMATTED'],
						'url' => $url['CURRENT_DATE'],
					],
					'PREVIOUS_DATE' => [
						'value' => $value['PREVIOUS_DATE'],
						'valueFormatted' => $value['PREVIOUS_DATE_FORMATTED'],
						'url' => $url['PREVIOUS_DATE'],
					],
					'CURRENT_VALUE' => [
						'value' => $value['CURRENT_VALUE'],
						'url' => $url['CURRENT_VALUE'],
					],
					'PREVIOUS_VALUE' => [
						'value' => $value['PREVIOUS_VALUE'],
						'url' => $url['PREVIOUS_VALUE'],
					],
					'DYNAMICS' => [
						'value' => $value['DYNAMICS'],
					],
				],
				'actions' => []
			];

			$rows[] = $row;
		}

		$sorting = $this->gridOptions->getSorting(['sort' => ['CURRENT_DATE' => 'ASC']]);

		foreach ($sorting['sort'] as $k => $v)
		{
			$column = $k;
			$direction = $v;
		}

		return $this->sortRows($rows, $column, $direction);
	}

	protected function getColumns()
	{

		$currentValueName = '';
		$previousValueName = '';

		switch ($this->filter['INCOMING'])
		{
			case CallType::INCOMING:
				$currentValueName = $previousValueName = Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_INCOMING');
				break;
			case CallType::OUTGOING:
				$currentValueName = $previousValueName = Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_OUTGOING');
				break;
			case CallType::MISSED:
				$currentValueName = $previousValueName = Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_MISSED');
				break;
			case CallType::CALLBACK:
				$currentValueName = $previousValueName = Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_CALLBACK');
				break;
			default:
				$currentValueName = $previousValueName = Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_COUNT');
				break;
		}

		$currentValueName .= ' ' . Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_CURRENT_PERIOD');
		$previousValueName .= ' ' . Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_PREVIOUS_PERIOD');

		$columns = [
			[
				'id' => 'CURRENT_DATE',
				'sort' => 'CURRENT_DATE',
				'name' => Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_CURRENT_DATE'),
				'default' => true,
			],
			[
				'id' => 'PREVIOUS_DATE',
				'sort' => 'PREVIOUS_DATE',
				'name' => Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_PREVIOUS_DATE'),
				'default' => true,
			],
			[
				'id' => 'CURRENT_VALUE',
				'sort' => 'CURRENT_VALUE',
				'name' => $currentValueName,
				'default' => true,
			],
			[
				'id' => 'PREVIOUS_VALUE',
				'sort' => 'PREVIOUS_VALUE',
				'name' => $previousValueName,
				'default' => true,
			],
			[
				'id' => 'DYNAMICS',
				'sort' => 'DYNAMICS',
				'name' => Loc::getMessage('TELEPHONY_REPORT_PERIOD_COMPARE_DYNAMICS'),
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
