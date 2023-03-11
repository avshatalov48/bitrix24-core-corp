<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Catalog\v2\Barcode\Barcode;
use Bitrix\Catalog\v2\Facade\Repository;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Catalog\v2\Product\BaseProduct;
use Bitrix\Catalog\v2\Sku\BaseSku;
use Bitrix\Crm\Service\Accounting;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Query;
use Bitrix\Mobile\UI\File;

Loader::requireModule('crm');
Loader::requireModule('catalog');

final class SkuCollectionQuery extends Query
{
	private int $variationId;

	private Accounting $accounting;

	private TaxCalculator $taxCalculator;

	private Repository $productsRepository;

	private string $currencyId;

	public function __construct(int $variationId, string $currencyId)
	{
		$this->variationId = $variationId;
		$this->currencyId = $currencyId;
		$this->accounting = Container::getInstance()->getAccounting();
		$this->taxCalculator = new TaxCalculator($this->accounting);
		$this->productsRepository = ServiceContainer::getRepositoryFacade();
	}

	public function execute(): array
	{
		$product = $this->findParentProduct();
		if (!$product)
		{
			return [];
		}

		$variations = [];

		foreach ($product->getSkuCollection() as $sku)
		{
			$fields = $sku->getFields();

			$basePriceFields = $sku->getPriceCollection()->findBasePrice()->getFields();
			$basePrice = $basePriceFields['PRICE'] ?? 0.0;
			$currencyId = $basePriceFields['CURRENCY'] ?? null;
			if ($currencyId && $currencyId !== $this->currencyId)
			{
				$basePrice = \CCrmCurrency::ConvertMoney($basePrice, $currencyId, $this->currencyId);
			}

			$vatId = $fields['VAT_ID'] ?? 0;
			$vatIncluded = ($fields['VAT_INCLUDED'] ?? 'N') === 'Y';

			$this->taxCalculator->calculate((float)$basePrice, (int)$vatId, $vatIncluded);

			$gallery = array_values(array_map(
				fn($item) => File::loadWithPreview($item['ID']),
				$sku->getFrontImageCollection()->toArray()
			));

			$variations[$sku->getId()] = [
				'ID' => $sku->getId(),
				'NAME' => $sku->getName(),
				'GALLERY' => $gallery,
				'PRICE' => $this->taxCalculator->getFinalPrice(),
				'CURRENCY' => $this->currencyId,
				'PRICE_BEFORE_TAX' => $this->taxCalculator->getPriceBeforeTax(),
				'TAX_VALUE' => $this->taxCalculator->getTaxValue(),
				'TAX_RATE' => $this->taxCalculator->getTaxRate(),
				'TAX_INCLUDED' => $this->taxCalculator->isTaxIncluded(),
				'TAX_NAME' => $this->taxCalculator->getVatName(),
				'TAX_MODE' => $this->accounting->isTaxMode(),
				'EMPTY_PRICE' => empty($basePriceFields['PRICE']),
				'BARCODE' => $this->findBarcode($sku),
			];
		}

		return [
			'variations' => $variations,
		];
	}

	private function findParentProduct(): ?BaseProduct
	{
		$sku = $this->productsRepository->loadVariation($this->variationId);
		if (!$sku)
		{
			return null;
		}

		/** @var BaseProduct $product */
		$product = $sku->getParent();

		return $product;
	}

	private function findBarcode(BaseSku $sku): string
	{
		$barcode = '';
		$barcodeEntity = $sku->getBarcodeCollection()->getFirst();

		/** @var Barcode|null $barcodeEntity */
		if ($barcodeEntity)
		{
			$barcode = $barcodeEntity->getBarcode() ?? '';
		}

		return $barcode;
	}
}