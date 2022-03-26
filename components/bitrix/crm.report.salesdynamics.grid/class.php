<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;

use \Bitrix\Crm\Integration\Report\Handler\SalesDynamics;

class CrmReportSalesDynamicsGridComponent extends \CBitrixComponent
{
	protected $gridId = "crm_report_salesdynamics_grid";

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
		$this->arResult["TARGET_URL"] = $this->prepareTargetUrl();
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

	public static function roundForSort($value)
	{
		if ($value < 0)
		{
			return -1;
		}
		else if ($value > 0 )
		{
			return 1;
		}
		return 0;
	}

	protected function getCompareFunc($column, $direction)
	{
		switch ($column)
		{
			case "EMPLOYEE":
				if($direction == "asc")
				{
					$result = function($a, $b) { return strcmp($a["columns"]["EMPLOYEE"]["NAME"], $b["columns"]["EMPLOYEE"]["NAME"]); };
				}
				else
				{
					$result = function($a, $b) { return -strcmp($a["columns"]["EMPLOYEE"]["NAME"], $b["columns"]["EMPLOYEE"]["NAME"]); };
				}
				break;
			case "WON_AMOUNT":
				if($direction == "asc")
				{
					$result = function($a, $b) { return static::roundForSort($a["columns"]["WON_AMOUNT"]["TOTAL_VALUE"] - $b["columns"]["WON_AMOUNT"]["TOTAL_VALUE"]); };
				}
				else
				{
					$result = function($a, $b) { return static::roundForSort($b["columns"]["WON_AMOUNT"]["TOTAL_VALUE"] - $a["columns"]["WON_AMOUNT"]["TOTAL_VALUE"]); };
				}
				break;
			case "LOST_AMOUNT":
				if($direction == "asc")
				{
					$result = function($a, $b) { return static::roundForSort($a["columns"]["LOST_AMOUNT"]["TOTAL_VALUE"] - $b["columns"]["LOST_AMOUNT"]["TOTAL_VALUE"]); };
				}
				else
				{
					$result = function($a, $b) { return static::roundForSort($b["columns"]["LOST_AMOUNT"]["TOTAL_VALUE"] - $a["columns"]["LOST_AMOUNT"]["TOTAL_VALUE"]); };
				}
				break;

			case "REVENUE_DYNAMICS":
				if($direction == "asc")
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["REVENUE_DYNAMICS"]["TOTAL"]["value"];
						$b = $b["columns"]["REVENUE_DYNAMICS"]["TOTAL"]["value"];

						if($a === $b) return 0;
						if($a === false) return 1;
						if($b === false) return -1;

						return static::roundForSort($a - $b);
					};
				}
				else
				{
					$result = function($a, $b)
					{
						$a = $a["columns"]["REVENUE_DYNAMICS"]["TOTAL"]["value"];
						$b = $b["columns"]["REVENUE_DYNAMICS"]["TOTAL"]["value"];

						if($a === $b) return 0;
						if($a === false) return 1;
						if($b === false) return -1;

						return static::roundForSort($b - $a);
					};
				}
				break;
			case "CONVERSION":
				if($direction == "asc")
				{
					$result = function($a, $b) { return static::roundForSort($a["columns"]["CONVERSION"]["TOTAL"] - $b["columns"]["CONVERSION"]["TOTAL"]); };
				}
				else
				{
					$result = function($a, $b) { return static::roundForSort($b["columns"]["CONVERSION"]["TOTAL"] - $a["columns"]["CONVERSION"]["TOTAL"]); };
				}
				break;
			case "LOSSES":
				if($direction == "asc")
				{
					$result = function($a, $b) { return static::roundForSort($a["columns"]["LOSSES"]["TOTAL"] - $b["columns"]["LOSSES"]["TOTAL"]); };
				}
				else
				{
					$result = function($a, $b) { return static::roundForSort($b["columns"]["LOSSES"]["TOTAL"] - $a["columns"]["LOSSES"]["TOTAL"]); };
				}
				break;
		}

		return $result;

	}

	protected function prepareRows()
	{
		$rows = [];
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();

		foreach ($this->dataProviderResult as $resultItem)
		{
			$value = $resultItem['value'];
			$userId = $value["USER_ID"];

			//region: conversion
			$primaryWon = $value[SalesDynamics\Conversion::COUNT_PRIMARY_WON];
			$primaryLost = $value[SalesDynamics\Conversion::COUNT_PRIMARY_LOST];
			$primaryInWork = $value[SalesDynamics\Conversion::COUNT_PRIMARY_IN_WORK];
			$returnWon = $value[SalesDynamics\Conversion::COUNT_RETURN_WON];
			$returnLost = $value[SalesDynamics\Conversion::COUNT_RETURN_LOST];
			$returnInWork = $value[SalesDynamics\Conversion::COUNT_RETURN_IN_WORK];

			$totalWon = $primaryWon + $returnWon;
			$totalLost = $primaryLost + $returnLost;
			$totalInWork = $primaryInWork + $returnInWork;

			$conversionPrimary = $primaryInWork > 0 ? $primaryWon / $primaryInWork : 0;
			$lossesPrimary = $primaryInWork > 0 ? $primaryLost / $primaryInWork : 0;
			$conversionReturn = $returnInWork > 0 ? $returnWon / $returnInWork : 0;
			$lossesReturn = $returnInWork > 0 ? $returnLost / $returnInWork : 0;

			$conversionTotal = $totalInWork > 0 ? $totalWon / $totalInWork : 0;
			$lossesTotal = $totalInWork > 0 ? $totalLost / $totalInWork : 0;
			//endregion

			//region: revenue dynamics
			$revenueChangePrimary = static::getPercentChange($value[SalesDynamics\WonLostAmount::PRIMARY_WON],$value[SalesDynamics\WonLostPrevious::PRIMARY_WON]);
			$revenueChangeReturn = static::getPercentChange($value[SalesDynamics\WonLostAmount::RETURN_WON], $value[SalesDynamics\WonLostPrevious::RETURN_WON]);
			$revenueChangeTotal = static::getPercentChange($value[SalesDynamics\WonLostAmount::TOTAL_WON], $value[SalesDynamics\WonLostPrevious::TOTAL_WON]);
			//endregion

			$row = [
				"id" => "user-" . $userId,
				"columns" => [
					"EMPLOYEE" =>  $this->getUserInfo($userId),
					"WON_AMOUNT" => [
						"PRIMARY_VALUE" => $value[SalesDynamics\WonLostAmount::PRIMARY_WON],
						"PRIMARY_FORMATTED" => CCrmCurrency::MoneyToString($value[SalesDynamics\WonLostAmount::PRIMARY_WON], $baseCurrency),
						"RETURN_VALUE" => $value[SalesDynamics\WonLostAmount::RETURN_WON],
						"RETURN_FORMATTED" => CCrmCurrency::MoneyToString($value[SalesDynamics\WonLostAmount::RETURN_WON], $baseCurrency),
						"TOTAL_VALUE" => $value[SalesDynamics\WonLostAmount::TOTAL_WON],
						"TOTAL_FORMATTED" => CCrmCurrency::MoneyToString($value[SalesDynamics\WonLostAmount::TOTAL_WON], $baseCurrency),
					],
					"LOST_AMOUNT" => [
						"PRIMARY_VALUE" => $value[SalesDynamics\WonLostAmount::PRIMARY_LOST],
						"PRIMARY_FORMATTED" => CCrmCurrency::MoneyToString($value[SalesDynamics\WonLostAmount::PRIMARY_LOST], $baseCurrency),
						"RETURN_VALUE" => $value[SalesDynamics\WonLostAmount::RETURN_LOST],
						"RETURN_FORMATTED" => CCrmCurrency::MoneyToString($value[SalesDynamics\WonLostAmount::RETURN_LOST], $baseCurrency),
						"TOTAL_VALUE" => $value[SalesDynamics\WonLostAmount::TOTAL_LOST],
						"TOTAL_FORMATTED" => CCrmCurrency::MoneyToString($value[SalesDynamics\WonLostAmount::TOTAL_LOST], $baseCurrency),
					],
					"REVENUE_DYNAMICS" => [
						"PRIMARY" => static::getChangeLabel($revenueChangePrimary),
						"RETURN" => static::getChangeLabel($revenueChangeReturn),
						"TOTAL" => static::getChangeLabel($revenueChangeTotal)
					],
					"CONVERSION" => [
						"PRIMARY" => round($conversionPrimary * 100, 2),
						"RETURN" => round($conversionReturn * 100, 2),
						"TOTAL" => round($conversionTotal * 100, 2),
					],
					"LOSSES" => [
						"PRIMARY" => round($lossesPrimary * 100, 2),
						"RETURN" => round($lossesReturn * 100, 2),
						"TOTAL" => round($lossesTotal * 100, 2),
					],
				],
				"actions" => []
			];

			$rows[] = $row;
		}

		$sorting = $this->gridOptions->getSorting(["sort" => ["WON_AMOUNT" => "DESC"]]);

		foreach ($sorting["sort"] as $k => $v)
		{
			$column = $k;
			$direction = $v;
		}

		return $this->sortRows($rows, $column, $direction);
	}

	protected function prepareTargetUrl()
	{
		$result = [];
		foreach ($this->dataProviderResult as $userId => $resultItem)
		{
			$result[$userId] = $resultItem['targetUrl'];
		}

		return $result;
	}

	protected function getColumns()
	{
		$columns = [
			[
				"id" => "EMPLOYEE",
				"sort" => "EMPLOYEE",
				"name" => Loc::getMessage("CRM_REPORT_SALESDYNAMICS_COLUMN_EMPLOYEE"),
				"default" => true,
			],
			[
				"id" => "WON_AMOUNT",
				"sort" => "WON_AMOUNT",
				"name" => Loc::getMessage("CRM_REPORT_SALESDYNAMICS_COLUMN_WON_AMOUNT"),
				"default" => true,
			],
			[
				"id" => "CONVERSION",
				"sort" => "CONVERSION",
				"name" => Loc::getMessage("CRM_REPORT_SALESDYNAMICS_COLUMN_CONVERSION"),
				"default" => true,
			],
			[
				"id" => "LOST_AMOUNT",
				"sort" => "LOST_AMOUNT",
				"name" => Loc::getMessage("CRM_REPORT_SALESDYNAMICS_COLUMN_LOST_AMOUNT"),
				"default" => true,
			],
			[
				"id" => "LOSSES",
				"sort" => "LOSSES",
				"name" => Loc::getMessage("CRM_REPORT_SALESDYNAMICS_COLUMN_LOSSES"),
				"default" => true,
			],
			[
				"id" => "REVENUE_DYNAMICS",
				"sort" => "REVENUE_DYNAMICS",
				"name" => Loc::getMessage("CRM_REPORT_SALESDYNAMICS_COLUMN_REVENUE_DYNAMICS"),
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
			$result['label'] = Loc::getMessage("CRM_REPORT_SALESDYNAMICS_LABEL_BAD");
			$result['color'] = '#E22E29';
		}
		else if ($changeInPercents <= 10)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_SALESDYNAMICS_LABEL_NOT_BAD");
			$result['color'] = '#6F9300';
		}
		else if ($changeInPercents <= 30)
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_SALESDYNAMICS_LABEL_GOOD");
			$result['color'] = '#1DB0DE';
		}
		else
		{
			$result['label'] = Loc::getMessage("CRM_REPORT_SALESDYNAMICS_LABEL_EXCELLENT");
			$result['color'] = '#1DB0DE';
		}

		return $result;
	}

	protected function getUserInfo($userId)
	{
		static $users = array();

		if(!$userId)
		{
			return null;
		}

		if(!$users[$userId])
		{
			// prepare link to profile
			$replaceList = array('user_id' => $userId, 'id' => $userId);
			$link = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER_PROFILE'], $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if(!$userFields)
			{
				return null;
			}

			// format name
			$userName = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => $userFields['LOGIN'],
					'NAME' => $userFields['NAME'],
					'LAST_NAME' => $userFields['LAST_NAME'],
					'SECOND_NAME' => $userFields['SECOND_NAME']
				),
				true, false
			);

			// prepare icon
			$fileTmp = CFile::ResizeImageGet(
				$userFields['PERSONAL_PHOTO'],
				array('width' => 42, 'height' => 42),
				BX_RESIZE_IMAGE_EXACT,
				false
			);
			$userIcon = $fileTmp['src'];

			$users[$userId] = array(
				'ID' => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => $userIcon
			);
		}

		return $users[$userId];
	}

	protected function printErrors(array $errors)
	{
		foreach ($errors as $error)
		{
			ShowError($error);
		}
	}

	public function getConversionReport()
	{

	}
}
