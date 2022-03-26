<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

class CrmReportManagersRatingGridComponent extends \CBitrixComponent
{
	protected $gridId = "crm_report_managers_rating_grid";

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
		$this->arResult["GRID"] = [
			"ID" => $this->gridId,
			"COLUMNS" => $this->getColumns(),
			"ROWS" => $this->prepareRows(),
		];
		$this->arResult["WIDGET"] = [
			"ID" => $this->widget->getGId(),
		];
		$this->arResult["BOARD"] = [
			"ID" => $this->widget->getBoardId(),
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
			if ($direction === "asc")
			{
				return $a["columns"][$column]["value"] <=> $b["columns"][$column]["value"];
			}
			else
			{
				return $b["columns"][$column]["value"] <=> $a["columns"][$column]["value"];
			}
		};
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

			$row = [
				"id" => "row-".$rowId++,
				"columns" => [
					"USER" => [
						"value" => $value["userFields"]["name"],
						"valueFormatted" => $value["userFields"]["name"],
						"icon" => $value["userFields"]["icon"],
						"targetUrl" => $targetUrl["userId"],
					],
					"DEAL_TOTAL_AMOUNT" => [
						"value" => (int)$value["totalDealAmount"],
						"valueFormatted" => CCrmCurrency::MoneyToString($value["totalDealAmount"], $baseCurrency),
						"targetUrl" => $targetUrl["totalDealAmount"],
						"delta" => CCrmCurrency::MoneyToString((float)$value["totalDealAmount"] - (float)$value["totalDealAmountPrev"], $baseCurrency),
					],
					"DEAL_WON_AMOUNT" => [
						"value" => (float)$value["successDealAmount"],
						"valueFormatted" => CCrmCurrency::MoneyToString($value["successDealAmount"], $baseCurrency),
						"targetUrl" => $targetUrl["successDealAmount"],
						"delta" => CCrmCurrency::MoneyToString((float)$value["successDealAmount"] - (float)$value["successDealAmountPrev"], $baseCurrency),
					],
					"DEAL_WON_AVERAGE" => [
						"value" => (float)$value["averageSuccessDealAmount"],
						"valueFormatted" => CCrmCurrency::MoneyToString($value["averageSuccessDealAmount"], $baseCurrency),
						"delta" => CCrmCurrency::MoneyToString((float)$value["averageSuccessDealAmount"] - (float)$value["averageSuccessDealAmountPrev"], $baseCurrency),
					],
					"CONVERSION" => static::getConversion($value["totalDealCount"] ?? 0, $value["successDealCount"] ?? 0),
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

	protected function getColumns()
	{
		$columns = [
			[
				"id" => "USER",
				"sort" => "USER",
				"name" => Loc::getMessage("CRM_REPORT_MANAGERS_RATING_COLUMN_USER"),
				"default" => true,
			],
			[
				"id" => "DEAL_TOTAL_AMOUNT",
				"sort" => "DEAL_TOTAL_AMOUNT",
				"name" => Loc::getMessage("CRM_REPORT_MANAGERS_RATING_COLUMN_TOTAL_AMOUNT"),
				"default" => true,
			],
			[
				"id" => "DEAL_WON_AMOUNT",
				"sort" => "DEAL_WON_AMOUNT",
				"name" => Loc::getMessage("CRM_REPORT_MANAGERS_RATING_COLUMN_WON_AMOUNT"),
				"default" => true,
			],
			[
				"id" => "DEAL_WON_AVERAGE",
				"sort" => "DEAL_WON_AVERAGE",
				"name" => Loc::getMessage("CRM_REPORT_MANAGERS_RATING_COLUMN_WON_AVERAGE_AMOUNT"),
				"default" => true,
			],
			[
				"id" => "CONVERSION",
				"sort" => "CONVERSION",
				"name" => Loc::getMessage("CRM_REPORT_MANAGERS_RATING_COLUMN_CONVERSION"),
				"default" => true,
			],
		];
		return $columns;
	}

	protected static function getConversion(int $totalDeals, int $wonDeals)
	{
		$conversion = $totalDeals ? ($wonDeals / $totalDeals) : 0;
		$conversion = round($conversion * 100, 2);

		$result = [
			'value' => $conversion,
		];

		if ($conversion < 10)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_MANAGERS_RATING_GRID_BAD");
			$result['color'] = '#E22E29';
		}
		else if ($conversion <= 20)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_MANAGERS_RATING_GRID_NOT_BAD");
			$result['color'] = '#6F9300';
		}
		else if ($conversion <= 30)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_MANAGERS_RATING_GRID_GOOD");
			$result['color'] = '#1DB0DE';
		}
		else
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_MANAGERS_RATING_GRID_EXCELLENT");
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
