<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

// @todo add counters too
final class StageCounter extends Dto
{
	/** @var int|null */
	public ?int $id;

	/** @var int|null */
	public ?int $count;

	/** @var int|null */
	public ?int $total;

	/** @var string|null */
	public ?string $currency;

	/** @var boolean|null */
	public ?bool $dropzone;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'count' => Type::int(),
			'total' => Type::int(),
			'currency' => Type::string(),
			'dropzone' => Type::bool(),
		];
	}
}
