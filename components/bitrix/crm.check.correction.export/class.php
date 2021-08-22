<?php

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Helpers\Admin\Correction;
use Bitrix\Sale\PriceMaths;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CorrectionCheckExport extends CBitrixComponent
{
	private $gridOptions;

	public function executeComponent()
	{
		$this->gridOptions = new \Bitrix\Main\Grid\Options(Correction::TABLE_ID);
		$this->getHeaders();
		$this->getEntries();
		$this->includeComponentTemplate($this->arParams['EXPORT_TYPE']);

		return [
			'PROCESSED_ITEMS' => count($this->arResult['ENTRIES']),
			'TOTAL_ITEMS' => $this->arResult['TOTAL_ENTRIES'],
		];
	}

	private function getHeaders()
	{
		// re-index the array with 'id' as the key
		$tableColumns = array_column(Correction::getTableHeaders(), null, 'id');
		$visibleColumns = $this->gridOptions->GetVisibleColumns();
		if (empty($visibleColumns))
		{
			$this->arResult['HEADERS'] = array_filter($tableColumns, function ($column) {
				return $column['default'];
			});
		}
		else
		{
			$this->arResult['HEADERS'] = array_intersect_key($tableColumns, array_flip($visibleColumns));
		}
	}

	private function getEntries()
	{
		$filter = Correction::getFilterValues();
		$filter = Correction::prepareFilter($filter);

		$queryParams = Correction::getPaymentSelectParams($filter);

		$pageSize = $this->arParams['STEXPORT_PAGE_SIZE'];
		$queryParams['limit'] = $pageSize;
		$currentPage = $this->arParams['PAGE_NUMBER'];
		$offset = ($currentPage - 1) * $pageSize;
		$queryParams['offset'] = $offset;
		$queryParams['count_total'] = true;

		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
		$paymentClass = $registry->getPaymentClassName();

		$gridSort = $this->gridOptions->getSorting(['sort' => ['ID' => 'ASC']]);
		$queryParams['order'] = $gridSort['sort'];

		$queryResult = $paymentClass::getList($queryParams);
		$total = $queryResult->getCount();
		$entries = [];
		while ($entry = $queryResult->fetch())
		{
			$entry['SUM'] = PriceMaths::roundPrecision($entry['SUM']);
			$entries[] = $entry;
		}
		$this->arResult['ENTRIES'] = $entries;
		$this->arResult['TOTAL_ENTRIES'] = $total;
		$this->arResult['IS_FIRST_PAGE'] = $currentPage === 1 ? 'Y' : 'N';
		$this->arResult['IS_LAST_PAGE'] = $offset + count($entries) === $total ? 'Y' : 'N';
	}
}