<?php

namespace Bitrix\CatalogMobile\InventoryControl\Dto;

use Bitrix\CatalogMobile\Dto\Measure;
use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class DocumentProductRecord extends Dto
{
	/** @var int|string|null */
	public $id;

	/**
	 * @var int|null
	 * @see \Bitrix\Catalog\ProductTable::TYPE_*
	 */
	public $type;

	/** @var int|null */
	public $documentId;

	/** @var int|null */
	public $basketCode;

	/** @var int|null */
	public $documentType;

	/** @var int|null */
	public $productId;

	public $name;

	/** @var int|null */
	public $storeFromId;

	/** @var int|null */
	public $storeToId;

	public $storeToAvailableAmount = 0.0;
	public $storeToAmount = 0.0;

	public $desktopUrl;

	public $gallery = [];
	public $galleryInfo = [];

	public $properties = [];

	public $sections = [];

	public $barcode;
	public $oldBarcode;

	/** @var float */
	public $amount = 0.0;

	public $price;

	/** @var Measure|null */
	public $measure;

	/** @var Store|null */
	public $storeFrom;

	public $storeFromAvailableAmount = 0.0;
	public $storeFromAmount = 0.0;

	/** @var Store|null */
	public $storeTo;

	/** @var bool */
	public $hasStoreFromAccess;

	/** @var bool */
	public $hasStoreToAccess;

	public $skuTree = [];

	public function getCasts(): array
	{
		return [
			'measure' => Type::object(Measure::class),
			'storeFrom' => Type::object(Store::class),
			'storeTo' => Type::object(Store::class),
			'hasStoreFromAccess' => Type::bool(),
			'hasStoreToAccess' => Type::bool(),
		];
	}
}
