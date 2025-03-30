<?php

namespace Bitrix\CatalogMobile\ProductWizard;

use Bitrix\Catalog\Component\ImageInput;
use Bitrix\Catalog\Controller\ProductSelector;
use Bitrix\Catalog\v2\Barcode\Barcode;
use Bitrix\Catalog\v2\Image\MorePhotoImage;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Mobile\Command;

Loader::requireModule('catalog');
Loader::requireModule('iblock');

final class SaveProductCommand extends Command
{
	private array $fields;
	private ?int $variationId;
	private ProductSelector $controller;
	private int $productIblockId;

	public function __construct(array $fields, ?int $variationId = null)
	{
		$this->fields = $fields;
		$this->variationId = $variationId;
		$this->productIblockId = (int)$fields['IBLOCK_ID'];
		$this->controller = new ProductSelector();
	}

	public function execute(): Result
	{
		$result = new Result();

		$response = $this->callCatalogController();

		if ($response === null)
		{
			$result->addErrors($this->controller->getErrors());
		}
		else
		{
			$result = $this->finalize((int)$response['id']);
		}

		return $result;
	}

	private function callCatalogController(): ?array
	{
		if ($this->variationId)
		{
			$productId = $this->getParentProductId($this->variationId);
			$oldFields = $this->prepareOldProductFields($this->variationId);
			$fields = $this->prepareNewProductFields($this->fields, $oldFields);

			$response = $this->controller->updateSkuAction(
				$this->variationId,
				$fields,
				$oldFields
			);
			if ($response && isset($fields['MORE_PHOTO']))
			{
				$this->controller->saveMorePhotoAction(
					$productId,
					$this->variationId,
					$this->productIblockId,
					$fields['MORE_PHOTO']
				);
			}
		}
		else
		{
			$response = $this->controller->createProductAction($this->fields);
		}

		return $response;
	}

	private function getParentProductId(int $variationId): int
	{
		$variation = $this->getVariation($variationId);
		if (!$variation)
		{
			return $variationId;
		}

		$parent = $variation->getParent();
		if (!$parent)
		{
			return $variationId;
		}

		return $parent->getId();
	}

	private function prepareOldProductFields(int $variationId): array
	{
		$variation = $this->getVariation($variationId);
		if (!$variation)
		{
			return [];
		}

		$oldFields = [];

		$barcodeCollection = $variation->getBarcodeCollection();

		/** @var Barcode $barcode */
		if ($barcode = $barcodeCollection->getFirst())
		{
			$oldFields['BARCODE'] = $barcode->getBarcode();
		}

		return $oldFields;
	}

	private function prepareNewProductFields(array $fields, array $oldFields = []): array
	{
		if (isset($fields['BARCODE']) && empty($fields['BARCODE']) && empty($oldFields['BARCODE']))
		{
			unset($fields['BARCODE']);
		}

		unset($fields['IBLOCK_ID']);

		return $fields;
	}

	private function getVariation(int $variationId): ?BaseSku
	{
		$repositoryFacade = ServiceContainer::getRepositoryFacade();
		if (!$repositoryFacade)
		{
			return null;
		}

		return $repositoryFacade->loadVariation($variationId);
	}

	private function finalize(int $variationId): Result
	{
		$result = new Result();

		$variation = $this->getVariation($variationId);
		if (!$variation)
		{
			return $result;
		}

		$parentProduct = $variation->getParent();
		if (!$parentProduct)
		{
			return $result;
		}

		$name = $this->fields['NAME'];
		if (isset($name) && $parentProduct->getField('NAME') !== $name)
		{
			$parentProduct->setField('NAME', $name);
			$result = $parentProduct->save();
		}

		if ($result->isSuccess())
		{
			$result->setData($this->getProductData($variation));
		}

		return $result;
	}

	private function getProductData(BaseSku $variation): array
	{
		$morePhotoProperty = $variation->getPropertyCollection()->findByCodeLazy(MorePhotoImage::CODE);
		$propertyId = $morePhotoProperty ? $morePhotoProperty->getId() : '';
		$signedValues = (new ImageInput($variation))->getFormattedField()['values'];

		$morePhoto = [];
		foreach ($variation->getImageCollection()->getMorePhotos() as $morePhotoImage)
		{
			$propertyValueId = $morePhotoImage->getPropertyValueId();
			$fileId = $morePhotoImage->getId();
			$valueCode = "PROPERTY_{$propertyId}_{$propertyValueId}";

			$morePhoto[] = [
				'iblockPropertyValue' => $propertyValueId,
				'fileId' => $fileId,
				'valueCode' => $valueCode,
				'signedFileId' => $signedValues[$valueCode],
			];
		}

		return [
			'id' => $variation->getId(),
			'morePhoto' => $morePhoto,
		];
	}
}
