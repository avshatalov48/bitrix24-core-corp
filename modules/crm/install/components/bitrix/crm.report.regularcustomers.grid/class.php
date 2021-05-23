<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

class CrmReportRegularCustomersGridComponent extends \CBitrixComponent
{
	protected $gridId = "crm_report_regularcustomers_grid";

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
		$compareFunction = $this->getCompareFunc($column, $direction);

		if(is_callable($compareFunction))
		{
			usort($data, $compareFunction);
		}

		return $data;
	}

	protected function getCompareFunc($column, $direction)
	{
		return function($a, $b) use ($column, $direction)
		{
			if($direction === "asc")
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
			$clientFields = $resultItem['clientFields'];
			$value = $resultItem['value'];
			$targetUrl = $resultItem['targetUrl'];

			$row = [
				"id" => "row-" . $rowId++,
				"columns" => [
					"CLIENT_TITLE" => [
						"value" => $clientFields['TITLE'],
						"valueFormatted" => $clientFields['TITLE'],
						"targetUrl" => $clientFields["SHOW_URL"],
					],
					"DEAL_COUNT" => [
						"value" => (int)$value["totalDealCount"],
						"valueFormatted" => $value["totalDealCount"],
						"targetUrl" => $targetUrl["totalDealCount"],
					],
					"DEAL_WON_COUNT" => [
						"value" => (int)$value["successDealCount"],
						"valueFormatted" => $value["successDealCount"],
						"targetUrl" => $targetUrl["successDealCount"],
					],
					"DEAL_WON_AMOUNT" => [
						"value" => (float)$value["successDealAmount"],
						"valueFormatted" => CCrmCurrency::MoneyToString($value["successDealAmount"], $baseCurrency),
						"targetUrl" => $targetUrl["successDealAmount"],
					],
					"CONVERSION" => static::getConversion($value["totalDealCount"], $value["successDealCount"]),
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
				"id" => "CLIENT_TITLE",
				"sort" => "CLIENT_TITLE",
				"name" => Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_CLIENT_TITLE"),
				"default" => true,
			],
			[
				"id" => "DEAL_COUNT",
				"sort" => "DEAL_COUNT",
				"name" => Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_DEAL_CLOSED_COUNT"),
				"default" => true,
			],
			[
				"id" => "DEAL_WON_COUNT",
				"sort" => "DEAL_WON_COUNT",
				"name" => Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_CLIENT_TITLECRM_REPORT_REGULAR_CUSTOMERS_GRID_DEAL_WON_COUNT"),
				"default" => true,
			],
			[
				"id" => "DEAL_WON_AMOUNT",
				"sort" => "DEAL_WON_AMOUNT",
				"name" => Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_DEAL_WON_AMOUNT"),
				"default" => true,
			],
			[
				"id" => "CONVERSION",
				"sort" => "CONVERSION",
				"name" => Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_CONVERSION"),
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
			$result['label'] = Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_BAD");
			$result['color'] = '#E22E29';
		}
		else if ($conversion <= 20)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_NOT_BAD");
			$result['color'] = '#6F9300';
		}
		else if ($conversion <= 30)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_GOOD");
			$result['color'] = '#1DB0DE';
		}
		else
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_REGULAR_CUSTOMERS_GRID_EXCELLENT");
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
