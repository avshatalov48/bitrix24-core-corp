<?php

namespace Bitrix\Mobile\Collab\Dto;

class CollabSettingsUserDto
{
	public function __construct(
		public int $id,
		public ?string $firstName = null,
		public ?string $lastName = null,
		public ?string $fullName = null,
	)
	{
	}
}