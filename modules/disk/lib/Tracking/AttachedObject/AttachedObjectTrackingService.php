<?php

declare(strict_types=1);

namespace Bitrix\Disk\Tracking\AttachedObject;

use Bitrix\Disk\AttachedObject;

class AttachedObjectTrackingService
{
	public function trackView(int $userId, string $fileUniqueCode, int $attachedObjectId): void
	{
	}

	/**
	 * @param string $fileUniqueCode
	 * @return AttachedObject[]
	 */
	public function getRecentlyViewed(string $fileUniqueCode): array
	{
		return [];
	}

	public function getPopular(string $fileUniqueCode): array
	{
		return [];
	}

	public function trackAccessByLink(string $fileUniqueCode, int $attachedObjectId): void
	{
	}

	public function getLastAccessedByLink(int $userId, string $fileUniqueCode): ?AttachedObject
	{
		return null;
	}
}