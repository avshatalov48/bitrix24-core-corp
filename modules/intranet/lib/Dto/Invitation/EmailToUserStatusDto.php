<?php

namespace Bitrix\Intranet\Dto\Invitation;

class EmailToUserStatusDto implements \JsonSerializable
{
	public function __construct(
		public readonly string $email,
		public readonly string $inviteStatus,
		public readonly bool $isValidEmail,
		public readonly ?int $userId,
	)
	{
	}

	public function jsonSerialize(): array
	{
		return [
			'email' => $this->email,
			'inviteStatus' => $this->inviteStatus,
			'isValidEmail' => $this->isValidEmail,
			'userId' => $this->userId,
		];
	}
}
