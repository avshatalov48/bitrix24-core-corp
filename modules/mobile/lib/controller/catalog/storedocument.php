<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Access;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Integration\Catalog\EntityEditor\StoreDocumentProvider;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Mobile\InventoryControl\Command\ConductDocumentCommand;
use CCatalogDocs;

Loader::requireModule('catalog');

class StoreDocument extends \Bitrix\Main\Engine\Controller
{
	use ReadsApplicationErrors;

	public function conductAction(int $id, CurrentUser $currentUser): ?array
	{
		if (!$this->checkDocumentAccess(ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT, $id, $currentUser))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_CONDUCT_PERMS')));

			return null;
		}

		$command = new ConductDocumentCommand($id, (int)$currentUser->getId());
		$result = $command();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		$statuses = $this->getStatusesList();

		return [
			'result' => $result->isSuccess(),
			'item' => [
				'statuses' => [
					CCatalogDocs::CONDUCTED,
				],
				'fields' => [
					[
						'name' => 'DOC_STATUS',
						'value' => [
							$statuses[CCatalogDocs::CONDUCTED],
						]
					],
				],
			],
		];
	}

	public function cancellationAction(int $id, CurrentUser $currentUser): ?array
	{
		if (!$this->checkDocumentAccess(ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT, $id, $currentUser))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_CANCELLATION_PERMS')));

			return null;
		}

		$result = CCatalogDocs::cancellationDocument($id, $currentUser->getId());
		if (!$result)
		{
			$this->addError(
				$this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_CANCELLATION'))
			);

			return null;
		}

		$statuses = $this->getStatusesList();

		return [
			'result' => $result,
			'item' => [
				'statuses' => [
					CCatalogDocs::CANCELLED,
				],
				'fields' => [
					[
						'name' => 'DOC_STATUS',
						'value' => [
							$statuses[CCatalogDocs::CANCELLED],
						]
					],
				],
			],
		];
	}

	private function getStatusesList(): array
	{
		$provider = StoreDocumentProvider::createByArray([]);
		return $provider->getStatusesList();
	}

	public function deleteAction(int $id, CurrentUser $currentUser): ?array
	{
		if (!$this->checkDocumentAccess(ActionDictionary::ACTION_STORE_DOCUMENT_DELETE, $id, $currentUser))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_DELETE_PERMS')));

			return null;
		}

		$result = CCatalogDocs::delete($id);
		if (!$result)
		{
			$this->addError(
				$this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_ERROR_DELETE'))
			);

			return null;
		}

		return [
			'result' => $result,
		];
	}

	/**
	 * @param string $action
	 * @param int $documentId
	 * @param CurrentUser $currentUser
	 * @return bool
	 */
	private function checkDocumentAccess(string $action, int $documentId, CurrentUser $currentUser): bool
	{
		return AccessController::getInstance($currentUser->getId())->check(
			$action,
			Access\Model\StoreDocument::createFromId($documentId)
		);
	}
}
