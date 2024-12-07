<?php

declare(strict_types = 1);

namespace Bitrix\CatalogMobile\Controller\DocumentDetails;

use Bitrix\Catalog\StoreDocumentBarcodeTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\UI\FileUploader\DocumentController;
use Bitrix\CatalogMobile\InventoryControl\Command\ConductDocumentCommand;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\CatalogMobile\EntityEditor\StoreDocumentProvider;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\Main\Type\DateTime;
use Bitrix\Mobile\Field\UserFieldDatabaseAdapter;
use Bitrix\Catalog\StoreDocumentFileTable;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Catalog\StoreDocumentElementTable;
use Bitrix\Catalog\Document\StoreDocumentTableManager;
use Bitrix\Sale\PriceMaths;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\Uploader;
use CCatalogStoreDocsBarcode;
use Bitrix\Catalog\v2\Contractor\Provider\Manager;

Loader::requireModule('catalog');

/**
 * Class StoreDocumentDetails
 *
 * @package Bitrix\Mobile\Controller\Catalog
 */
class StoreDocumentDetails extends BaseDocumentDetails
{
	use ReadsApplicationErrors;

	private string $docType = '';

	/**
	 * @param int|null $entityId
	 * @param string|null $docType
	 * @return array
	 */
	public function loadMainAction(int $entityId = null, string $docType = null): array
	{
		if (!$this->checkDocumentReadRights($entityId, $docType))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_READ_PERMS')));

			return [];
		}

		if ($entityId === null)
		{
			if (empty($docType))
			{
				throw new \DomainException('Parameter {docType} is required for document creation.');
			}

			$provider = StoreDocumentProvider::createByType($docType);
		}
		else
		{
			$provider = StoreDocumentProvider::createById($entityId);
		}

