<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Responsible extends Dto
{
	/** @var int|null */
	public $id;

	/** @var string|null */
	public $label;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'label' => Type::string(),
		];
	}
}