<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Main\Entity\EntityInterface;

class BaseInfo implements EntityInterface
{
	public function __construct(
		public readonly int $userId,
		public readonly string $fullName,
		public readonly UserRole $userRole,
		public readonly InvitationStatus $invitationStatus,
		public readonly ?int $photoId = null
	)
	{}

	public function getId(): int
	{
		return $this->userId;
	}

	public function getPhoto(int $size = 100): string
	{
		$userPhoto = \Bitrix\Intranet\Component\UserProfile::getUserPhoto(
			$this->photoId,
			$size,
		);

		return is_string($userPhoto) ? $userPhoto : '';
	}
}
