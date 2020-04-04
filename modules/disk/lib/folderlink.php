<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Entity\ModelSynchronizer;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;

Loc::loadMessages(__FILE__);

final class FolderLink extends Folder
{
	const ERROR_COULD_NOT_COPY_LINK = 'DISK_FOLDER_LINK_22002';

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
	 * Checks rights to add object to current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canAdd(SecurityContext $securityContext)
	{
		return $securityContext->canAdd($this->realObjectId);
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
	 * Pre-loads all operations for children.
	 * @internal
	 * @param SecurityContext $securityContext Security context.
	 */
	public function preloadOperationsForChildren(SecurityContext $securityContext)
	{
		$securityContext->preloadOperationsForChildren($this->realObjectId);
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return array_merge(parent::getErrors(), $this->getRealObject()->getErrors());
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return array_merge(parent::getErrorsByCode($code), $this->getRealObject()->getErrorsByCode($code));
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
	 * @return Folder
	 * @throws ObjectException
	 */
	public function getRealObject()
	{
		$realObject = parent::getRealObject();
		if($realObject === null)
		{
			return null;
		}
		if(!$realObject instanceof Folder)
		{
			throw new ObjectException('Wrong value in realObjectId, which do not specifies subclass of Folder');
		}

		return $realObject;
	}

	/**
	 * Uploads new file to folder.
	 * @param array $fileArray Structure like $_FILES.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return File|null
	 */
	public function uploadFile(array $fileArray, array $data, array $rights = array(), $generateUniqueName = false)
	{
		if(!$this->getRealObject())
		{
			return null;
		}

		return $this->getRealObject()->uploadFile($fileArray, $data, $rights, $generateUniqueName);
	}

	/**
	 * Adds file in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, MIME_TYPE).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return null|static|File
	 */
	public function addFile(array $data, array $rights = array(), $generateUniqueName = false)
	{
		if(!$this->getRealObject())
		{
			return null;
		}

		return $this->getRealObject()->addFile($data, $rights, $generateUniqueName);
	}

	/**
	 * Adds link on file in folder.
	 * @param File  $sourceFile Source file.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, MIME_TYPE).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return File|null
	 */
	public function addFileLink(File $sourceFile, array $data, array $rights = array(), $generateUniqueName = false)
	{
		if(!$this->getRealObject())
		{
			return null;
		}

		return $this->getRealObject()->addFileLink($sourceFile, $data, $rights, $generateUniqueName);
	}

	/**
	 * Adds sub-folder in folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return null|static|Folder
	 */
	public function addSubFolder(array $data, array $rights = array(), $generateUniqueName = false)
	{
		if(!$this->getRealObject())
		{
			return null;
		}

		return $this->getRealObject()->addSubFolder($data, $rights, $generateUniqueName);
	}

	/**
	 * Adds link on folder in folder.
	 * @param Folder $sourceFolder Original folder.
	 * @param array $data Contains additional fields (CREATED_BY, NAME, etc).
	 * @param array $rights Rights (@see \Bitrix\Disk\RightsManager).
	 * @param bool  $generateUniqueName Generates unique name for object in directory.
	 * @throws \Bitrix\Main\ArgumentException
	 * @return FolderLink|null
	 */
	public function addSubFolderLink(Folder $sourceFolder, array $data, array $rights = array(), $generateUniqueName = false)
	{
		if(!$this->getRealObject())
		{
			return null;
		}

		return $this->getRealObject()->addSubFolderLink($sourceFolder, $data, $rights, $generateUniqueName);
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
		$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LINK_MODEL_ERROR_COULD_NOT_COPY_LINK'), self::ERROR_COULD_NOT_COPY_LINK)));
		return null;
	}

	/**
	 * @param Folder $targetFolder
	 * @param        $updatedBy
	 * @param bool   $generateUniqueName
	 * @return null
	 */
	protected function copyToInternal(Folder $targetFolder, $updatedBy, $generateUniqueName = false)
	{
		$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FOLDER_LINK_MODEL_ERROR_COULD_NOT_COPY_LINK'), self::ERROR_COULD_NOT_COPY_LINK)));
		return null;
	}

	/**
	 * Gets all descendants objects by the folder.
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @param int             $orderDepthLevel Order for depth level (default asc).
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @return BaseObject[]
	 */
	public function getDescendants(SecurityContext $securityContext, array $parameters = array(),
	                               $orderDepthLevel = SORT_ASC)
	{
		if(!$this->getRealObject())
		{
			return array();
		}

		return $this->getRealObject()->getDescendants($securityContext, $parameters, $orderDepthLevel);
	}

	/**
	 * Gets direct children (files, folders).
	 * @param SecurityContext $securityContext Security context.
	 * @param array           $parameters Parameters.
	 * @return BaseObject[]
	 */
	public function getChildren(SecurityContext $securityContext, array $parameters = array())
	{
		if(!$this->getRealObject())
		{
			return array();
		}

		return $this->getRealObject()->getChildren($securityContext, $parameters);
	}

	/**
	 * Deletes folder and all descendants objects.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @return bool
	 */
	public function deleteTree($deletedBy)
	{
		/** @var Sharing $sharing */
		$sharing = Sharing::load(array(
			'LINK_OBJECT_ID' => $this->getId(),
		));
		if($sharing)
		{
			$sharing->setAttributes(array('LINK_OBJECT' => $this));
			$sharing->decline($deletedBy, false);
		}

		return parent::deleteNonRecursive($deletedBy);
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function markDeleted($deletedBy)
	{
		return $this->deleteTree($deletedBy);
	}

	/**
	 * Restores object from trash can.
	 * @param int $restoredBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function restore($restoredBy)
	{
		if (!$this->isDeleted())
		{
			return true;
		}

		$this->errorCollection->clear();

		$status = $this->restoreByLinkObject($restoredBy);
		if (!$status)
		{
			if ($this->errorCollection->getErrorByCode(self::ERROR_RESTORE_UNDER_LINK_WRONG_TYPE))
			{
				$this->errorCollection->clear();

				$needRecalculate = $this->deletedType == ObjectTable::DELETED_TYPE_CHILD;
				$status = parent::restoreNonRecursive($restoredBy);
				if($status && $needRecalculate)
				{
					$this->recalculateDeletedTypeAfterRestore($restoredBy);
				}

				if($status)
				{
					Driver::getInstance()->sendChangeStatusToSubscribers($this);
				}
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