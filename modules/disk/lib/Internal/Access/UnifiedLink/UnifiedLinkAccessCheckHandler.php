<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\File;
use Bitrix\Disk\Integration\Collab\CollabService;
use Bitrix\Disk\Internal\Service\UnifiedLink\Configuration;
use Bitrix\Disk\Internal\Repository\UnifiedLinkAccessRepository;
use Bitrix\Disk\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

class UnifiedLinkAccessCheckHandler extends ChainableAccessCheckHandler
{
	private CollabService $collabService;

	public function __construct(
		private readonly int $userId,
	)
	{
		$this->collabService = new CollabService();
	}
	
	protected function doCheck(File $file): UnifiedLinkAccessLevel
	{
		$unifiedLinkAccessLevel = UnifiedLinkAccessRepository::getByObjectId((int)$file->getId());
		$checkAccessByLink = $unifiedLinkAccessLevel ?? Configuration::getDefaultAccessLevel();

		$user = User::loadById($this->userId);

		if ($user->isIntranetUser())
		{
			return $checkAccessByLink;
		}

		if ($user->isCollaber() && $this->isUserMemberOfCollab($file))
		{
			return $checkAccessByLink;
		}

		return UnifiedLinkAccessLevel::Denied;
	}

	/**
	 * @param File $file
	 * @return bool
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function isUserMemberOfCollab(File $file): bool
	{
		$collab = $this->collabService->getCollabByStorage($file->getStorage());

		return $collab && in_array($this->userId, $collab->getUserMemberIds(), true);
	}
}