<?php

declare(strict_types = 1);

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Catalog\StoreBarcodeTable;
use Bitrix\Catalog\StoreDocumentBarcodeTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Mobile\InventoryControl\Command\ConductDocumentCommand;
use Bitrix\Mobile\InventoryControl\DataProvider\DocumentProducts;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Main\Engine\JsonPayload;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Integration\Catalog\EntityEditor\StoreDocumentProvider;
use Bitrix\Mobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\UI\DetailCard\Controller;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\Main\Type\DateTime;
use Bitrix\Catalog\StoreDocumentFileTable;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Catalog\StoreDocumentElementTable;
use Bitrix\Sale\PriceMaths;
use CCatalogStoreDocsBarcode;

Loader::requireModule('catalog');

/**
 * Class StoreDocumentDetails
 *
 * @package Bitrix\Mobile\Controller\Catalog
 */
class StoreDocumentDetails extends Controller
{
	use ReadsApplicationErrors;
	use CatalogPermissions;

	/**
	 * @inherit
	 */
	public function getLoadActionsList(): array
	{
		return ['main', 'products'];
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function loadMainAction(array $params = []): array
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_READ_PERMS')));

			return [];
		}

		$id = $params['id'] ?? null;
		if ($id === null)
		{
			$docType = $params['docType'] ?? null;
			if (empty($docType))
			{
				throw new \DomainException('Parameter {docType} is required for document creation.');
			}

			$provider = StoreDocumentProvider::createByType($docType);
		}
		else
		{
			$provider = StoreDocumentProvider::createById($id);
		}

		return [
			'editor' => (new FormWrapper($provider))->getResult(),
		];
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function loadProductsAction(array $params): array
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_READ_PRODUCTS_PERMS')));

