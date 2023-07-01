<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\ProductGrid;

use Bitrix\Crm\ProductRow;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\Date;

final class ProductRowViewModel
{
	private const TYPE_PRODUCT = 1;

	public ProductRow $source;

	public string $currencyId;

	public array $skuTree = [];

	public bool $isTaxMode = false;

	public bool $isPriceEditable = false;

	public bool $isDiscountEditable = true;

	public array $sections = [];

	public array $gallery = [];

	public array $basketItemFields = [];

	public string $barcode = '';

	public int $type = self::TYPE_PRODUCT;

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
		$viewModel->isTaxMode = (bool)($fields['TAX_MODE'] ?? false);
		$viewModel->isPriceEditable = (bool)($fields['PRICE_EDITABLE'] ?? true);
		$viewModel->isDiscountEditable = (bool)($fields['DISCOUNT_EDITABLE'] ?? true);
		$viewModel->sections = $fields['SECTIONS'] ?? [];
		$viewModel->gallery = $fields['GALLERY'] ?? [];
		$viewModel->basketItemFields = $fields['BASKET_FIELDS'] ?? [];
		$viewModel->barcode = (string)($fields['BARCODE'] ?? '');
		$viewModel->type = (int)($fields['TYPE'] ?? self::TYPE_PRODUCT);

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
		$source = $this->source->toArray();
		if (isset($source['DATE_RESERVE_END']))
		{
			if ($source['DATE_RESERVE_END'] instanceof Date)
			{
				$source['DATE_RESERVE_END'] = $source['DATE_RESERVE_END']->toString();
			}
		}

		return array_merge($source, [
			'ID' => $this->getId(),
			'CURRENCY' => $this->currencyId,
			'SKU_TREE' => $this->skuTree,
			'TAX_MODE' => $this->isTaxMode,
			'PRICE_EDITABLE' => $this->isPriceEditable,
			'DISCOUNT_EDITABLE' => $this->isDiscountEditable,
			'SECTIONS' => $this->sections,
			'GALLERY' => $this->gallery,
			'BASKET_ITEM_FIELDS' => $this->basketItemFields,
			'BARCODE' => $this->barcode,
			'TYPE' => $this->type,
		]);
	}
}
