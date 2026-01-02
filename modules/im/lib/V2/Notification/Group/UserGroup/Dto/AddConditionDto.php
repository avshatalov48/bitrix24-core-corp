<?php

namespace Bitrix\Im\V2\Notification\Group\UserGroup\Dto;

use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\NotEmpty;

class AddConditionDto
{
	public function __construct(
		#[Length(min: 1, max: 255)]
		public string $module,
		#[Length(min: 1, max: 255)]
		public string $event,
		#[NotEmpty]
		public int $userId,
	) {}
}