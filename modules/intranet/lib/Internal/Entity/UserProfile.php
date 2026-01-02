<?php

namespace Bitrix\Intranet\Internal\Entity;

use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Intranet\Internal\Entity\Collection\UserFieldSectionCollection;
use Bitrix\Main\Entity\EntityInterface;

class UserProfile implements EntityInterface
{
	public function __construct(
		public readonly int $userId,
		public readonly string $fullName,
		public readonly UserRole $userRole,
		public readonly InvitationStatus $invitationStatus,
		public readonly UserFieldSectionCollection $fieldSectionCollection,
		public readonly ?int $photoId = null,
	)
	{}

	public function getId(): int
	{
		return $this->userId;
	}
}
