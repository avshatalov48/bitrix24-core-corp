<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class DocumentFieldOption extends Dto
{
	/** @var string */
	public $id;

	/** @var string */
	public $value;

	public function getCasts(): array
	{
		return [
			'id' => Type::string(),
			'value' => Type::string(),
		];
	}
}