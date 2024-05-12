<?php

namespace Bitrix\CalendarMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

final class UserInfo extends Dto
{
	/** @var int $id */
	public $id;

	/** @var string $name */
	public $name;

	/** @var string $avatar */
	public $avatar;

	public function getCasts(): array
	{
		return [
			'id' => Type::int(),
			'name' => Type::string(),
			'avatar' => Type::string(),
		];
	}
}