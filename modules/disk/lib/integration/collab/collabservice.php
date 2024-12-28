<?php
declare(strict_types=1);

namespace Bitrix\Disk\Integration\Collab;

use Bitrix\Disk\Driver;
use Bitrix\Disk\ProxyType\Group;
use Bitrix\Disk\Storage;
use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Collab\Collab;
use Bitrix\Socialnetwork\Collab\Entity\CollabEntityFactory;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;
use Bitrix\Socialnetwork\Collab\Registry\CollabRegistry;
use Psr\Container\NotFoundExceptionInterface;

final class CollabService
{
	public function __construct()
	{
	}

	/**
	 * Returns true if the user is a collaboration user.
	 * @param int $userId User ID.
	 * @return bool
	 * @throws LoaderException
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public function isCollaberUserById(int $userId): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			return false;
		}

		return ServiceContainer::getInstance()->getCollaberService()->isCollaberById($userId);
	}

	/**
	 * Returns true if the storage is a collaboration storage.
	 * @param Storage $storage
	 * @return bool
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isCollabStorage(Storage $storage): bool
	{
		return $this->isCollabStorageById((int)$storage->getId());
	}

	/**
	 * Returns true if the storage is a group storage.
	 * @param Storage $storage
	 * @return bool
	 */
	public function isGroupStorage(Storage $storage): bool
	{
		return $storage->getProxyType() instanceof Group;
	}

	/**
	 * Returns true if the storage is a collaboration storage.
	 * @param int $storageId Storage ID.
	 * @return bool
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws NotImplementedException
	 */
	public function isCollabStorageById(int $storageId): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$storage = Storage::getById($storageId);
		if ($storage === null)
		{
			return false;
		}

		if (!$this->isGroupStorage($storage))
		{
			return false;
		}

		$groupId = (int)$storage->getEntityId();

		return CollabProvider::getInstance()->isCollab($groupId);
	}

	/**
	 * Returns the collaboration entity by the storage.
	 * @param Storage $storage Storage.
	 * @return Collab|null
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getCollabByStorage(Storage $storage): ?Collab
	{
		if (!$this->isGroupStorage($storage))
		{
			return null;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$groupId = (int)$storage->getEntityId();

		return CollabRegistry::getInstance()->get($groupId);
	}

	/**
	 * Returns true if the entity is a collaboration entity.
	 * @param int $entityId Entity ID (Userfields).
	 * @param string $entityType Entity type (Userfields).
	 * @return bool
	 */
	public function isCollabEntity(int $entityId, string $entityType): bool
	{
		return $this->getCollabStorageByEntity($entityId, $entityType) !== null;
	}

	/**
	 * Returns the collaboration storage by the entity.
	 * @param int $entityId Entity ID (Userfields).
	 * @param string $entityType Entity type (Userfields).
	 * @return Storage|null
	 * @throws LoaderException
	 */
	public function getCollabStorageByEntity(int $entityId, string $entityType): ?Storage
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$entity = CollabEntityFactory::getById($entityId, $entityType);
		if ($entity === null)
		{
			return null;
		}

		$collab = $entity->getCollab();

		return Driver::getInstance()->getStorageByGroupId($collab->getId());
	}
}