			return [];
		}

		$documentId = isset($params['id']) ? (int)$params['id'] : null;
		$documentType = $params['docType'] ?? null;

		return DocumentProducts\Facade::loadByDocumentId($documentId, $documentType);
	}

	/**
	 * @inheritDoc
	 */
	protected function add(array $parameters, array $data): ?int
	{
		if (!$this->hasWritePermissions())
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_ADD_PERMS')));

			return null;
		}

		$documentType = $parameters['docType'] ?? '';
		if (!$documentType)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

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

		$fields['DOC_TYPE'] = $documentType;
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

		return $documentId;
	}

	/**
	 * @inheritDoc
	 */
	protected function update($parameters, $data): ?int
	{
		if (!$this->hasWritePermissions())
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_UPDATE_PERMS')));

			return null;
		}

		$documentId = isset($parameters['id']) ? (int)$parameters['id'] : 0;
		if (!$documentId)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

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
		$fields['MODIFIED_BY'] = $this->getCurrentUser()->getId();

		$result = \CCatalogDocs::update($documentId, $this->prepareFieldsForSaving($fields));
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

		return $documentId;
	}

	/**
	 * @param JsonPayload $payload
	 * @return array|null
	 */
	public function conductAction(JsonPayload $payload): ?array
	{
		if (!$this->hasWritePermissions())
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_CONDUCT_PERMS')));

			return null;
		}

		$data = $payload->getData();
		$id = (int)($data['id'] ?? 0);

		if (!$id)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		$command = new ConductDocumentCommand($id, (int)$this->getCurrentUser()->getId());
		$result = $command();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [
			'load' => $this->createLoadResponse(['id' => $id])
		];
	}

	/**
	 * @param JsonPayload $payload
	 * @return array|null
	 */
	public function cancelAction(JsonPayload $payload):? array
	{
		if (!$this->hasWritePermissions())
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_CANCELLATION_PERMS')));

			return null;
		}

		$data = $payload->getData();
		$id = (int)($data['id'] ?? 0);

		if (!$id)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		$result = \CCatalogDocs::cancellationDocument($id, $this->getCurrentUser()->getId());
		if (!$result)
		{
			$this->addError(
				$this->getLastApplicationError()
					?: new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_CANCELLATION'))
			);

			return null;
		}

		return [
			'load' => $this->createLoadResponse(['id' => $id])
		];
	}

	/**
	 * @inherit
	 */
	protected function getEntityTitle(int $entityId): string
	{
		$document = StoreDocumentTable::getList(['filter' => ['=ID' => $entityId]])->fetch();
		if ($document)
		{
			return $document['TITLE'] ?? '';
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
			'select' => ['ID'],
			'filter' => ['=DOC_ID' => $documentId]
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
				'filter' => ['DOC_ELEMENT_ID' => $documentRecordIds]
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
			$productDto = new DocumentProductRecord($productElement);

			$price = $productDto->price ?? [];
			$pricePurchase = $price['purchase'] ?? [];
			$priceSell = $price['sell'] ?? [];

			$fields = [
				'DOC_ID' => $documentId,
				'AMOUNT' => (float)$productDto->amount,
				'ELEMENT_ID' => (int)$productDto->productId,
				'PURCHASING_PRICE' => isset($pricePurchase['amount']) ? (float)$pricePurchase['amount'] : null,
				'BASE_PRICE' => isset($priceSell['amount']) ? (float)$priceSell['amount'] : null,
			];

			if (in_array(
				$document['DOC_TYPE'],
				[
					StoreDocumentTable::TYPE_ARRIVAL,
					StoreDocumentTable::TYPE_STORE_ADJUSTMENT,
					StoreDocumentTable::TYPE_MOVING
				],
				true
			))
			{
				$fields['STORE_TO'] = isset($productDto->storeToId)
					? (int)$productDto->storeToId
					: null;
			}

			if (in_array(
				$document['DOC_TYPE'],
				[
					StoreDocumentTable::TYPE_MOVING,
					StoreDocumentTable::TYPE_DEDUCT,
				],
				true
			))
			{
				$fields['STORE_FROM'] = isset($productDto->storeFromId)
					? (int)$productDto->storeFromId
					: null;
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
					'DOC_ELEMENT_ID' => $documentRecordId
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
				'AMOUNT'
			],
			'filter' => ['=DOC_ID' => $documentId]
		]);
		$documentTotal = 0.00;
		while ($element = $elementsList->fetch())
		{
			$documentTotal += PriceMaths::roundPrecision((float)$element['PURCHASING_PRICE'] * (float)$element['AMOUNT']);
		}

		\CCatalogDocs::update($documentId, ['TOTAL' => $documentTotal]);
	}

	/**
	 * @param int $documentId
	 * @param array $files
	 */
	private function updateFiles(int $documentId, array $files): void
	{
		$isSuccess = true;

		$fileIds = array_filter($files, function ($file) {
			return !is_array($file);
		});

		$filesToSave = array_filter($files, function ($file) {
			return is_array($file);
		});
		foreach ($filesToSave as $fileToSave)
		{
			$fileId = \CFile::saveFile(
				[
					'MODULE_ID' => 'catalog',
					'name' => $fileToSave['name'],
					'type' => $fileToSave['type'],
					'content' => base64_decode($fileToSave['content'])
				],
				'catalog_store_documents'
			);

			if ((int)$fileId > 0)
			{
				$fileIds[] = $fileId;
			}
			else
			{
				$isSuccess = false;
			}
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
			$idsToDelete = array_intersect_key($fileIdToPrimary, array_fill_keys($filesToDelete, true));
			foreach ($idsToDelete as $id)
			{
				$deleteResult = StoreDocumentFileTable::delete($id);
				if ($deleteResult->isSuccess())
				{
					\CFile::Delete($id);
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
			$addResult = StoreDocumentFileTable::add([
				'DOCUMENT_ID' => $documentId,
				'FILE_ID' => $fileToAdd,
			]);
			if (!$addResult->isSuccess())
			{
				$isSuccess = false;
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
	 * @param array $products
	 */
	private function updateCatalogProducts(array $products): void
	{
		foreach ($products as $product)
		{
			$productDto = new DocumentProductRecord($product);
			$sku = ServiceContainer::getRepositoryFacade()->loadVariation((int)$productDto->productId);
			if (!$sku)
			{
				continue;
			}

			/**
			 * Name
			 */
			$sku->setField('NAME', $productDto->name);

			/**
			 * Measure
			 */
			$measure = $productDto->measure ?? null;
			if ($measure && $measure->id)
			{
				$sku->setField('MEASURE', $measure->id);
			}

			/**
			 * Sections
			 */
			$sections = [];
			if (!empty($productDto->sections))
			{
				foreach ($productDto->sections as $section)
				{
					$sections[] = (int)$section['id'];
				}
			}

			$sku->getParent()->getSectionCollection()->setValues(
				empty($sections) ? [0] : $sections
			);

			/**
			 * Images
			 */
			$gallery = $productDto->gallery ?? [];
			$receivedFileIds = [];
			$newImages = [];
			foreach ($gallery as $file)
			{
				if (is_array($file))
				{
					if (isset($file['new']))
					{
						$newImages[] = $file;
					}
				}
				else
				{
					$receivedFileIds[] = (int)$file;
				}
			}

			/**
			 * Remove
			 */
			$imageCollection = $sku->getImageCollection();
			$morePhotos = $imageCollection->getMorePhotos();
			foreach ($morePhotos as $image)
			{
				if (in_array((int)$image->getFields()['ID'], $receivedFileIds, true))
				{
					continue;
				}

				$image->remove();
			}

			/**
			 * Add new
			 */
			foreach ($newImages as $newImage)
			{
				$fileId = \CFile::saveFile(
					[
						'MODULE_ID' => 'catalog',
						'name' => $newImage['new']['name'],
						'type' => $newImage['new']['type'],
						'content' => base64_decode($newImage['new']['content']),
					],
					'catalog'
				);

				if ((int)$fileId > 0)
				{
					$imageCollection->addValues([
						\CFile::MakeFileArray($fileId)
					]);
				}
			}

			$result = $sku->save();
			if (!$result->isSuccess())
			{
				$this->addNonCriticalErrors($result->getErrors());
			}
		}
	}

	/**
	 * @return string[]
	 */
	private function getAllowedForSaveFields(): array
	{
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
					function ($field) {
						return $field->getDataType();
					},
					StoreDocumentTable::getEntity()->getFields()
				),
				function($v) {
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

		return $result;
	}

	/**
	 * Checks unique barcodes
	 * @param array $data
	 * @return bool
	 */
	private function validateBarcodes(array $data): bool
	{
		if (!isset($data['PRODUCTS']))
		{
			return true;
		}

		$documentBarcodes = [];
		$productBarcodes = [];

		foreach ($data['PRODUCTS'] as $productData)
		{
			$productDto = new DocumentProductRecord($productData);
			$productId = (int)$productDto->productId;
			$barcode = (string)$productDto->barcode;

			if ($productId > 0 && $barcode !== '')
			{
				$documentBarcodes[$barcode] = $productId;
			}
		}

		if (empty($documentBarcodes))
		{
			return true;
		}

		$rows = StoreBarcodeTable::getList([
			'filter' => ['=BARCODE' => array_keys($documentBarcodes)],
			'select' => ['PRODUCT_ID', 'BARCODE'],
		]);

		while ($row = $rows->fetch())
		{
			$productBarcodes[$row['BARCODE']] = (int)$row['PRODUCT_ID'];
		}

		$isSuccess = true;
		foreach ($documentBarcodes as $barcode => $documentSkuId)
		{
			if (isset($productBarcodes[$barcode]))
			{
				$existingSkuId = $productBarcodes[$barcode];
				if ($documentSkuId !== $existingSkuId)
				{
					$isSuccess = false;
					$message = Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_BARCODE_ALREADY_EXISTS', [
						'#BARCODE#' => htmlspecialcharsbx($barcode)
					]);
					$this->addError(new Error($message));
				}
			}
		}

		return $isSuccess;
	}
}
