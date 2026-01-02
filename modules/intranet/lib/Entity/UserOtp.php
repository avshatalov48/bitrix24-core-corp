<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity;

use Bitrix\Main\Type\DateTime;

class UserOtp
{
	public function __construct(
		public int $userId,
		public bool $isActive,
		public ?DateTime $dateDeactivate = null,
	) {}

	public static function initByArray(array $userOtpData): static
	{
		return new static(
			userId: $userOtpData['userId'],
			isActive: $userOtpData['isActive'],
			dateDeactivate: $userOtpData['dateDeactivate'] ?? null,
		);
	}

	public function toArray(): array
	{
		return [
			'userId' => $this->userId,
			'isActive' => $this->isActive,
			'dateDeactivate' => $this->dateDeactivate,
		];
	}
}
