<?php

namespace Bitrix\CrmMobile\Dto;

use Bitrix\Mobile\Dto\Type;
use Bitrix\Mobile\Dto\Dto;

final class Stage extends Dto
{
	/** @var int|null */
	public $id;

	/** @var string|null */
	public $name;

	/** @var int|null */
	public $sort;

	/** @var string|null */
	public $color;

	/** @var string|null */
	public $semantics;

	/** @var string|null */
	public $statusId;

	/** @var Tunnel[]|null */
	public $tunnels = [];

	/** @var int|null */
	public $count;

	/** @var string|null */
	public $currency;

	/** @var string|null */
	public $total;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'name' => Type::string(),
			'sort' => Type::int(),
			'color' => Type::string(),
			'semantics' => Type::string(),
			'statusId' => Type::string(),
			'tunnels' => Type::collection(Tunnel::class),
			'count' => Type::int(),
			'currency' => Type::string(),
			'total' => Type::float(),
		];
	}
}
