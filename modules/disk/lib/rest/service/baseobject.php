<?php


namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Disk;

abstract class BaseObject extends Base
{
	/**
	 * Returns work-object by id.
	 * @param int $id Id of object.
	 * @return Disk\File|Disk\Folder
	 */
	abstract protected function getWorkObjectById($id);

	/**
	 * Returns object by id.
	 * @param int $id Id of object.
	 * @return Disk\File|Disk\Folder
	 * @throws AccessException
	 */
	protected function get($id)
	{
		$object = $this->getWorkObjectById($id);
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			throw new AccessException;
		}

		return $object;
	}

	/**
	 * Renames object.
	 * @param int    $id      Id of object.
	 * @param string $newName New name for object.
	 * @return Disk\File|Disk\Folder|null
	 * @throws AccessException
	 */
	protected function rename($id, $newName)
	{
		$object = $this->getWorkObjectById($id);
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRename($securityContext))
		{
			throw new AccessException;
		}
		if(!$object->rename($newName))
		{
			$this->errorCollection->add($object->getErrors());
			return null;
		}

		return $object;
	}

	/**
	 * Copies object to target folder.
	 * @param int $id             Id of object.
	 * @param int $targetFolderId Id of target folder.
	 * @return Disk\File|Disk\Folder|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function copyTo($id, $targetFolderId)
	{
		$object = $this->getWorkObjectById($id);
		$targetFolder = $this->getFolderById($targetFolderId);

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		$targetSecurityContext = $targetFolder->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext) || !$targetFolder->canAdd($targetSecurityContext))
		{
			throw new AccessException;
		}

		$newFile = $object->copyTo($targetFolder, $this->userId);
		if(!$newFile)
		{
			$this->errorCollection->add($object->getErrors());
			return null;
		}

		return $newFile;
	}

	/**
	 * Moves object to target folder.
	 * @param int $id             Id of object.
	 * @param int $targetFolderId Id of target folder.
	 * @return Disk\File|Disk\Folder|bool|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function moveTo($id, $targetFolderId)
	{
		$object = $this->getWorkObjectById($id);
		$targetFolder = $this->getFolderById($targetFolderId);

		if($object->getStorageId() != $targetFolder->getStorageId())
		{
			$this->errorCollection->addOne(new Disk\Internals\Error\Error('Could not move object to another storage'));
			return false;
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canMove($securityContext, $targetFolder))
		{
			throw new AccessException;
		}
		if(!$object->getParentId())
		{
			throw new RestException('Could not move root folder.');
		}
		if(!$object->moveTo($targetFolder, $this->userId))
		{
			$this->errorCollection->add($object->getErrors());
			return null;
		}

		return $object;
	}

	/**
	 * Marks deleted object (moves in trash).
	 * @param int $id Id of object.
	 * @return Disk\File|Disk\Folder|null
	 * @throws AccessException
	 * @throws RestException
	 */
	protected function markDeleted($id)
	{
		$object = $this->getWorkObjectById($id);
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canMarkDeleted($securityContext))
		{
			throw new AccessException;
		}
		if(!$object->getParentId())
		{
			throw new RestException('Could not delete root folder.');
		}
		if(!$object->markDeleted($this->userId))
		{
			$this->errorCollection->add($object->getErrors());
			return null;
		}

		return $object;
	}

	/**
	 * Restores object from trash.
	 * @param int $id Id of object.
	 * @return Disk\File|Disk\Folder|null
	 * @throws AccessException
	 */
	protected function restore($id)
	{
		$object = $this->getWorkObjectById($id);
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRestore($securityContext))
		{
			throw new AccessException;
		}
		if(!$object->restore($this->userId))
		{
			$this->errorCollection->add($object->getErrors());
			return null;
		}

		return $object;
	}

	/**
	 * Returns new or existent external link for current user on the file or folder.
	 * @param int $id Id of file or folder.
	 * @return null|string
	 * @throws AccessException
	 */
	protected function getExternalLink($id)
	{
		$object = $this->get($id);

		$extLinks = $object->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $object->getId(),
				'CREATED_BY' => $this->userId,
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));
		$extModel = array_pop($extLinks);
		if(!$extModel)
		{
			$extModel = $object->addExternalLink(array(
				'CREATED_BY' => $this->userId,
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
			));
		}
		if(!$extModel)
		{
			$this->errorCollection->add($object->getErrors());

			return null;
		}

		return Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
			'hash' => $extModel->getHash(),
			'action' => 'default',
		), true);
	}

	/**
	 * Returns file by id.
	 * @param int $id Id of object.
	 * @return Disk\File
	 * @throws RestException
	 */
	protected function getFileById($id)
	{
		$folder = Disk\File::getById($id, array('STORAGE'));
		if(!$folder)
		{
			throw new RestException("Could not find entity with id '{$id}'.", RestException::ERROR_NOT_FOUND);
		}

		return $folder;
	}

	/**
	 * Returns folder by id.
	 * @param int $id Id of object.
	 * @return Disk\Folder
	 * @throws RestException
	 */
	protected function getFolderById($id)
	{
		$folder = Disk\Folder::getById($id, array('STORAGE'));
		if(!$folder)
		{
			throw new RestException("Could not find entity with id '{$id}'.", RestException::ERROR_NOT_FOUND);
		}

		return $folder;
	}

}