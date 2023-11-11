<?php

namespace Bitrix\CatalogMobile\Controller;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Access;
use Bitrix\Crm\Controller\RealizationDocument;
use Bitrix\Main\Localization\Loc;
use Bitrix\CatalogMobile\EntityEditor\StoreDocumentProvider;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\CatalogMobile\InventoryControl\Command\ConductDocumentCommand;
use CCatalogDocs;
use Bitrix\Main\Engine\Controller;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Catalog\Integration\PullManager;

Loader::requireModule('catalog');

class StoreDocument extends Controller
{
	use ReadsApplicationErrors;

	public function conductAction(int $id, string $docType, CurrentUser $currentUser): ?array
	{
		if ($docType === StoreDocumentTable::TYPE_SALES_ORDERS)
		{
			return $this->setShipped($id, 'Y');
		}

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

	private function setShipped(int $id, string $shipped): ?array
	{
		if (!Loader::requireModule('crm'))
		{
			$this->addError(new Error('Module crm is not installed'));

			return null;
		}

		$this->forward(
			RealizationDocument::class,
			'setShipped',
			[
				'id' => $id,
				'value' => $shipped,
			]
		);

		if (!empty($this->getErrors()))
		{
			return null;
		}

		$statuses = $this->getStatusesList();
		$status = $shipped === 'Y' ? CCatalogDocs::CONDUCTED : CCatalogDocs::CANCELLED;

		$shipment = ShipmentRepository::getInstance()->getById($id);
		if (!$shipment)
		{
			return null;
		}

		$fields = $shipment->getFields()->getValues();
		$fields['DOC_TYPE'] = 'W';
		PullManager::getInstance()->sendDocumentsUpdatedEvent([
			[
				'id' => $shipment->getId(),
				'data' => [
					'fields' => $fields,
				],
			],
		]);

		return [
			'result' => true,
			'item' => [
				'statuses' => [
					$status,
				],
				'fields' => [
					[
						'name' => 'DOC_STATUS',
						'value' => [
							$statuses[$status],
						]
					],
				],
			],
		];
	}

	public function cancellationAction(int $id, string $docType, CurrentUser $currentUser): ?array
	{
		if ($docType === StoreDocumentTable::TYPE_SALES_ORDERS)
		{
			return $this->setShipped($id, 'N');
		}

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

	public function deleteAction(int $id, string $docType, CurrentUser $currentUser): ?array
	{
		if ($docType === StoreDocumentTable::TYPE_SALES_ORDERS)
		{
			return $this->deleteRealization($id);
		}
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

	private function deleteRealization(int $id): ?array
	{
		if (!Loader::requireModule('crm'))
		{
			$this->addError(new Error('Module crm is not installed'));

			return null;
		}

		$this->forward(
			RealizationDocument::class,
			'setRealization',
			[
				'id' => $id,
				'value' => 'N'
			]
		);

		if (!empty($this->getErrors()))
		{
			return null;
		}

		$shipment = ShipmentRepository::getInstance()->getById($id);
		if (!$shipment)
		{
			return null;
		}

		$fields = $shipment->getFields()->getValues();
		$fields['DOC_TYPE'] = 'W';
		PullManager::getInstance()->sendDocumentDeletedEvent([
			[
				'id' => $shipment->getId(),
				'data' => [
					'fields' => $fields,
				],
			],
		]);

		return [
			'result' => true,
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
