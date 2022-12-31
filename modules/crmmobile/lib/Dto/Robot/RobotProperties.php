<?php

namespace Bitrix\CrmMobile\Dto\Robot;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class RobotProperties extends Dto
{
	/** @var int|null */
	public $categoryId;

	/** @var string|null */
	public $stageId;

	public function getCasts(): array
	{
		return [
			'categoryId' => Type::int(),
			'stageId' => Type::string(),
		];
	}
}