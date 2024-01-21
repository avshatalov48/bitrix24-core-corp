<?php

namespace Bitrix\CatalogMobile\Controller\Actions;

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\CatalogMobile\StoreDocumentList;
use Bitrix\Sale\Internals\ShipmentTable;

class GetStoreDocumentListAction extends GetBaseDocumentListAction
{
	/**
	 * @inheritdoc
	 */
	protected function getListItems(
		array $documentTypes,
		PageNavigation $pageNavigation,
		array $extra = []
	): array
	{
		$select = [
			'ID',
			'TITLE',
			'DOC_TYPE',
			'TITLE',
			'TOTAL',
			'CURRENCY',
			'DATE_CREATE',
			'DATE_DOCUMENT',
			'STATUS',
			'WAS_CANCELLED',
			'DOC_NUMBER',
			'ITEMS_RECEIVED_DATE',
			'ITEMS_ORDER_DATE',
			'CONTRACTOR_ID',
			'CONTRACTOR_REF_' => 'CONTRACTOR',
			'RESPONSIBLE_ID',
			'RESPONSIBLE.ID',
			'RESPONSIBLE.LOGIN',
			'RESPONSIBLE.NAME',
			'RESPONSIBLE.LAST_NAME',
			'RESPONSIBLE.SECOND_NAME',
			'RESPONSIBLE.PERSONAL_PHOTO',
		];

		$filter = [
			'@DOC_TYPE' => $documentTypes,
		];
		if (!empty($extra['search']))
		{
			$filter['=%TITLE'] = '%' . $extra['search'] . '%';
		}
		$accessFilter = $this->getAccessFilter();
		if ($accessFilter)
		{
			$filter[] = $accessFilter;
		}

		if (isset($extra['filterParams']['ID']))
		{
			$filter['@ID'] = array_map(static fn($id) => (int) $id, $extra['filterParams']['ID']);
		}

		$documentList = StoreDocumentTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => ['DATE_MODIFY' => 'DESC'],
			'limit' => $pageNavigation->getLimit(),
			'offset' => $pageNavigation->getOffset(),
		])->fetchAll();

		$items = [];
		foreach ($documentList as $document)
		{
			$items[] = (new StoreDocumentList\Item($document))->prepareItem();
		}

		return $items;
	}

	protected function getAccessEntityFilterName(): string
	{
		return StoreDocumentTable::class;
	}
}
