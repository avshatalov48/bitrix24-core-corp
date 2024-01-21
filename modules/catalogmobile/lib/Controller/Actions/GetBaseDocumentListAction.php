<?php

namespace Bitrix\CatalogMobile\Controller\Actions;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Integration\PullManager;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\UI\StatefulList\BaseAction;
use CPullWatch;

abstract class GetBaseDocumentListAction extends BaseAction
{
	/** @var AccessController */
	protected $accessController;

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
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_DOCUMENTS_NOT_FOUND_MSGVER_2')));

			return $this->showErrors();
		}

		if (!$this->checkModules())
		{
			return $this->ShowErrors();
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

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('catalog'))
		{
			$this->addError(new Error('The Commercial Catalog module is not installed'));

			return false;
		}

		return true;
	}

	/**
	 * @param array $documentTypes
	 * @param PageNavigation $pageNavigation
	 * @param array $extra
	 * @return array
	 */
	abstract protected function getListItems(
		array $documentTypes,
		PageNavigation $pageNavigation,
		array $extra = []
	): array;

	/**
	 * @param array $documentTypes
	 * @return bool
	 */
	protected function hasReadAccess(array $documentTypes): bool
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

	/**
	 * @return array
	 */
	protected function getAccessFilter(): array
	{
		$result = [];

		$docTypeFilter = $this->accessController->getEntityFilter(
			ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
			$this->getAccessEntityFilterName()
		);
		if ($docTypeFilter)
		{
			$result[] = $docTypeFilter;
		}

		$storeFilter = $this->accessController->getEntityFilter(
			ActionDictionary::ACTION_STORE_VIEW,
			$this->getAccessEntityFilterName()
		);
		if ($storeFilter)
		{
			$result[] = $storeFilter;
		}

		return $result;
	}

	abstract protected function getAccessEntityFilterName(): string;
}
