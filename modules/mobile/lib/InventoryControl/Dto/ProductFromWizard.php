<?php

namespace Bitrix\Mobile\InventoryControl\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;
use Bitrix\Mobile\Dto\Type;

final class ProductFromWizard extends Dto
{
	public $wizardUniqid;

	public $amount;

	public $basePrice;

	public $documentCurrency;

	public $documentType;

	public $id;

	public $measureCode;

	public $morePhoto;

	public $name;

	public $barcode;

	public $purchasingPrice;

	public $section;

	/**
	 * @var Store|null
	 */
	public $storeFrom;

	/**
	 * @var Store|null
	 */
	public $storeTo;

	public function getCasts(): array
	{
		return [
			'storeFrom' => Type::object(Store::class),
			'storeTo' => Type::object(Store::class),
		];
	}

	protected function getDecoders(): array
	{
		return [
			new ToCamelCase(),
		];
	}
}
