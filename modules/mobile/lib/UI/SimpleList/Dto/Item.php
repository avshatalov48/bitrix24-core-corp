<?php

namespace Bitrix\Mobile\UI\SimpleList\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

abstract class Item extends Dto
{
	/** @var int|string|null */
	public $id;

	/** @var Data|null */
	public $data;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'data' => Type::object(Data::class),
		];
	}
}
