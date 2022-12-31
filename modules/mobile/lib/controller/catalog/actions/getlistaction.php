<?php

namespace Bitrix\Mobile\Controller\Catalog\Actions;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Integration\PullManager;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Integration\Catalog\StoreDocumentList\Item;
use Bitrix\Mobile\InventoryControl\Dto\DocumentListItem;
use Bitrix\Mobile\UI\StatefulList\BaseAction;
use CPullWatch;

class GetListAction extends BaseAction
{
	protected const NOT_CONDUCTED = 'N';
	protected const CONDUCTED = 'Y';
	protected const CANCELLED = 'C';

	protected const SUM_FIELD_ID = 'TOTAL_WITH_CURRENCY';
	protected const CONTRACTOR_FIELD_ID = 'CONTRACTOR_ID';
	protected const STATUS_FIELD_ID = 'DOC_STATUS';

	protected static $entities = [];

	/** @var AccessController */
	private $accessController;

	public function __construct($name, Controller $controller, $config = [])
	{
		parent::__construct($name, $controller, $config);

		$this->accessController = AccessController::getInstance($this->getCurrentUser()->getId());
	}

	public function run(
		array $documentTypes,
		PageNavigation $pageNavigation,
		array $extra = []
	)
	{
		if (empty($documentTypes))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_DOCUMENTS_NOT_FOUND')));

			return $this->showErrors();
		}

		if (!Loader::includeModule('catalog'))
		{
			$this->addError(new Error('The Commercial Catalog module is not installed'));

			return $this->showErrors();
		}

		$hasReadAccess = $this->hasReadAccess($documentTypes);
		$result =  [
			'items' => $hasReadAccess
				? $this->getListItems($documentTypes, $pageNavigation, $extra)
				: [],
		];

		if ($pageNavigation->getOffset() === 0)
		{
			$result['permissions'] = [
				'read' => $hasReadAccess,
			];

			if (empty($extra['subscribeUser']) || $extra['subscribeUser'] === 'true')
			{
				$result['isSubscribed'] = $this->subscribeUserToPull();
			}
		}

		return $result;
	}

	/**
	 * @param array $documentTypes
	 * @param PageNavigation $pageNavigation
	 * @return array
	 */
	private function getListItems(
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
			'DOC_TYPE' => $documentTypes,
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
			$items[] = $this->prepareItem($document);
		}

		return $items;
	}

	/**
	 * @param array $documentTypes
	 * @return bool
	 */
	private function hasReadAccess(array $documentTypes): bool
	{
		if (
			!$this->accessController->check(ActionDictionary::ACTION_CATALOG_READ)
			|| !$this->accessController->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
		)
		{
			return false;
		}

		foreach ($documentTypes as $documentType)
		{
			if (
				$this->accessController->checkByValue(
					ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
					$documentType
				)
			)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	private function getAccessFilter(): array
	{
		$result = [];

		$docTypeFilter = $this->accessController->getEntityFilter(
			ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
			StoreDocumentTable::class
		);
		if ($docTypeFilter)
		{
			$result[] = $docTypeFilter;
		}

		$storeFilter = $this->accessController->getEntityFilter(
			ActionDictionary::ACTION_STORE_VIEW,
			StoreDocumentTable::class
		);
		if ($storeFilter)
		{
			$result[] = $storeFilter;
		}

		return $result;
	}

	protected function prepareItem(array $document): DocumentListItem
	{
		$item = new Item($document);
		return $item->prepareItem();
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
