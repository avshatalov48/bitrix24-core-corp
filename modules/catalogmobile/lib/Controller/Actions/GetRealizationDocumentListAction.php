<?php

namespace Bitrix\CatalogMobile\Controller\Actions;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\CatalogMobile\RealizationList;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Crm\Order\Internals\ShipmentRealizationTable;
use Bitrix\Main\Entity\ReferenceField;

class GetRealizationDocumentListAction extends GetBaseDocumentListAction
{
	protected function checkModules(): bool
	{
		if (!parent::checkModules())
		{
			return false;
		}

		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('The Crm module is not installed'));

			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error('The Sale module is not installed'));

			return false;
		}

		return true;
	}

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
			'*',
			'ORDER_CURRENCY' => 'ORDER.CURRENCY',
			'RESPONSIBLE_BY_ID' => 'RESPONSIBLE_BY.ID',
			'RESPONSIBLE_BY_LOGIN' => 'RESPONSIBLE_BY.LOGIN',
			'RESPONSIBLE_BY_PERSONAL_PHOTO' => 'RESPONSIBLE_BY.PERSONAL_PHOTO',
			'RESPONSIBLE_BY_NAME' => 'RESPONSIBLE_BY.NAME',
			'RESPONSIBLE_BY_SECOND_NAME' => 'RESPONSIBLE_BY.SECOND_NAME',
			'RESPONSIBLE_BY_LAST_NAME' => 'RESPONSIBLE_BY.LAST_NAME',
		];

		$filter = [
			'=SHIPMENT_REALIZATION.IS_REALIZATION' => 'Y',
			'=SYSTEM' => 'N',
		];

		if (!empty($extra['search']))
		{
			$filter['=%ACCOUNT_NUMBER'] = '%' . $extra['search'] . '%';
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

		$documentList = ShipmentTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => ['DATE_UPDATE' => 'DESC'],
			'limit' => $pageNavigation->getLimit(),
			'offset' => $pageNavigation->getOffset(),
			'runtime' => [
				new ReferenceField(
					'SHIPMENT_REALIZATION',
					ShipmentRealizationTable::class,
					[
						'=this.ID' => 'ref.SHIPMENT_ID',
					],
					'left_join'
				),
			]
		])->fetchAll();

		$items = [];
		foreach ($documentList as $document)
		{
			$items[] = (new RealizationList\Item($document))->prepareItem();
		}

		return $items;
	}

	protected function getAccessEntityFilterName(): string
	{
		return ShipmentTable::class;
	}
}
