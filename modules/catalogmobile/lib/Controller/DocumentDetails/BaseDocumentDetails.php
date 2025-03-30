<?php

declare(strict_types = 1);

namespace Bitrix\CatalogMobile\Controller\DocumentDetails;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\StoreBarcodeTable;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Catalog\UI\FileUploader\ProductController;
use Bitrix\Catalog\v2\Image\MorePhotoImage;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Main\Request;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\CatalogMobile\InventoryControl\Dto\DocumentProductRecord;
use Bitrix\Mobile\UI\DetailCard\Controller;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\Uploader;
use Bitrix\Catalog\Access;

Loader::requireModule('catalog');
Loc::loadMessages(__DIR__ . '/../StoreDocumentDetails.php');

/**
 * Class StoreDocumentDetails
 *
 * @package Bitrix\Mobile\Controller\Catalog
 */
abstract class BaseDocumentDetails extends Controller
{
	/** @var AccessController */
	protected \Bitrix\Catalog\Access\AccessController $accessController;

	use ReadsApplicationErrors;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->accessController = AccessController::getCurrent();
	}

	public function getTabIds(): array
	{
		return ['main', 'products'];
	}

	protected function getProductPendingFiles(array $tokens, BaseSku $sku): PendingFileCollection
	{
		$fileController = new ProductController([
			'productId' => $sku->getId(),
		]);

		return (new Uploader($fileController))->getPendingFiles($tokens);
	}

	/**
	 * @param array $products
	 */
	protected function updateCatalogProducts(array $products): void
	{
		if (!$this->checkProductModifyRights())
		{
			return;
		}

		foreach ($products as $product)
		{
			$productDto = DocumentProductRecord::make($product);
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
			 * Barcode
			 */
			if ($productDto->barcode || $productDto->oldBarcode)
			{
				$updateBarcodeItem = null;
				$barcodeCollection = $sku->getBarcodeCollection();
				if ($productDto->oldBarcode)
				{
					$updateBarcodeItem = $barcodeCollection->getItemByBarcode($productDto->oldBarcode);
				}

				if ($updateBarcodeItem)
				{
					if (empty($productDto->barcode))
					{
						$barcodeCollection->remove($updateBarcodeItem);
					}
					else
					{
						$updateBarcodeItem->setBarcode($productDto->barcode);
					}
				}
				else
				{
					$barcodeItem =
						$barcodeCollection
							->create()
							->setBarcode($productDto->barcode)
					;

					$barcodeCollection->add($barcodeItem);
				}
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
			$newImageIds = [];
			$tokens = [];

			foreach ($gallery as $file)
			{
				if (is_array($file))
				{
					if (!empty($file['token']))
					{
						$tokens[] = $file['token'];
					}
				}
				else
				{
					$receivedFileIds[] = (int)$file;
				}
			}

			$pendingFiles = null;
			if (!empty($tokens))
			{
				$pendingFiles = $this->getProductPendingFiles($tokens, $sku);
				$newImageIds = $pendingFiles->getFileIds();
			}

			/**
			 * Remove
			 */
			$imageCollection = $sku->getImageCollection();
			foreach ($imageCollection as $image)
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
			$morePhotoProperty = $sku->getPropertyCollection()->findByCodeLazy(MorePhotoImage::CODE);
			$hasMorePhoto = $morePhotoProperty && $morePhotoProperty->isActive();

			foreach ($newImageIds as $newImage)
			{
				$fileArray = \CFile::MakeFileArray($newImage);

				if ($hasMorePhoto)
				{
					$imageCollection->addValues([$fileArray]);
				}
				else
				{
					$previewImage = $imageCollection->getPreviewImage();
					$previewImageValue = $previewImage->getFileStructure();
					if (empty($previewImageValue))
					{
						$previewImage->setFileStructure($fileArray);
						continue;
					}

					$detailImage = $imageCollection->getDetailImage();
					$detailImageValue = $detailImage->getFileStructure();
					if (empty($detailImageValue))
					{
						$detailImage->setFileStructure($fileArray);
						continue;
					}

					break;
				}
			}

			$result = $sku->save();
			if ($result->isSuccess())
			{
				if ($pendingFiles)
				{
					$pendingFiles->makePersistent();
				}
			}
			else
			{
				$this->addNonCriticalErrors($result->getErrors());
			}
		}
	}

	/**
	 * Checks unique barcodes
	 *
	 * @param array $data
	 * @return bool
	 */
	protected function validateBarcodes(array $data): bool
	{
		if (!isset($data['PRODUCTS']))
		{
			return true;
		}

		$documentBarcodes = [];
		$productBarcodes = [];

		foreach ($data['PRODUCTS'] as $productData)
		{
			$productDto = DocumentProductRecord::make($productData);
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
						'#BARCODE#' => htmlspecialcharsbx($barcode),
					]);
					$this->addError(new Error($message));
				}
			}
		}

		return $isSuccess;
	}

	protected function checkEditPurchasePriceRights(): bool
	{
		return (
			$this->accessController->check(ActionDictionary::ACTION_CATALOG_READ)
			&& $this->accessController->check(ActionDictionary::ACTION_PRODUCT_PURCHASE_INFO_VIEW)
		);
	}

	protected function checkEditPriceRights(): bool
	{
		return (
			$this->accessController->check(ActionDictionary::ACTION_CATALOG_READ)
			&& $this->accessController->check(ActionDictionary::ACTION_PRICE_EDIT)
		);
	}

	protected function checkDocumentBaseRights(): bool
	{
		return (
			$this->accessController->check(ActionDictionary::ACTION_CATALOG_READ)
			&& $this->accessController->check(ActionDictionary::ACTION_INVENTORY_MANAGEMENT_ACCESS)
		);
	}

	protected function checkProductModifyRights(): bool
	{
		return (
			$this->accessController->check(ActionDictionary::ACTION_CATALOG_READ)
			&& $this->accessController->check(ActionDictionary::ACTION_PRODUCT_EDIT)
		);
	}

	protected function checkStoreAccessRights(int $storeId): bool
	{
		if (!$this->checkDocumentBaseRights())
		{
			return false;
		}

		return $this->accessController->checkByValue(
			ActionDictionary::ACTION_STORE_VIEW,
			(string)$storeId
		);
	}

	protected function checkDocumentReadRights(int $entityId = null, string $docType = null): bool
	{
		return (
			$this->checkDocumentBaseRights()
			&& $this->accessController->check(
				ActionDictionary::ACTION_STORE_DOCUMENT_VIEW,
				(
				$entityId && $docType !== StoreDocumentTable::TYPE_SALES_ORDERS
					? Access\Model\StoreDocument::createFromId($entityId)
					: Access\Model\StoreDocument::createFromArray(['DOC_TYPE' => $docType])
				)
			)
		);
	}

	protected function checkDocumentConductRights(int $documentId): bool
	{
		return (
			$this->checkDocumentBaseRights()
			&& $this->accessController->check(
				ActionDictionary::ACTION_STORE_DOCUMENT_CONDUCT,
				Access\Model\StoreDocument::createFromId($documentId)
			)
		);
	}

	protected function checkDocumentCancelRights(int $documentId): bool
	{
		return (
			$this->checkDocumentBaseRights()
			&& $this->accessController->check(
				ActionDictionary::ACTION_STORE_DOCUMENT_CANCEL,
				Access\Model\StoreDocument::createFromId($documentId)
			)
		);
	}

	protected function checkDocumentModifyRights(int $entityId = null, string $docType = null): bool
	{
		return (
			$this->checkDocumentBaseRights()
			&& $this->accessController->check(
				ActionDictionary::ACTION_STORE_DOCUMENT_MODIFY,
				(
				$entityId
					? Access\Model\StoreDocument::createFromId($entityId)
					: Access\Model\StoreDocument::createFromArray(['DOC_TYPE' => $docType])
				)
			)
		);
	}
}
