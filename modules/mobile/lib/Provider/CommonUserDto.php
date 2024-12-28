<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Mobile\Dto\Dto;

final class CommonUserDto extends Dto
{
	public function __construct(
		public int $id,
		public ?string $login = null,
		public ?string $name = null,
		public ?string $lastName = null,
		public ?string $secondName = null,
		public ?string $fullName = null,
		public ?string $email = null,
		public ?string $workPhone = null,
		public ?string $workPosition = null,
		public ?string $link = null,
		public ?string $avatarSizeOriginal = null,
		public ?string $avatarSize100 = null,
		public ?bool $isAdmin = null,
		public ?bool $isCollaber = null,
		public ?bool $isExtranet = null,
		public ?string $personalMobile = null,
		public ?string $personalPhone = null,
	)
	{
		parent::__construct();
	}
}