		return [
			'editor' => (new FormWrapper($provider))->getResult(),
		];
	}

	/**
	 * @param int|null $entityId
	 * @param string|null $docType
	 * @return array
	 */
	public function loadProductsAction(int $entityId = null, string $docType = null): array
	{
		if (!$this->checkDocumentReadRights($entityId, $docType))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_READ_PRODUCTS_PERMS')));

			return [];
		}

		return DocumentProducts\Facade::loadByDocumentId($entityId, $docType);
	}

	public function addInternalAction(string $docType, array $data): ?int
	{
		if (!$this->checkDocumentModifyRights(null, $docType))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_ADD_PERMS')));

			return null;
		}

		if (!$docType)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		$this->docType = $docType;

		if (!$this->validateBarcodes($data))
		{
			return null;
		}

		$fields = array_intersect_key(
			$data,
			array_flip($this->getAllowedForSaveFields())
		);

		foreach ($this->getDateFields() as $dateField)
		{
			if (isset($fields[$dateField]))
			{
				$fields[$dateField] = DateTime::createFromTimestamp($fields[$dateField]);
			}
		}

		$fields['DOC_TYPE'] = $docType;
		$fields['SITE_ID'] = SITE_ID;
		$fields['CREATED_BY'] = $this->getCurrentUser()->getId();
		$fields['CURRENCY'] = $fields['CURRENCY'] ?: CurrencyManager::getBaseCurrency();

		$documentId = (int)\CCatalogDocs::add($this->prepareFieldsForSaving($fields));
		if (!$documentId)
		{
			$this->addError(
				$this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_ADD'))
			);

			return null;
		}

		/**
		 * Files
		 */
		if (isset($data['DOCUMENT_FILES']))
		{
			$this->updateFiles($documentId, $data['DOCUMENT_FILES']);
		}

		/**
		 * Products
		 */
		if (isset($data['PRODUCTS']))
		{
			$this->updateDocumentProductRecords($documentId, $data['PRODUCTS']);
			$this->updateCatalogProducts($data['PRODUCTS']);
		}

		$this->updateCatalogContractor($documentId, $data);

		return $documentId;
	}

	public function updateInternalAction(int $entityId, string $docType, array $data): ?int
	{
		if (!$this->checkDocumentModifyRights($entityId))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_UPDATE_PERMS')));

			return null;
		}

		if (!$entityId)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		if (!$this->validateBarcodes($data))
		{
			return null;
		}

		$docType = StoreDocumentTable::getRow([
			'select' => ['DOC_TYPE'],
			'filter' => ['=ID' => $entityId],
		])['DOC_TYPE'] ?? 0;

		if (!$docType)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		$this->docType = $docType;

		$fields = array_intersect_key(
			$data,
			array_flip($this->getAllowedForSaveFields())
		);
		foreach ($this->getDateFields() as $dateField)
		{
			if (isset($fields[$dateField]))
			{
				$fields[$dateField] = DateTime::createFromTimestamp($fields[$dateField]);
			}
		}
		$fields['MODIFIED_BY'] = $this->getCurrentUser()->getId();

		$result = \CCatalogDocs::update($entityId, $this->prepareFieldsForSaving($fields));
		if (!$result)
		{
			$this->addError(
				$this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_UPDATE'))
			);

			return null;
		}

		/**
		 * Files
		 */
		if (isset($data['DOCUMENT_FILES']))
		{
			$this->updateFiles($entityId, $data['DOCUMENT_FILES']);
		}

		/**
		 * Products
		 */
		if (isset($data['PRODUCTS']))
		{
			$this->updateDocumentProductRecords($entityId, $data['PRODUCTS']);
			$this->updateCatalogProducts($data['PRODUCTS']);
		}

		$this->updateCatalogContractor($entityId, $data);

		return $entityId;
	}

	/**
	 * @param int $entityId
	 * @return array|null
	 */
	public function conductAction(int $entityId, string $docType): ?array
	{
		if (!$this->checkDocumentConductRights($entityId))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_CONDUCT_PERMS')));

			return null;
		}

		if (!$entityId)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		$command = new ConductDocumentCommand($entityId, (int)$this->getCurrentUser()->getId());
		$result = $command();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [
			'load' => $this->createLoadResponse(),
		];
	}

	/**
	 * @param int $entityId
	 * @return array|null
	 */
	public function cancelAction(int $entityId, string $docType): ?array
	{
		if (!$this->checkDocumentCancelRights($entityId))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_CANCELLATION_PERMS')));

			return null;
		}

		if (!$entityId)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		$result = \CCatalogDocs::cancellationDocument($entityId, $this->getCurrentUser()->getId());
		if (!$result)
		{
			$this->addError(
				$this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_CANCELLATION'))
			);

			return null;
		}

		return [
			'load' => $this->createLoadResponse(),
		];
	}

	protected function getEntityTitle(): string
	{
		$entityId = $this->findInSourceParametersList('entityId');

		if ($entityId)
		{
			$document = StoreDocumentTable::getList(['filter' => ['=ID' => $entityId]])->fetch();
			if ($document)
			{
				return $document['TITLE'] ?? '';
			}
		}

		return '';
	}

	/**
	 * @param int $documentId
	 * @param array $products
	 */
	private function updateDocumentProductRecords(int $documentId, array $products): void
	{
		$isSuccess = true;

		$document = StoreDocumentTable::getList(['filter' => ['=ID' => $documentId]])->fetch();
		if (!$document)
		{
			return;
		}

		$existingElements = [];
		$existingElementsList = StoreDocumentElementTable::getList([
			'select' => ['*'],
			'filter' => ['=DOC_ID' => $documentId],
		]);
		while ($element = $existingElementsList->fetch())
		{
			$existingElements[$element['ID']] = $element;
		}

		/**
		 * Clean existing document barcodes
		 */
		$documentRecordIds = array_keys($existingElements);
		if (!empty($documentRecordIds))
		{
			$existingDocumentBarcodes = StoreDocumentBarcodeTable::getList([
				'select' => ['ID'],
				'filter' => ['DOC_ELEMENT_ID' => $documentRecordIds],
			]);
			while ($existingBarcode = $existingDocumentBarcodes->fetch())
			{
				CCatalogStoreDocsBarcode::delete($existingBarcode['ID']);
			}
		}

		/**
		 * Delete existing product elements
		 */
		$elementsToDelete = array_diff(
			array_column($existingElements, 'ID'),
			array_column($products, 'id')
		);
		foreach ($elementsToDelete as $elementToDelete)
		{
			$deleteResult = \CCatalogStoreDocsElement::delete($elementToDelete);
			if ($deleteResult !== true)
			{
				$isSuccess = false;
			}
		}

		/**
		 * Add / Update existing product elements
		 */
		foreach ($products as $productElement)
		{
			$productDto = DocumentProductRecord::make($productElement);

			$price = $productDto->price ?? [];
			$pricePurchase = $price['purchase'] ?? [];
			$priceSell = $price['sell'] ?? [];

			$fields = [
				'DOC_ID' => $documentId,
				'ELEMENT_ID' => (int)$productDto->productId,
			];

			$skuEntity = null;
			if ($this->checkEditPurchasePriceRights())
			{
				$fields['PURCHASING_PRICE'] = isset($pricePurchase['amount']) ? (float)$pricePurchase['amount'] : null;
			}
			else if (isset($existingElements[(int)$productDto->id]))
			{
				$fields['PURCHASING_PRICE'] = (float)$existingElements[(int)$productDto->id]['PURCHASING_PRICE'];
			}
			else
			{
				$skuEntity ??= ServiceContainer::getRepositoryFacade()->loadVariation($productElement['productId']);
				$fields['PURCHASING_PRICE'] = $skuEntity ? $skuEntity->getField('PURCHASING_PRICE') : null;
			}

			if ($this->checkEditPriceRights())
			{
				$fields['BASE_PRICE'] = isset($priceSell['amount']) ? (float)$priceSell['amount'] : null;
			}
			else if (isset($existingElements[(int)$productDto->id]))
			{
				$fields['BASE_PRICE'] = (float)$existingElements[(int)$productDto->id]['BASE_PRICE'];
			}
			else
			{
				$skuEntity ??= ServiceContainer::getRepositoryFacade()->loadVariation($productElement['productId']);
				$basePriceEntity = $skuEntity->getPriceCollection()->findBasePrice();
				$fields['BASE_PRICE'] = $basePriceEntity ? $basePriceEntity->getPrice() : null;
			}

			$existingStoreTo = isset($existingElements[(int)$productDto->id]['STORE_TO'])
				? (int)$existingElements[(int)$productDto->id]['STORE_TO']
				: null;
			$hasAccessToExistingStoreTo = !$existingStoreTo || $this->checkStoreAccessRights($existingStoreTo);
			if ($hasAccessToExistingStoreTo)
			{
				$storeTo = isset($productDto->storeToId)
					? (int)$productDto->storeToId
					: null;
				$hasStoreToAccess = !$storeTo || $this->checkStoreAccessRights($storeTo);
				if (
					$hasStoreToAccess
					&& in_array(
						$document['DOC_TYPE'],
						[
							StoreDocumentTable::TYPE_ARRIVAL,
							StoreDocumentTable::TYPE_STORE_ADJUSTMENT,
							StoreDocumentTable::TYPE_MOVING,
						],
						true
					)
				)
				{
					$fields['STORE_TO'] = $storeTo;
					$fields['AMOUNT'] = (float)$productDto->amount;
				}
			}

			$existingStoreFrom = isset($existingElements[(int)$productDto->id]['STORE_FROM'])
				? (int)$existingElements[(int)$productDto->id]['STORE_FROM']
				: null;
			$hasAccessToExistingStoreFrom = !$existingStoreFrom || $this->checkStoreAccessRights($existingStoreFrom);
			if ($hasAccessToExistingStoreFrom)
			{
				$storeFrom = isset($productDto->storeFromId)
					? (int)$productDto->storeFromId
					: null;
				$hasStoreFromAccess = !$storeFrom || $this->checkStoreAccessRights($storeFrom);
				if (
					$hasStoreFromAccess
					&& in_array(
						$document['DOC_TYPE'],
						[
							StoreDocumentTable::TYPE_MOVING,
							StoreDocumentTable::TYPE_DEDUCT,
						],
						true
					)
				)
				{
					$fields['STORE_FROM'] = $storeFrom;
					$fields['AMOUNT'] = (float)$productDto->amount;
				}
			}

			if (isset($existingElements[$productDto->id]))
			{
				$documentRecordId = (int)$productDto->id;
				$saveResult = StoreDocumentElementTable::update($documentRecordId, $fields);
			}
			else
			{
				$saveResult = StoreDocumentElementTable::add($fields);
				$documentRecordId = $saveResult->getId();
			}

			if (!$saveResult->isSuccess())
			{
				$isSuccess = false;
			}

			if (!empty($productDto->barcode) && !empty($documentRecordId))
			{
				CCatalogStoreDocsBarcode::add([
					'BARCODE' => $productDto->barcode,
					'DOC_ELEMENT_ID' => $documentRecordId,
				]);
			}
		}

		if (!$isSuccess)
		{
			$this->addNonCriticalError(
				new Error(
					Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_DOCUMENT_FILES_SAVE_ERROR')
				)
			);
		}

		/**
		 * Update document total
		 */
		$elementsList = StoreDocumentElementTable::getList([
			'select' => [
				'ID',
				'BASE_PRICE',
				'PURCHASING_PRICE',
				'AMOUNT',
			],
			'filter' => ['=DOC_ID' => $documentId],
		]);
		$documentTotal = 0.00;
		while ($element = $elementsList->fetch())
		{
			$documentTotal += PriceMaths::roundPrecision((float)$element['PURCHASING_PRICE']
				* (float)$element['AMOUNT']);
		}

		\CCatalogDocs::update($documentId, ['TOTAL' => $documentTotal]);
	}

	private function prepareUserFieldsValues(array $values): array
	{
		global $USER_FIELD_MANAGER;
		$tableClass = StoreDocumentTableManager::getTableClassByType($this->docType);

		if (!$tableClass)
		{
			return $values;
		}

		$userFieldInfos = $USER_FIELD_MANAGER->GetUserFields($tableClass::getUfId(), 0, LANGUAGE_ID);
		if (empty($userFieldInfos))
		{
			return $values;
		}

		$result = $values;

		$userFieldAdapter = new UserFieldDatabaseAdapter();
		$userFieldAdapter->setUploaderControllerClass(DocumentController::class);

		foreach ($userFieldInfos as $userFieldName => $userFieldInfo)
		{
			if (isset($values[$userFieldName]))
			{
				$result[$userFieldName] = $userFieldAdapter->getAdaptedUserFieldValue($userFieldInfo, $values[$userFieldName]);
			}
		}

		return $result;
	}

	private function getDocumentPendingFiles(array $tokens): PendingFileCollection
	{
		$fileController = new DocumentController([
			'fieldName' => 'DOCUMENT_FILES',
		]);

		return (new Uploader($fileController))->getPendingFiles($tokens);
	}

	/**
	 * @param int $documentId
	 * @param array $files
	 */
	private function updateFiles(int $documentId, array $files): void
	{
		$isSuccess = true;
		$pendingFiles = null;

		$fileIds = array_filter($files, static fn ($file) => !is_array($file));
		$filesToSave = array_filter($files, static fn ($file) => is_array($file) && !empty($file['token']));

		if (!empty($filesToSave))
		{
			$tokens = array_column($filesToSave, 'token');
			$pendingFiles = $this->getDocumentPendingFiles($tokens);
			$fileIds = array_merge($fileIds, $pendingFiles->getFileIds());
		}

		$existingFiles = StoreDocumentFileTable::getList([
			'select' => ['ID', 'FILE_ID'],
			'filter' => ['=DOCUMENT_ID' => $documentId],
		])->fetchAll();

		$existingFileIds = array_column($existingFiles, 'FILE_ID');
		$filesToDelete = array_diff($existingFileIds, $fileIds);
		if (!empty($filesToDelete))
		{
			$fileIdToPrimary = array_column($existingFiles, 'ID', 'FILE_ID');
			$primaryToFileId = array_column($existingFiles, 'FILE_ID', 'ID');
			$idsToDelete = array_intersect_key($fileIdToPrimary, array_fill_keys($filesToDelete, true));
			foreach ($idsToDelete as $id)
			{
				$deleteResult = StoreDocumentFileTable::delete($id);
				if ($deleteResult->isSuccess())
				{
					\CFile::Delete($primaryToFileId[$id]);
				}
				else
				{
					$isSuccess = false;
				}
			}
		}

		$filesToAdd = array_diff($fileIds, $existingFileIds);
		foreach ($filesToAdd as $fileToAdd)
		{
			if ($pendingFile = $pendingFiles->getByFileId($fileToAdd))
			{
				$addResult = StoreDocumentFileTable::add([
					'DOCUMENT_ID' => $documentId,
					'FILE_ID' => $fileToAdd,
				]);
				if ($addResult->isSuccess())
				{
					$pendingFile->makePersistent();
				}
				else
				{
					$isSuccess = false;
				}
			}
		}

		if (!$isSuccess)
		{
			$this->addNonCriticalError(
				new Error(
					Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_DOCUMENT_PRODUCTS_SAVE_ERROR')
				)
			);
		}
	}

	/**
	 * @param int $entityId
	 * @param array $data
	 */
	private function updateCatalogContractor(int $entityId, array $data): void
	{
		$contractorsProvider = Manager::getActiveProvider(Manager::PROVIDER_STORE_DOCUMENT);
		if ($contractorsProvider)
		{
			$contractorsProvider::onAfterDocumentSaveSuccessForMobile($entityId, $data);
		}
	}

	/**
	 * @return string[]
	 */
	private function getAllowedForSaveFields(): array
	{
		$additionalFields = [];
		if ($this->docType)
		{
			global $USER_FIELD_MANAGER;
			$tableClass = StoreDocumentTableManager::getTableClassByType($this->docType);
			if ($tableClass)
			{
				$userFieldInfos = $USER_FIELD_MANAGER->GetUserFields($tableClass::getUfId(), 0, LANGUAGE_ID);
				$additionalFields += array_keys($userFieldInfos);
			}
		}

		return [
			'DOC_NUMBER',
			'CONTRACTOR_ID',
			'TITLE',
			'RESPONSIBLE_ID',
			'CURRENCY',
			'DATE_DOCUMENT',
			'ITEMS_ORDER_DATE',
			'ITEMS_RECEIVED_DATE',
			'COMMENTARY',
			...$additionalFields,
		];
	}

	/**
	 * @return string[]
	 */
	private function getDateFields(): array
	{
		return array_keys(
			array_filter(
				array_map(
					static function ($field) {
						return $field->getDataType();
					},
					StoreDocumentTable::getEntity()->getFields()
				),
				static function ($v) {
					return in_array($v, ['datetime', 'date'], true);
				}
			)
		);
	}

	/**
	 * @param array $fields
	 * @return array
	 *
	 * @see CDatabaseMysql::PrepareUpdateBind
	 */
	private function prepareFieldsForSaving(array $fields): array
	{
		$result = [];

		foreach ($fields as $key => $value)
		{
			$result[$key] = $value ?? false;
		}

		$result = $this->prepareUserFieldsValues($result);

		return $result;
	}
}
