<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Integration\Collab\CollabService;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Disk\ProxyType\User;
use Bitrix\Disk\Storage;
use Throwable;

class DiskObject
{
	private CollabService $collabService;

	public function __construct(
		protected readonly BaseObject $diskObject,
	)
	{
		$this->collabService = new CollabService();
	}

	public function isInCollab(): bool
	{
		$storage = $this->diskObject->getStorage();

		try
		{
			return $storage !== null && $this->collabService->isCollabStorage($storage);
		}
		catch (Throwable)
		{
			return false;
		}
	}

	public function getCollabId(): ?int
	{
		$storage = $this->diskObject->getStorage();

		if (isset($storage))
		{
			try
			{
				return $this->collabService->getCollabByStorage($storage)?->getId();
			}
			catch (Throwable)
			{
				return null;
			}
		}

		return null;
	}

	public function getStorage(): ?Storage
	{
		return $this->diskObject->getStorage();
	}

	public function isInProject(): bool
	{
		$isGroupStorage = $this->getStorage()?->getEntityType() === Group::class;

		return $isGroupStorage && !$this->isInCollab();
	}

	public function isPersonal(): bool
	{
		return $this->getStorage()?->getEntityType() === User::class;
	}
}