<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Restriction extends Dto
{
	/** @var string|null */
	public $id;

	/** @var string|null */
	public $name;

	/** @var bool */
	public $isExceeded;

	public function getCasts(): array
	{
		return [
			'id' => Type::string(),
			'name' => Type::string(),
			'isExceeded' => Type::bool(),
		];
	}
}