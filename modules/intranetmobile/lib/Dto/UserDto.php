<?php

namespace Bitrix\IntranetMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

class UserDto extends Dto
{
	public const NOT_REGISTERED = 0;
	public const INVITED = 1;
	public const INVITE_AWAITING_APPROVE = 2;
	public const ACTIVE = 3;
	public const FIRED = 4;

	public function __construct(
		public readonly int $id,
		public readonly ?array $department = null,
		public readonly ?bool $isExtranetUser = null,
		public readonly ?InstalledAppsDto $installedApps = null,
		public readonly ?int $employeeStatus = null,
		public readonly ?string $dateRegister = null,
		public readonly ?array $actions = null,
		public readonly ?PhoneNumberDto $phoneNumber = null,
		public readonly ?string $email = null,
	)
	{
		parent::__construct();
	}
}