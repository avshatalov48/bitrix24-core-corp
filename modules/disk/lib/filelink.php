<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Internals\EditSessionTable;
use Bitrix\Disk\Internals\Entity\ModelSynchronizer;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\Internals\TrackedObjectTable;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Disk;

Loc::loadMessages(__FILE__);

final class FileLink extends File
{
	const ERROR_COULD_NOT_COPY_LINK = 'DISK_FILE_LINK_22002';

	protected function setAttributes(array $attributes, array &$aliases = null)
	{
		$object = parent::setAttributes($attributes, $aliases);

		if($attributes)
		{
			ModelSynchronizer::getInstance()->subscribeOnRealObject($this);
		}

		return $object;
	}

	/**
	 * Checks rights to change rights on current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canChangeRights(SecurityContext $securityContext)
	{
		return $securityContext->canChangeRights($this->realObjectId);
	}

	/**
	 * Checks rights to read current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canRead(SecurityContext $securityContext)
	{
		return $securityContext->canRead($this->realObjectId);
	}

	/**
	 * Checks rights to share current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canShare(SecurityContext $securityContext)
	{
		return $securityContext->canShare($this->realObjectId);
	}

	/**
	 * Checks rights to update (content) current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canUpdate(SecurityContext $securityContext)
	{
		return $securityContext->canUpdate($this->realObjectId);
	}

	/**
	 * Checks rights to start bizprocess on current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canStartBizProc(SecurityContext $securityContext)
	{
		return $securityContext->canStartBizProc($this->realObjectId);
	}

	/**
	 * Checks rights to lock (content) the object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canLock(SecurityContext $securityContext)
	{
		return $securityContext->canUpdate($this->realObjectId);
	}

	/**
	 * Checks rights to unlock (content) the object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canUnlock(SecurityContext $securityContext)
	{
		return $securityContext->canUpdate($this->realObjectId);
	}

	/**
	 * Adds row to entity table, fills error collection and builds model.
	 * @param array           $data Data.
	 * @param ErrorCollection $errorCollection Error collection.
	 * @return \Bitrix\Disk\Internals\Model|static|null
	 * @throws \Bitrix\Main\NotImplementedException
	 * @internal
	 */
	public static function add(array $data, ErrorCollection $errorCollection)
	{
		$result = FileTable::add($data);
		if (!$result->isSuccess())
		{
			$errorCollection->addFromResult($result);
			return null;
		}

		$file = static::buildFromResult($result);
		if($file && $file->getCreatedBy() && !$file->isDeleted())
		{
			$driver = Driver::getInstance();

			$driver->sendChangeStatusToSubscribers($file, 'quick');
			$storage = $file->getStorage();
			if ($storage->isUseInternalRights())
			{
				$userId = $file->getCreatedBy();
				if ($storage->getProxyType() instanceof ProxyType\User)
				{
					$userId = $storage->getEntityId();
				}

				$driver->getRecentlyUsedManager()->push(
					$userId,
					$file
				);
			}
		}

		if($file)
		{
			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterAddFileLink", array($file));
			$event->send();
		}

		return $file;
	}


	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return array_merge(
			parent::getErrors(),
			$this->getRealObject()? $this->getRealObject()->getErrors() : array()
		);
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return array_merge(
			parent::getErrorsByCode($code),
			$this->getRealObject()? $this->getRealObject()->getErrorsByCode($code) : array()
		);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return parent::getErrorByCode($code)?: $this->getRealObject()->getErrorByCode($code);
	}

	/**
	 * Returns real object of object.
	 *
	 * For example if object is link (@see FolderLink, @see FileLink), then method returns original object.
	 * @return FileLink
	 * @throws ObjectException
	 */
	public function getRealObject()
	{
		$realObject = parent::getRealObject();
		if($realObject === null)
		{
			return null;
		}
		if(!$realObject instanceof File)
		{
			throw new ObjectException('Wrong value in realObjectId, which do not specifies subclass of File');
		}

		return $realObject;
	}

	/**
	 * Returns id of file (table {b_file}).
	 * @return int
	 */
	public function getFileId()
	{
		$file = $this->getRealObject();

		return $file? $file->getFileId() : null;
	}

	/**
	 * Returns view id of file (table {b_file}).
	 * @return int
	 */
	public function getViewId()
	{
		$file = $this->getRealObject();

		return $file? $file->getViewId() : null;
	}

	/**
	 * Returns preview id of file (table {b_file}).
	 * @return int
	 */
	public function getPreviewId()
	{
		$file = $this->getRealObject();

		return $file? $file->getPreviewId() : null;
	}

	/**
	 * Returns file (@see CFile::getById());
	 * @return array|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getFile()
	{
		$file = $this->getRealObject();

		return $file? $file->getFile() : null;
	}

	/**
	 * Copies object to target folder.
	 * @param Folder $targetFolder Target folder.
	 * @param int    $updatedBy Id of user.
	 * @param bool   $generateUniqueName Generates unique name for object in directory.
	 * @return BaseObject|null
	 */
	public function copyTo(Folder $targetFolder, $updatedBy, $generateUniqueName = false)
	{
		$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_LINK_MODEL_ERROR_COULD_NOT_COPY_LINK'), self::ERROR_COULD_NOT_COPY_LINK)));
		return null;
	}

	/**
	 * Updates file content.
	 *
	 * Runs index file, updates all FileLinks, sends notify to subscribers.
	 *
	 * @param array $file Structure like $_FILES.
	 * @param int $updatedBy Id of user.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function updateContent(array $file, $updatedBy)
	{
		return $this->getRealObject()->updateContent($file, $updatedBy);
	}

	/**
	 * Adds new version to file.
	 *
	 * The method may joins version with last version.
	 *
	 * @param array $file Structure like $_FILES.
	 * @param int $createdBy Id of user.
	 * @param bool $disableJoin If set false the method attempts to join version with last version (@see \Bitrix\Disk\File::SECONDS_TO_JOIN_VERSION).
	 * @return Version|null
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addVersion(array $file, $createdBy, $disableJoin = false, array $options = [])
	{
		return $this->getRealObject()->addVersion($file, $createdBy, $disableJoin, $options);
	}

	/**
	 * Returns version of file by version id.
	 * @param int $versionId Id of version.
	 * @return static
	 */
	public function getVersion($versionId)
	{
		$file = $this->getRealObject();
		if (!$file)
		{
			return null;
		}

		return $this->getRealObject()->getVersion($versionId);
	}

	/**
	 * Returns all versions by file.
	 * @param array $parameters Parameters.
	 * @return Version[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public function getVersions(array $parameters = array())
	{
		$file = $this->getRealObject();
		if (!$file)
		{
			return [];
		}

		return $this->getRealObject()->getVersions($parameters);
	}

	/**
	 * Restores file from the version.
	 *
	 * The method is similar with (@see Bitrix\Disk\File::addVersion()).
	 *
	 * @param Version $version Version which need to restore.
	 * @param int $createdBy Id of user.
	 * @return bool
	 */
	public function restoreFromVersion(Version $version, $createdBy)
	{
		return $this->getRealObject()->restoreFromVersion($version, $createdBy);
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function markDeleted($deletedBy)
	{
		return $this->delete($deletedBy);
	}

	/**
	 * Deletes file and all connected data and entities (@see Sharing, @see Rights, etc).
	 * @param int $deletedBy Id of user.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function delete($deletedBy)
	{
		return $this->deleteProcess($deletedBy, true);
	}

	/**
	 * Deletes file and all connected data and entities without sharing.(@see Rights, etc).
	 * @param int $deletedBy Id of user.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function deleteWithoutSharing($deletedBy)
	{
		return $this->deleteProcess($deletedBy, false);
	}

	protected function deleteProcess($deletedBy, $withDeletingSharing = true)
	{
		$this->errorCollection->clear();

		$success = EditSessionTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		Document\OnlyOffice\Models\DocumentSessionTable::deleteBatch([
			'OBJECT_ID' => $this->id,
		]);

		TrackedObjectTable::deleteBatch([
			'OBJECT_ID' => $this->id,
		]);

		$success = ExternalLinkTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if($withDeletingSharing)
		{
			foreach($this->getSharingsAsLink() as $sharing)
			{
				$sharing->delete($deletedBy, false);
			}
			//with status unreplied, declined (not approved)
			$success = SharingTable::deleteByFilter(array(
				'REAL_OBJECT_ID' => $this->id,
			));
		}

		if(!$success)
		{
			return false;
		}

		SimpleRightTable::deleteBatch(array('OBJECT_ID' => $this->id));

		$success = RightTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		Driver::getInstance()->getDeletedLogManager()->mark($this, $deletedBy);

		$resultDelete = FileTable::delete($this->id);
		if(!$resultDelete->isSuccess())
		{
			return false;
		}

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterDeleteFile", array($this->getId(), $deletedBy, array(
            'STORAGE_ID' => $this->getStorageId(),
        )));
		$event->send();

		return true;
	}

	/**
	 * Restores object under link and destroy itself.
	 * It's special case. @see \Bitrix\Disk\BaseObject::markDeletedInternal.
	 * There we put file/folder in original trash can and in trash can of user, who delete the object.
	 *
	 * @param int $restoredBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function restore($restoredBy)
	{
		if (!$this->isDeleted())
		{
			return true;
		}

		$status = $this->restoreByLinkObject($restoredBy);
		if (!$status)
		{
			if ($this->errorCollection->getErrorByCode(self::ERROR_RESTORE_UNDER_LINK_WRONG_TYPE))
			{
				$this->errorCollection->clear();

				return parent::restore($restoredBy);
			}
		}

		return $status;
	}

	public function onModelSynchronize(array $attributes)
	{
		$this->setAttributes($attributes);
	}

	public function __destruct()
	{
		ModelSynchronizer::getInstance()->unsubscribe($this);
	}
}