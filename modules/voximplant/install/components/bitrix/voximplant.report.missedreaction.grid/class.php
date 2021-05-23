<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

class VoximplantReportMissedReactionGridComponent extends \CBitrixComponent
{
	protected $gridId = 'telephony_report_missed_reaction_grid_v2';

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
					'MISSED' => [
						'value' => $value['MISSED'],
						'url' => $url['MISSED'],
					],
					'UNANSWERED' => [
						'value' => $value['UNANSWERED'],
						'url' => $url['UNANSWERED'],
					],
					'AVG_RESPONSE_TIME' => [
						'value' => $value['AVG_RESPONSE_TIME'],
						'valueFormatted' => $value['AVG_RESPONSE_TIME_FORMATTED'],
					],
					'DYNAMICS' => [
						'value' => $value['DYNAMICS'],
						'valueFormatted' => $value['DYNAMICS_FORMATTED']
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
				'name' => Loc::getMessage('TELEPHONY_REPORT_MISSED_REACTION_EMPLOYEE'),
				'default' => true,
			],
			[
				'id' => 'MISSED',
				'sort' => 'MISSED',
				'name' => Loc::getMessage('TELEPHONY_REPORT_MISSED_REACTION_MISSED'),
				'default' => true,
			],
			[
				'id' => 'UNANSWERED',
				'sort' => 'INCOMING',
				'name' => Loc::getMessage('TELEPHONY_REPORT_MISSED_REACTION_UNANSWERED'),
				'default' => true,
			],
			[
				'id' => 'AVG_RESPONSE_TIME',
				'sort' => 'INCOMING',
				'name' => Loc::getMessage('TELEPHONY_REPORT_MISSED_REACTION_AVG_RESPONSE_TIME'),
				'default' => true,
			],
			[
				'id' => 'DYNAMICS',
				'sort' => 'DYNAMICS',
				'name' => Loc::getMessage('TELEPHONY_REPORT_MISSED_REACTION_DYNAMICS'),
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
