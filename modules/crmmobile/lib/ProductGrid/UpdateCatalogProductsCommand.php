<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Catalog\v2\Facade\Repository;
use Bitrix\Catalog\UI\FileUploader\ProductController;
use Bitrix\Catalog\v2\Image\MorePhotoImage;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Mobile\Command;
use Bitrix\Mobile\Integration\Catalog\PermissionsProvider;
use Bitrix\Mobile\Integration\Catalog\Repository\MeasureRepository;
use Bitrix\UI\FileUploader\PendingFileCollection;
use Bitrix\UI\FileUploader\Uploader;

Loader::requireModule('catalog');

final class UpdateCatalogProductsCommand extends Command
{
	private array $productRows;

	/** @var PendingFileCollection[] */
	private array $pendingFileCollection = [];

	private Repository $productRepository;

	private PermissionsProvider $permissionsProvider;

	public function __construct(array $productRows)
	{
		$this->productRows = $productRows;
		$this->productRepository = ServiceContainer::getRepositoryFacade();
		$this->permissionsProvider = PermissionsProvider::getInstance();
	}

	public function execute(): Result
	{
		if (!$this->isCatalogProductEditPermitted())
		{
			return new Result();
		}

		return $this->mapVariations(function ($sku, $productRow) {
			/** @var BaseSku $sku */
			/** @var array<string, mixed> $productRow */

			if (isset($productRow['PRODUCT_NAME']))
			{
				$this->updateName($sku, (string)$productRow['PRODUCT_NAME']);
			}

			if (isset($productRow['MEASURE_CODE']))
			{
				$this->updateMeasure($sku, (string)$productRow['MEASURE_CODE']);
			}

			if (isset($productRow['SECTIONS']))
			{
				$this->updateSections($sku, (array)$productRow['SECTIONS']);
			}

			if (isset($productRow['BARCODE']))
			{
				$this->updateBarcode($sku, (string)$productRow['BARCODE']);
			}

			if (isset($productRow['GALLERY']))
			{
				$this->updateImages($sku, $productRow['GALLERY']);
			}
		});
	}

	/**
	 * @param \Closure $mutator
	 * @return Result
	 */
	private function mapVariations(\Closure $mutator): Result
	{
		foreach ($this->productRows as $productRow)
		{
			$skuId = (int)$productRow['PRODUCT_ID'];
			$sku = $this->productRepository->loadVariation($skuId);
			if (!$sku)
			{
				continue;
			}

			$mutator($sku, $productRow);

			$result = $sku->save();
			if ($result->isSuccess())
			{
				$this->commitPendingCollection($skuId);
			}
			else
			{
				return $result;
			}
		}

		return new Result();
	}

	private function updateName(BaseSku $sku, string $name): void
	{
		$name = trim($name);
		if (mb_strlen($name) > 0)
		{
			$sku->setName($name);
		}
	}

	private function updateMeasure(BaseSku $sku, string $measureCode): void
	{
		$measureCode = trim($measureCode);
		if ($measureCode !== '')
		{
			$measure = MeasureRepository::findByCode($measureCode);
			if ($measure)
			{
				$sku->setField('MEASURE', $measure->id);
			}
		}
	}

	private function updateSections(BaseSku $sku, array $sections): void
	{
		$sectionIds = array_map(fn ($section) => (int)$section['ID'], $sections);
		$sku->getParent()->getSectionCollection()->setValues(
			empty($sectionIds) ? [0] : $sectionIds
		);
	}

	private function updateBarcode(BaseSku $sku, string $barcode): void
	{
		$sku->getBarcodeCollection()->setSimpleBarcodeValue($barcode);
	}

	private function updateImages(BaseSku $sku, array $images): void
	{
		$receivedFileIds = [];
		$newImageIds = [];
		$tokens = [];

		foreach ($images as $file)
		{
			if (!empty($file['id']) && is_numeric($file['id']))
			{
				$receivedFileIds[] = (int)$file['id'];
			}
			elseif (!empty($file['token']))
			{
				$tokens[] = $file['token'];
			}
		}

		if (!empty($tokens))
		{
			$pendingFiles = $this->getProductPendingFiles($tokens, $sku->getId());
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
		$morePhotoProperty = $sku->getPropertyCollection()->findByCode(MorePhotoImage::CODE);
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
	}

	private function getProductPendingFiles(array $tokens, int $skuId): PendingFileCollection
	{
		$fileController = new ProductController([
			'productId' => $skuId,
		]);
		$uploader = (new Uploader($fileController));

		$this->pendingFileCollection[$skuId] = $uploader->getPendingFiles($tokens);

		return $this->pendingFileCollection[$skuId];
	}

	private function commitPendingCollection(int $skuId): void
	{
		$pendingCollection = $this->pendingFileCollection[$skuId] ?? null;
		if ($pendingCollection)
		{
			$pendingCollection->makePersistent();
		}
	}

	private function isCatalogProductEditPermitted(): bool
	{
		$permissions = $this->permissionsProvider->getPermissions();

		/** @var ?bool $canEditProduct */
		$canEditProduct = $permissions['catalog_product_edit'];

		return isset($canEditProduct) && $canEditProduct === true;
	}
}
