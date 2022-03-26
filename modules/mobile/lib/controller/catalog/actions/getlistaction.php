<?php

namespace Bitrix\Mobile\Controller\Catalog\Actions;

use Bitrix\Catalog\Integration\PullManager;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\EO_StoreDocument;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Integration\Catalog\StoreDocumentList\Item;
use Bitrix\Mobile\UI\StatefulList\BaseAction;
use CPullWatch;

class GetListAction extends BaseAction
{
	protected static $entities = [];

	public function run(
		array $documentTypes,
		PageNavigation $pageNavigation,
		array $extra = []
	)
	{
		if (empty($documentTypes))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_DOCUMENTS_NOT_FOUND')));

			return null;
		}

		if (!$this->getCurrentUser()->CanDoOperation('catalog_read'))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_READ_PERMISSIONS')));

			return null;
		}

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
			'CONTRACTOR.PERSON_TYPE',
			'CONTRACTOR.PERSON_NAME',
			'CONTRACTOR.ID',
			'CONTRACTOR.COMPANY',
			'RESPONSIBLE_ID',
			'RESPONSIBLE.ID',
			'RESPONSIBLE.LOGIN',
			'RESPONSIBLE.NAME',
			'RESPONSIBLE.LAST_NAME',
			'RESPONSIBLE.SECOND_NAME',
			'RESPONSIBLE.PERSONAL_PHOTO',
		];

		$filter = [
			'DOC_TYPE' => $documentTypes,
		];
		if (!empty($extra['search']))
		{
			$filter['=%TITLE'] = '%' . $extra['search'] . '%';
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
			$items[] = $this->prepareItem($document);
		}

		$result =  [
			'items' => $items,
		];

		if ($pageNavigation->getOffset() === 0)
		{
			$result['permissions'] = $this->getUserPermissions();

			if (empty($extra['subscribeUser']) || $extra['subscribeUser'] === 'true')
			{
				$result['isSubscribed'] = $this->subscribeUserToPull();
			}
		}

		return $result;
	}

	protected function prepareItem(array $document): array
	{
		$item = new Item($document);
		return $item->prepareItem();
	}

	/**
	 * @return array
	 */
	protected function getUserPermissions(): array
	{
		$user = $this->getCurrentUser();

		// perhaps in the future there will be a more flexible setting of rights
		return [
			'write' => $user->canDoOperation('catalog_store'),
			'read' => $user->canDoOperation('catalog_read'),
		];
	}

	/**
	 * @return bool
	 */
	protected function subscribeUserToPull(): bool
	{
		$userId = $this->getCurrentUser()->getId();
		if ($userId > 0 && Loader::requireModule('pull'))
		{
			return CPullWatch::Add($userId, PullManager::EVENT_DOCUMENTS_LIST_UPDATED);
		}
		return false;
	}
}
