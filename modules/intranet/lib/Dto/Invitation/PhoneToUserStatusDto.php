<?php

namespace Bitrix\Intranet\Dto\Invitation;

class PhoneToUserStatusDto implements \JsonSerializable
{
	public function __construct(
		public readonly string $phone,
		public readonly ?string $countryCode,
		public readonly string $inviteStatus,
		public readonly bool $isValidPhoneNumber,
		public readonly ?string $formattedPhone,
		public readonly ?int $userId,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'phone' => $this->phone,
			'countryCode' => $this->countryCode,
			'inviteStatus' => $this->inviteStatus,
			'isValidPhoneNumber' => $this->isValidPhoneNumber,
			'formattedPhone' => $this->formattedPhone,
			'userId' => $this->userId,
		];
	}
}
