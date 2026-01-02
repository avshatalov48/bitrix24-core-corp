<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\AttachedObject;
use Bitrix\Main\Engine\CurrentUser;

final class AccessCheckHandlerFactory
{
	/** @var array<string, ChainableAccessCheckHandler> */
	private array $cacheForAuthorizedUser = [];
	private ?ExternalLinkAccessCheckHandler $externalLinkAccessCheckHandler = null;

	public function create(?AttachedObject $attachedObject = null, int $userId = 0): AccessCheckHandler
	{
		if ($userId === 0)
		{
			$userId = (int)CurrentUser::get()->getId();
		}

		if ($userId > 0)
		{
			$cacheKey = $this->getCacheKey($attachedObject, $userId);

			return $this->cacheForAuthorizedUser[$cacheKey] ??= $this->createForAuthorizedUser($userId, $attachedObject);
		}

		return $this->externalLinkAccessCheckHandler ??= new ExternalLinkAccessCheckHandler();
	}

	private function createForAuthorizedUser(int $userId, ?AttachedObject $attachedObject = null): ChainableAccessCheckHandler
	{
		$unifiedLinkAccessCheckHandler = new UnifiedLinkAccessCheckHandler($userId);
		$attachedObjectsAccessCheckHandler = new AttachedObjectsAccessCheckHandler($userId, $attachedObject);
		$permissionSystemAccessCheckHandler = new PermissionSystemAccessCheckHandler($userId);

		return (new ExternalLinkAccessCheckHandler())
			->setNext($unifiedLinkAccessCheckHandler
				->setNext($permissionSystemAccessCheckHandler
					->setNext($attachedObjectsAccessCheckHandler),
				),
			)
		;
	}

	private function getCacheKey(?AttachedObject $attachedObject = null, int $userId = 0): string
	{
		$attachedObjectId = (int)$attachedObject?->getId();

		return 'attached_object_' . $attachedObjectId . '_user_' . $userId;
	}
}
