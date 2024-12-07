<?php

namespace Bitrix\IntranetMobile\Dto;

use Bitrix\Mobile\Dto\Dto;

class PhoneNumberDto extends Dto
{
	public function __construct(
		public readonly string $phoneNumber,
		public readonly string $countryCode = '',
		public readonly string $formattedPhoneNumber = '',
		public readonly bool $isValidPhoneNumber = false,
	)
	{
		parent::__construct();
	}
}