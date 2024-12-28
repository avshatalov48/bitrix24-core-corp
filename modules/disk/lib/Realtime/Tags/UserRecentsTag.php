<?php
declare(strict_types=1);

namespace Bitrix\Disk\Realtime\Tags;

final class UserRecentsTag extends Tag
{
	public function __construct(private readonly int $userId)
	{
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getName(): string
	{
		return "disk_user_{$this->getUserId()}_recents";
	}
}