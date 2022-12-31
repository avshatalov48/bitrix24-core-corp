<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Tunnel extends Dto
{
	/** @var int|null */
	public $dstStageId;

	/** @var string|null */
	public $dstStageStatusId;

	/** @var string|null */
	public $dstStageName;

	/** @var string|null */
	public $dstStageColor;

	/** @var int|null */
	public $dstCategoryId;

	/** @var string|null */
	public $dstCategoryName;

	/** @var int|null */
	public $srcStageId;

	/** @var string|null */
	public $srcStageStatusId;

	/** @var int|null */
	public $srcCategoryId;

	/** @var string|null */
	public $srcStageColor;

	/** @var Robot\Robot|null */
	public $robot;

	public function getCasts(): array
	{
		return [
			'dstStageId' => Type::int(),
			'dstStageStatusId' => Type::string(),
			'dstStageName' => Type::string(),
			'dstStageColor' => Type::string(),
			'dstCategoryId' => Type::int(),
			'dstCategoryName' => Type::string(),
			'srcStageId' => Type::int(),
			'srcStageStatusId' => Type::string(),
			'srcCategoryId' => Type::int(),
			'srcStageColor' => Type::string(),
			'robot' => Type::object(Robot\Robot::class),
		];
	}
}