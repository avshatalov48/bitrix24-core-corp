<?php

namespace Bitrix\Mobile\InventoryControl\Dto;

use Bitrix\Mobile\Dto\Dto;

final class Document extends Dto
{
	/** @var int|null */
	public $id;

	/** @var string|null */
	public $type;

	/** @var string|null */
	public $currency;

	/** @var bool */
	public $editable = true;

	/** @var array */
	public $total;
}
