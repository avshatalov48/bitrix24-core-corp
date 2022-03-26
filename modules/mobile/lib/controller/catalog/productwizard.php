<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Catalog\Component\ImageInput;
use Bitrix\Catalog\v2\Barcode\Barcode;
use Bitrix\Catalog\v2\Image\MorePhotoImage;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Product\BaseProduct;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

class ProductWizard extends \Bitrix\Main\Engine\Controller
{
	use CatalogPermissions;
	private const MAX_DICTIONARY_ITEMS = 500;

	/**
	 * Get config data for catalog product wizard.
	 *
	 * @param string $wizardType Wizard type
	 * @return array|\array[][]
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function configAction(string $wizardType): array
	{
		if (!$this->hasReadPermissions())
		{
			$this->addError(new Error($this->getInsufficientPermissionsError()));
			return [];
		}

		if (
			$wizardType === 'store'
			&& Loader::includeModule('catalog')
			&& Loader::includeModule('currency')
		)
		{
			return [
				'dictionaries' => [
					'stores' => $this->getStoresList(),
					'measures' => $this->getMeasuresList(),
				],
			];
		}

		return [];
	}

	public function saveProductAction(array $fields, int $id = null): ?array
	{
		if (
			Loader::includeModule('catalog')
			&& Loader::includeModule('iblock')
		)
		{
			$iblockId = (int)$fields['IBLOCK_ID'];
			$controller = new \Bitrix\Catalog\Controller\ProductSelector();

			if ($id)
			{
				$oldFields = $this->prepareOldProductFields($id);
				$fields = $this->prepareNewProductFields($fields, $oldFields);

				$result = $controller->updateSkuAction(
					$id,
					$fields,
					$oldFields
				);
				if ($result && isset($fields['MORE_PHOTO']))
				{
					$controller->saveMorePhotoAction(
						$id,
						$id,
						$iblockId,
						$fields['MORE_PHOTO']
					);
				}
			}
			else
			{
				$result = $controller->createProductAction($fields);
			}

			if ($result === null)
			{
				$this->errorCollection->add($controller->getErrors());
			}
			else
			{
				$result = $this->getProductData((int)$result['id'], $iblockId);
			}

			return $result;
		}

		return [];
	}

	private function getProductData(int $productId, int $iblockId): array
	{
		$productRepository = ServiceContainer::getProductRepository($iblockId);
		if (!$productRepository)
		{
			return [];
		}

		/** @var BaseProduct $product */
		$product = $productRepository->getEntityById($productId);
		if (!$product)
		{
			return [];
		}

		$morePhotoProperty = $product->getPropertyCollection()->findByCode(MorePhotoImage::CODE);
		$propertyId = $morePhotoProperty ? $morePhotoProperty->getId() : '';
		$signedValues = (new ImageInput($product))->getFormattedField()['values'];

		$morePhoto = [];
		foreach ($product->getImageCollection()->getMorePhotos() as $morePhotoImage)
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
			'id' => $product->getId(),
			'morePhoto' => $morePhoto,
		];
	}

	private function getStoresList(): array
	{
		$result = [];

		$stores = \CCatalogStore::GetList(
			[
				'SORT' => 'ASC',
			],
			[
				'ACTIVE' => 'Y',
			],
			false,
			['nTopCount' => self::MAX_DICTIONARY_ITEMS],
			['ID', 'TITLE', 'ADDRESS','IS_DEFAULT',]
		);
		while ($store = $stores->Fetch())
		{
			$result[] = [
				'id' => $store['ID'],
				'title' => $store['TITLE'] == '' ? $store['ADDRESS'] : $store['TITLE'],
				'type' => 'store',
				'isDefault' => $store['IS_DEFAULT'] == 'Y',
			];
		}

		return $result;
	}

	private function getMeasuresList(): array
	{
		$result = [];

		$measures = \CCatalogMeasure::getList(
			[
				'CODE' => 'ASC'
			],
			[],
			false,
			['nTopCount' => self::MAX_DICTIONARY_ITEMS],
			['CODE', 'SYMBOL_RUS', 'SYMBOL_INTL', 'IS_DEFAULT', ]
		);

		while ($measure = $measures->Fetch())
		{
			$result[] = [
				'value' => (int)$measure['CODE'],
				'isDefault' => $measure['IS_DEFAULT'] == 'Y',
				'name' => $measure['SYMBOL_RUS'] ?? $measure['SYMBOL_INTL'],
			];
		}

		return $result;
	}

	private function prepareOldProductFields(int $skuId): array
	{
		$repositoryFacade = ServiceContainer::getRepositoryFacade();
		if (!$repositoryFacade)
		{
			return [];
		}

		$sku = $repositoryFacade->loadVariation($skuId);
		if (!$sku)
		{
			return [];
		}

		$oldFields = [];

		$barcodeCollection = $sku->getBarcodeCollection();

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
		return $fields;
	}
}
