<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\ProductRow;
use Bitrix\Main\Security\Random;

final class ProductRowViewModel
{
	public ProductRow $source;

	public string $currencyId;

	public array $skuTree = [];

	public bool $isTaxMode = false;

	public bool $isPriceEditable = false;

	public bool $isDiscountEditable = true;

	public array $sections = [];

	public array $gallery = [];

	public string $barcode = '';

	private string $uniqId;

	public function __construct(ProductRow $source, string $currencyId)
	{
		$this->source = $source;
		$this->currencyId = $currencyId;
		$this->uniqId = 'tmp_' . Random::getString(8);
	}

	public static function createFromArray(array $fields): self
	{
		$productRow = ProductRow::createFromArray($fields);
		$currencyId = $fields['CURRENCY'] ?? '';

		$viewModel = new self($productRow, $currencyId);

		$viewModel->skuTree = $fields['SKU_TREE'] ?? [];
		$viewModel->isTaxMode = (bool)$fields['TAX_MODE'];
		$viewModel->isPriceEditable = (bool)$fields['PRICE_EDITABLE'];
		$viewModel->isDiscountEditable = (bool)$fields['DISCOUNT_EDITABLE'];
		$viewModel->sections = $fields['SECTIONS'] ?? [];
		$viewModel->gallery = $fields['GALLERY'] ?? [];
		$viewModel->barcode = (string)$fields['BARCODE'];

		return $viewModel;
	}

	/**
	 * @return int|string
	 */
	public function getId()
	{
		$id = (int)$this->source->getId();
		return $id > 0 ? $id : $this->uniqId;
	}

	public function getProductId(): int
	{
		return (int)$this->source->getProductId();
	}

	public function toArray(): array
	{
		return array_merge($this->source->toArray(), [
			'ID' => $this->getId(),
			'CURRENCY' => $this->currencyId,
			'SKU_TREE' => $this->skuTree,
			'TAX_MODE' => $this->isTaxMode,
			'PRICE_EDITABLE' => $this->isPriceEditable,
			'DISCOUNT_EDITABLE' => $this->isDiscountEditable,
			'SECTIONS' => $this->sections,
			'GALLERY' => $this->gallery,
			'BARCODE' => $this->barcode,
		]);
	}
}
