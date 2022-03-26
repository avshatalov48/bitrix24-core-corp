<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

use \Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

class CrmReportSalesDynamicsGridComponent extends \CBitrixComponent
{
	protected $gridId = "crm_report_compareperiods_grid";

	/** @var \Bitrix\Report\VisualConstructor\Entity\Widget */
	protected $widget;
	protected $dataProviderResult;

	protected $filterOptions;

	/** @var Grid\Options */
	protected $gridOptions;

	public function executeComponent()
	{
		$this->dataProviderResult = $this->arParams['RESULT'] ?? [];
		if(isset($this->dataProviderResult['errors']))
		{
			$this->printErrors($this->dataProviderResult['errors']);
			return false;
		}
		$this->widget = $this->arParams['WIDGET'];
		$this->filterOptions = $filterOptions = new Filter\Options($this->widget->getFilterId(), []);
		$this->gridOptions = new Grid\Options($this->gridId);

		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? \CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);

		$this->prepareResult();

		$this->includeComponentTemplate();
		return $this->arResult;
	}

	protected function prepareResult()
	{
		$this->arResult["GRID"] = [
			"ID" => $this->gridId,
			"COLUMNS" => $this->getColumns(),
			"ROWS" => $this->prepareRows(),
		];
		$this->arResult["GRID"]["TOTAL"] = $this->prepareTotal($this->arResult["GRID"]["ROWS"]);
		$this->arResult["WIDGET"] = [
			"ID" => $this->widget->getGId(),
		];
		$this->arResult["BOARD"] = [
			"ID" => $this->widget->getBoardId(),
		];
	}

	public function sortRows($data, $column, $direction)
	{
		$compareFunction = $this->getCompareFunc($column, $direction);

		if(is_callable($compareFunction))
		{
			usort($data, $compareFunction);
		}

		return $data;
	}

	protected function getCompareFunc($column, $direction)
	{
		switch ($column)
		{
			case "CURRENT_DATE":
				if($direction == "asc")
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["CURRENT_DATE"]["value"] instanceof Date ? $a["columns"]["CURRENT_DATE"]["value"]->getTimestamp() : null;
						$b = $b["columns"]["CURRENT_DATE"]["value"] instanceof Date ? $b["columns"]["CURRENT_DATE"]["value"]->getTimestamp() : null;

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $a - $b;
					};
				}
				else
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["CURRENT_DATE"]["value"] instanceof Date ? $a["columns"]["CURRENT_DATE"]["value"]->getTimestamp() : null;
						$b = $b["columns"]["CURRENT_DATE"]["value"] instanceof Date ? $b["columns"]["CURRENT_DATE"]["value"]->getTimestamp() : null;

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $b - $a;
					};
				}
				break;
			case "PREV_DATE":
				if($direction == "asc")
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["PREV_DATE"]["value"] instanceof Date ? $a["columns"]["PREV_DATE"]["value"]->getTimestamp() : null;
						$b = $b["columns"]["PREV_DATE"]["value"] instanceof Date ? $b["columns"]["PREV_DATE"]["value"]->getTimestamp() : null;

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $a - $b;
					};
				}
				else
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["PREV_DATE"]["value"] instanceof Date ? $a["columns"]["PREV_DATE"]["value"]->getTimestamp() : null;
						$b = $b["columns"]["PREV_DATE"]["value"] instanceof Date ? $b["columns"]["PREV_DATE"]["value"]->getTimestamp() : null;

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $b - $a;
					};
				}
				break;
			case "WON_CURRENT":
				if($direction == "asc")
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["WON_CURRENT"]["value"];
						$b = $b["columns"]["WON_CURRENT"]["value"];

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $a - $b;
					};
				}
				else
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["WON_CURRENT"]["value"];
						$b = $b["columns"]["WON_CURRENT"]["value"];

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $b - $a;
					};
				}
				break;
			case "WON_PREV":
				if($direction == "asc")
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["WON_PREV"]["value"];
						$b = $b["columns"]["WON_PREV"]["value"];

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $a - $b;
					};
				}
				else
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["WON_PREV"]["value"];
						$b = $b["columns"]["WON_PREV"]["value"];

						if($a === $b) return 0;
						if(is_null($a)) return 1;
						if(is_null($b)) return -1;
						return $b - $a;
					};
				}
				break;
			case "DYNAMICS":
				if($direction == "asc")
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["DYNAMICS"]["value"]["value"];
						$b = $b["columns"]["DYNAMICS"]["value"]["value"];

						if($a === $b) return 0;
						if($a === false) return 1;
						if($b === false) return -1;

						return $a - $b;
					};
				}
				else
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["DYNAMICS"]["value"]["value"];
						$b = $b["columns"]["DYNAMICS"]["value"]["value"];

						if($a === $b) return 0;
						if($a === false) return 1;
						if($b === false) return -1;

						return $b - $a;
					};
				}
				break;


		}

		return $result;

	}

	protected function prepareRows()
	{
		$rows = [];
		$rowId = 0;

		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();

		foreach ($this->dataProviderResult as $resultItem)
		{
			$value = $resultItem['value'];
			$targetUrl = $resultItem['targetUrl'];

			$dynamics = static::getPercentChange($value["amountCurrent"], $value["amountPrev"]);

			$row = [
				"id" => "row-" . $rowId++,
				"columns" => [
					"CURRENT_DATE" => [
						"value" => $value["dateCurrent"],
						"valueFormatted" => $value["dateCurrentFormatted"],
						"targetUrl" => $targetUrl["amountCurrent"],
					],
					"PREV_DATE" => [
						"value" => $value["datePrev"],
						"valueFormatted" => $value["datePrevFormatted"],
						"targetUrl" => $targetUrl["amountPrev"],
					],
					"WON_CURRENT" => [
						"value" => $value["amountCurrent"],
						"valueFormatted" => is_null($value["amountCurrent"]) ? "&mdash;" : CCrmCurrency::MoneyToString($value["amountCurrent"], $baseCurrency),
						"targetUrl" => $targetUrl["amountCurrent"],
					],
					"WON_PREV" => [
						"value" => $value["amountPrev"],
						"valueFormatted" => is_null($value["amountPrev"]) ? "&mdash;" : CCrmCurrency::MoneyToString($value["amountPrev"], $baseCurrency),
						"targetUrl" => $targetUrl["amountPrev"],
					],
					"DYNAMICS" => [
						"value" => static::getChangeLabel($dynamics),
					],
				],
				"actions" => []
			];

			$rows[] = $row;
		}

		$sorting = $this->gridOptions->getSorting(["sort" => ["CURRENT_DATE" => "ASC"]]);

		foreach ($sorting["sort"] as $k => $v)
		{
			$column = $k;
			$direction = $v;
		}

		return $this->sortRows($rows, $column, $direction);
	}

	protected function prepareTotal($gridRows)
	{
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$totalAmountCurrent = 0;
		$totalAmountPrev = 0;

		foreach ($gridRows as $row)
		{
			$columns = $row['columns'];

			$totalAmountCurrent += $columns['WON_CURRENT']['value'];
			$totalAmountPrev += $columns['WON_PREV']['value'];
		}

		$dynamics = static::getPercentChange($totalAmountCurrent, $totalAmountPrev);

		return [
			"id" => "row-total",
			"columns" => [
				"CURRENT_DATE" => [
					"value" => "",
					"valueFormatted" => ""
				],
				"PREV_DATE" => [
					"value" => "",
					"valueFormatted" => Loc::getMessage("CRM_REPORT_COMPAREPERIODS_ROW_TOTAL")
				],
				"WON_CURRENT" => [
					"value" => $totalAmountCurrent,
					"valueFormatted" => CCrmCurrency::MoneyToString($totalAmountCurrent, $baseCurrency),
				],
				"WON_PREV" => [
					"value" => $totalAmountPrev,
					"valueFormatted" => CCrmCurrency::MoneyToString($totalAmountPrev, $baseCurrency),
				],
				"DYNAMICS" => [
					"value" => static::getChangeLabel($dynamics),
				],
			],
			"actions" => []
		];
	}

	protected function getColumns()
	{
		$columns = [
			[
				"id" => "CURRENT_DATE",
				"sort" => "CURRENT_DATE",
				"name" => Loc::getMessage("CRM_REPORT_COMPAREPERIODS_COLUMN_CURRENT_DATE"),
				"default" => true,
			],
			[
				"id" => "PREV_DATE",
				"sort" => "PREV_DATE",
				"name" => Loc::getMessage("CRM_REPORT_COMPAREPERIODS_COLUMN_PREV_DATE"),
				"default" => true,
			],
			[
				"id" => "WON_CURRENT",
				"sort" => "WON_CURRENT",
				"name" => Loc::getMessage("CRM_REPORT_COMPAREPERIODS_COLUMN_WON_CURRENT"),
				"default" => true,
			],
			[
				"id" => "WON_PREV",
				"sort" => "WON_PREV",
				"name" => Loc::getMessage("CRM_REPORT_COMPAREPERIODS_COLUMN_WON_PREV"),
				"default" => true,
			],
			[
				"id" => "DYNAMICS",
				"sort" => "DYNAMICS",
				"name" => Loc::getMessage("CRM_REPORT_COMPAREPERIODS_COLUMN_DYNAMICS"),
				"default" => true,
			],
		];
		return $columns;
	}

	protected static function getPercentChange($currentValue, $previousValue)
	{
		if ($currentValue == 0)
		{
			return false;
		}
		if ($previousValue == 0)
		{
			return false;
		}
		if ($currentValue == $previousValue)
		{
			return 0;
		}
		$result = $currentValue / $previousValue - 1;
		return round($result * 100, 2);
	}

	protected static function getChangeLabel($changeInPercents)
	{
		$result = [
			'value' => $changeInPercents,
		];

		if ($changeInPercents === false)
		{
			return $result;
		}

		if ($changeInPercents < 0)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_COMPAREPERIODS_LABEL_BAD");
			$result['color'] = '#E22E29';
		}
		else if ($changeInPercents <= 10)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_COMPAREPERIODS_LABEL_NOT_BAD");
			$result['color'] = '#6F9300';
		}
		else if ($changeInPercents <= 30)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_COMPAREPERIODS_LABEL_GOOD");
			$result['color'] = '#1DB0DE';
		}
		else
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_COMPAREPERIODS_LABEL_EXCELLENT");
			$result['color'] = '#1DB0DE';
		}

		return $result;
	}

	protected function printErrors(array $errors)
	{
		foreach ($errors as $error)
		{
			ShowError($error);
		}
	}
}
