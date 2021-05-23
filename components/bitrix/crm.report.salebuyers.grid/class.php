<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;

CBitrixComponent::includeComponentClass('bitrix:crm.report.regularcustomers.grid');
class CrmReportSaleBuyersGridComponent extends CrmReportRegularCustomersGridComponent
{
	protected $gridId = 'crm_report_salebuyers_grid';

	protected function prepareRows()
	{
		$rows = [];
		$rowId = 0;

		$baseCurrency = CCrmCurrency::GetAccountCurrencyID();
		foreach ($this->dataProviderResult as $resultItem)
		{
			$targetUrl = $resultItem['targetUrl'];
			$successUrl = $resultItem['successUrl'];
			$profileUrl = $resultItem['profileUrl'];

			$row = [
				'id' => 'row-' . $rowId++,
				'columns' => [
					'BUYERS_TITLE' => [
						'value' => $resultItem['name'],
						'valueFormatted' => $resultItem['name'],
						'targetUrl' => $profileUrl,
					],
					'ORDER_COUNT' => [
						'value' => (int)$resultItem['totalCount'],
						'valueFormatted' => $resultItem['totalCount'],
						'targetUrl' => $targetUrl,
					],
					'ORDER_WON_COUNT' => [
						'value' => (int)$resultItem['successCount'],
						'valueFormatted' => $resultItem['successCount'],
						'targetUrl' => $successUrl,
					],
					'ORDER_WON_AMOUNT' => [
						'value' => (float)$resultItem['successSum'],
						'valueFormatted' => CCrmCurrency::MoneyToString($resultItem['successSum'], $baseCurrency),
						'targetUrl' => $successUrl,
					],
					'CONVERSION' => static::getConversion($resultItem['totalCount'], $resultItem['successCount']),
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
		$columns = [
			[
				'id' => 'BUYERS_TITLE',
				'sort' => 'BUYERS_TITLE',
				'name' => Loc::getMessage('CRM_REPORT_REGULAR_CUSTOMERS_GRID_BUYER_TITLE'),
				'default' => true,
			],
			[
				'id' => 'ORDER_COUNT',
				'sort' => 'ORDER_COUNT',
				'name' => Loc::getMessage('CRM_REPORT_REGULAR_CUSTOMERS_GRID_ORDER_COUNT'),
				'default' => true,
			],
			[
				'id' => 'ORDER_WON_COUNT',
				'sort' => 'DEAL_WON_COUNT',
				'name' => Loc::getMessage('CRM_REPORT_REGULAR_CUSTOMERS_GRID_ORDER_WON_COUNT'),
				'default' => true,
			],
			[
				'id' => 'ORDER_WON_AMOUNT',
				'sort' => 'ORDER_WON_AMOUNT',
				'name' => Loc::getMessage('CRM_REPORT_REGULAR_CUSTOMERS_GRID_ORDER_WON_AMOUNT'),
				'default' => true,
			],
			[
				'id' => 'CONVERSION',
				'sort' => 'CONVERSION',
				'name' => Loc::getMessage('CRM_REPORT_REGULAR_CUSTOMERS_GRID_CONVERSION'),
				'default' => true,
			],
		];
		return $columns;
	}
}
