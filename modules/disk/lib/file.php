<?php

namespace Bitrix\Disk;

use Bitrix\Disk;
use Bitrix\Disk\Document\Online\UserInfoToken;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Disk\Internals\EditSessionTable;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\RightTable;
use Bitrix\Disk\Internals\SharingTable;
use Bitrix\Disk\Internals\SimpleRightTable;
use Bitrix\Disk\Internals\TrackedObjectTable;
use Bitrix\Disk\Internals\VersionTable;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Viewer\FilePreviewTable;
use CFile;

Loc::loadMessages(__FILE__);

class File extends BaseObject
{
	const ERROR_COULD_NOT_SAVE_FILE           = 'DISK_FILE_22002';
	const ERROR_COULD_NOT_COPY_FILE           = 'DISK_FILE_22003';
	const ERROR_COULD_NOT_RESTORE_FROM_OBJECT = 'DISK_FILE_22004';
	const ERROR_COULD_NOT_GET_SAVED_FILE      = 'DISK_FILE_22005';
	const ERROR_SIZE_RESTRICTION              = 'DISK_FILE_22006';
	const ERROR_EXCLUSIVE_LOCK                = 'DISK_FILE_22007';
	const ERROR_ALREADY_LOCKED                = 'DISK_FILE_22008';
	const ERROR_INVALID_LOCK_TOKEN            = 'DISK_FILE_22009';
	const ERROR_INVALID_USER_FOR_UNLOCK       = 'DISK_FILE_22010';

	const SECONDS_TO_JOIN_VERSION = 300;

	const STATE_DO_NOTHING     = 2;
	const STATE_DELETE_PROCESS = 3;

	const CODE_RECORDED_FILE = 'RECORDED';

	/** @var int */
	protected $typeFile;
	/** @var int */
	protected $globalContentVersion;
	/** @var int */
	protected $fileId;
	/** @var int */
	protected $prevFileId;
	/** @var array */
	protected $file;
	/** @var int */
	protected $size;
	/** @var string */
	protected $externalHash;
	/** @var string */
	protected $etag;
	/** @var string */
	protected $extension;
	/** @var int */
	protected $currentState = self::STATE_DO_NOTHING;
	/** @var  int */
	protected $previewId;
	/** @var  int */
	protected $viewId;
	/** @var int */
	protected $prevViewId;
	/** @var View\Base */
	protected $view;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return FileTable::className();
	}

	/**
	 * Checks rights to start bizprocess on current object.
	 * @param SecurityContext $securityContext Security context.
	 * @return bool
	 */
	public function canStartBizProc(SecurityContext $securityContext)
	{
		return $securityContext->canStartBizProc($this->id);
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
		$parent = null;
		if(isset($data['PARENT']) && $data['PARENT'] instanceof Folder)
		{
			$parent = $data['PARENT'];
			unset($data['PARENT']);
		}

		/** @var File $file */
		$file = parent::add($data, $errorCollection);
		if($file)
		{
			if($parent !== null)
			{
				$file->setAttributes(array('PARENT' => $parent));
			}

			$versionData = array(
				'ID' => $file->getFileId(),
				'FILE_SIZE' => $file->getSize(),
			);

			if(!empty($data['UPDATE_TIME']))
			{
				$versionData['UPDATE_TIME'] = $data['UPDATE_TIME'];
			}
			if(!empty($data['ETAG']))
			{
				$versionData['ETAG'] = $data['ETAG'];
			}

			$version = $file->addVersion($versionData, $file->getCreatedBy());
			if(!$version)
			{
				$errorCollection->add($file->getErrors());
				$file->delete(SystemUser::SYSTEM_USER_ID);
				return null;
			}

			$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterAddFile", array($file));
			$event->send();
		}

		return $file;
	}

	/**
	 * Returns once model by specific filter.
	 * @param array $filter Filter.
	 * @param array $with List of eager loading.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return static
	 */
	public static function load(array $filter, array $with = array())
	{
		$filter['TYPE'] = ObjectTable::TYPE_FILE;

		return parent::load($filter, $with);
	}

	protected static function getClassNameModel(array $row)
	{
		$classNameModel = parent::getClassNameModel($row);
		if(
			$classNameModel === static::className() ||
			is_subclass_of($classNameModel, static::className()) ||
			in_array(static::className(), class_parents($classNameModel)) //5.3.9
		)
		{
			return $classNameModel;
		}

		throw new ObjectException('Could not to get non subclass of ' . static::className());
	}

	/**
	 * Returns extension.
	 * @return string
	 */
	public function getExtension()
	{
		if($this->extension === null)
		{
			$this->extension = getFileExtension($this->getName());
		}
		return $this->extension;
	}

	/**
	 * Returns external hash.
	 * @return string
	 */
	public function getExternalHash()
	{
		return $this->externalHash;
	}

	/**
	 * Returns etag.
	 * @return string
	 */
	public function getEtag()
	{
		return $this->etag;
	}

	/**
	 * Changes etag.
	 *
	 * @param string $etag Entity tag.
	 * @return bool
	 */
	public function changeEtag($etag)
	{
		return $this->update(array('ETAG' => $etag));
	}

	/**
	 * Returns global content version.
	 *
	 * Version always increments after creating new version.
	 * @return int
	 */
	public function getGlobalContentVersion()
	{
		return $this->globalContentVersion;
	}

	/**
	 * Returns id of file (table {b_file}).
	 * @return int
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * Returns file (@see CFile::getById());
	 * @return array|null
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getFile()
	{
		if(!$this->fileId)
		{
			return null;
		}

		if(isset($this->file) && $this->fileId == $this->file['ID'])
		{
			return $this->file;
		}

		$this->file = \CFile::GetFileArray($this->fileId);

		if(!$this->file)
		{
			return array();
		}

		return $this->file;
	}

	/**
	 * Returns id of preview image.
	 * @return int
	 * @deprecated
	 */
	public function getPreviewId()
	{
		return $this->previewId;
	}

	/**
	 * Set previewId, save in the database.
	 *
	 * @param int $fileId
	 * @return bool
	 * @deprecated
	 */
	public function changePreviewId($fileId)
	{
		return $this->update(array('PREVIEW_ID' => $fileId));
	}

	/**
	 * Returns id of view.
	 * @return int|null
	 * @deprecated
	 */
	public function getViewId()
	{
		return $this->viewId;
	}

	/**
	 * Set viewId, save in the database.
	 *
	 * @param int $fileId
	 * @return bool
	 * @deprecated
	 */
	public function changeViewId($fileId)
	{
		return $this->update(array('VIEW_ID' => $fileId));
	}

	/**
	 * Delete converted view file.
	 *
	 * @return bool
	 * @deprecated
	 */
	public function deleteViewId()
	{
		if($this->viewId > 0)
		{
			\CFile::Delete($this->viewId);
			return $this->update(array('VIEW_ID' => null));
		}

		return false;
	}

	/**
	 * Returns size in bytes.
	 *
	 * @param null $filter
	 *
	 * @return int
	 */
	public function getSize($filter = null)
	{
		return $this->size;
	}

	/**
	 * Returns type of file.
	 * @see TypeFile class for details.
	 * @return int
	 */
	public function getTypeFile()
	{
		return $this->typeFile;
	}

	/**
	 * Renames object.
	 * @param string $newName New name.
	 * @return bool
	 */
	public function rename($newName, bool $generateUniqueName = false)
	{
		$result = parent::rename($newName, $generateUniqueName);
		if($result)
		{
			$this->extension = null;
		}
		return $result;
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
		$this->errorCollection->clear();

		$forkFileId = \CFile::CloneFile($this->getFileId());
		if (!$forkFileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_COPY_FILE'), self::ERROR_COULD_NOT_COPY_FILE)));
			return null;
		}

		$fileModel = $targetFolder->addFile([
			'NAME' => $this->getName(),
			'FILE_ID' => $forkFileId,
			'ETAG' => $this->getEtag(),
			'SIZE' => $this->getSize(),
			'CREATED_BY' => $updatedBy,
		], [], $generateUniqueName);
		if (!$fileModel)
		{
			\CFile::Delete($forkFileId);
			$this->errorCollection->add($targetFolder->getErrors());

			return null;
		}

		return $fileModel;
	}

	/**
	 * Increases global content version.
	 * @return bool
	 */
	public function increaseGlobalContentVersion()
	{
		//todo inc in DB by expression
		$success = $this->update(array(
			'GLOBAL_CONTENT_VERSION' => (int)$this->getGlobalContentVersion() + 1,
		));

		if(!$success)
		{
			return false;
		}

		$this->updateLinksAttributes(array(
			'GLOBAL_CONTENT_VERSION' => $this->getGlobalContentVersion(),
		));

		return $success;
	}

	/**
	 * Returns object lock model.
	 *
	 * @return ObjectLock|null
	 */
	public function getLock()
	{
		if ($this->isLoadedAttribute('lock'))
		{
			return $this->lock;
		}

		$lock = ObjectLock::load(['OBJECT_ID' => $this->getRealObjectId()]);
		$this->lock = $lock;
		$this->setAsLoadedAttribute('lock');

		if ($lock && $lock->shouldProcessAutoUnlock())
		{
			$this->unlock(SystemUser::SYSTEM_USER_ID);
			$this->lock = null;
		}

		return $this->lock;
	}

	/**
	 * Locks file.
	 *
	 * @param int $lockedBy User which locks object.
	 * @param string $token Token, if is not set, then run auto generate.
	 * @param array $data Data.
	 * @return ObjectLock
	 */
	public function lock($lockedBy, $token = null, array $data = array())
	{
		if($token === null)
		{
			$token = ObjectLock::generateLockToken();
		}

		//now we work only with exclusive locks.
		$objectLock = $this->getLock();
		if($objectLock)
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FILE_MODEL_ERROR_ALREADY_LOCKED'),
				self::ERROR_ALREADY_LOCKED
			);
			return null;
		}

		$lock = ObjectLock::add(array_merge($data, array(
			'TOKEN' => $token,
			'OBJECT_ID' => $this->getRealObjectId(),
			'CREATED_BY' => $lockedBy,
		)), $this->errorCollection);

		if($lock)
		{
			$this->update(array('SYNC_UPDATE_TIME' => new DateTime()));
			Driver::getInstance()->sendChangeStatusToSubscribers($this, 'quick');
		}

		return $lock;
	}

	/**
	 * Unlocks file.
	 *
	 * @param int $unlockedBy User which unlocks object.
	 * @param string $token Token.
	 * @return bool
	 */
	public function unlock($unlockedBy, $token = null)
	{
		$objectLock = $this->getLock();
		if (!$objectLock)
		{
			return true;
		}

		if (!$objectLock->canUnlock($unlockedBy))
		{
			$this->errorCollection[] = $this->generateUnlockErrorByAnotherUser($objectLock);

			return false;
		}

		if ($token !== null && $objectLock->getToken() !== $token)
		{
			$this->errorCollection[] = new Error(
				Loc::getMessage('DISK_FILE_MODEL_ERROR_INVALID_LOCK_TOKEN'),
				self::ERROR_INVALID_LOCK_TOKEN
			);

			return false;
		}

		$success = $objectLock->delete($unlockedBy);
		if ($success)
		{
			$this->update(array('SYNC_UPDATE_TIME' => new DateTime()));
			Driver::getInstance()->sendChangeStatusToSubscribers($this, 'quick');
		}

		return $success;
	}

	public function generateUnlockErrorByAnotherUser(ObjectLock $objectLock): Error
	{
		$createLockUser = $objectLock->getCreateUser();
		if ($createLockUser)
		{
			return new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_INVALID_USER_FOR_UNLOCK_2', [
				'#USER#' => "<a href='{$createLockUser->getDetailUrl()}'>" . htmlspecialcharsbx($createLockUser->getFormattedName()) . "</a>",
			]), self::ERROR_INVALID_USER_FOR_UNLOCK);
		}

		return new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_INVALID_USER_FOR_UNLOCK'), self::ERROR_INVALID_USER_FOR_UNLOCK);
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
		$this->errorCollection->clear();

		static::checkRequiredInputParams($file, array(
			'ID', 'FILE_SIZE'
		));

		$objectLock = null;
		if(Configuration::isEnabledObjectLock())
		{
			$objectLock = $this->getLock();
			if($objectLock && $objectLock->isExclusive() && !$objectLock->canUnlock($updatedBy))
			{
				$this->errorCollection[] = new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_EXCLUSIVE_LOCK'), self::ERROR_EXCLUSIVE_LOCK);
				return false;
			}
		}

		//todo inc in DB by expression
		$updateData = array(
			'GLOBAL_CONTENT_VERSION' => (int)$this->getGlobalContentVersion() + 1,
			'FILE_ID' => $file['ID'],
			'ETAG' => empty($file['ETAG'])? $this->generateEtag() : $file['ETAG'], //each time we update etag field. It is random string
			'SIZE' => $file['FILE_SIZE'],
			'UPDATE_TIME' => empty($file['UPDATE_TIME'])? new DateTime() : $file['UPDATE_TIME'],
			'UPDATED_BY' => $updatedBy,
		);

		$this->prevFileId = $this->fileId;
		$success = $this->update($updateData);

		if(!$success)
		{
			$this->prevFileId = null;
			return false;
		}

		$this->changeParentUpdateTime(new DateTime(), $updatedBy);

		if ($objectLock instanceof ObjectLock && Configuration::shouldAutoUnlockObjectOnSave())
		{
			$this->unlock($updatedBy);
		}

		$this->updateLinksAttributes(array(
			'ETAG' => $this->getEtag(),
			'GLOBAL_CONTENT_VERSION' => $this->getGlobalContentVersion(),
			'SIZE' => $file['FILE_SIZE'],
			'UPDATE_TIME' => $this->getUpdateTime(),
			'SYNC_UPDATE_TIME' => $this->getSyncUpdateTime(),
			'UPDATED_BY' => $updatedBy,
		));
		$this->updateRelated();

		$driver = Driver::getInstance();
		if ($this->getStorage()->isUseInternalRights())
		{
			$driver->getRecentlyUsedManager()->push(
				$updatedBy,
				$this
			);
		}

		if ($this->getGlobalContentVersion() <= 1)
		{
			//initial full index
			$driver->getIndexManager()->indexFile($this);
		}
		else
		{
			//just update content
			$driver->getIndexManager()->updateFileContent($this);
		}

		$objectEvent = $this->makeObjectEvent(
			'contentUpdated',
			[
				'object' => [
					'id' => (int)$this->getId(),
					'name' => $this->getName(),
					'updatedBy' => (int)$this->getUpdatedBy(),
				],
				'updatedBy' => [
					'infoToken' => UserInfoToken::generateTimeLimitedToken($this->getUpdatedBy(), $this->getId()),
				]
			]
		);
		$objectEvent->sendToObjectChannel();

		//todo little hack...We don't synchronize file in folder with uploaded files. And we have not to send notify by pull
		if($this->parent === null || $this->parent && $this->parent->getCode() !== Folder::CODE_FOR_UPLOADED_FILES)
		{
			$driver->sendChangeStatusToSubscribers($this, 'quick');

			$updatedBy = $this->getUpdatedBy();
			if($updatedBy)
			{
				$driver->sendEvent($updatedBy, 'live', array(
					'objectId' => $this->getId(),
					'action' => 'commit',
					'contentVersion' => (int)$this->getGlobalContentVersion(),
					'size' => (int)$this->getSize(),
					'formatSize' => (string)CFile::formatSize($this->getSize()),
				));
			}
		}

		return true;
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
		$this->errorCollection->clear();

		$commentAttachedObjects = $options['commentAttachedObjects'] ?? true;

		if(Configuration::isEnabledStorageSizeRestriction())
		{
			static::checkRequiredInputParams($file, array(
				'FILE_SIZE'
			));

			if($this->errorCollection->hasErrors())
			{
				return null;
			}

			if(!$this->getStorage()->isPossibleToUpload($file['FILE_SIZE']))
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('DISK_FILE_MODEL_ERROR_SIZE_RESTRICTION'), self::ERROR_SIZE_RESTRICTION
				);

				return null;
			}
		}

		$needToJoin = !$disableJoin && $this->isNeedToJoinVersion($createdBy);
		if(!$this->updateContent($file, $createdBy))
		{
			return null;
		}

		if($needToJoin)
		{
			$lastVersion = $this->joinVersion();
			if ($lastVersion)
			{
				$this->tryToRunBizProcAfterEdit();

				return $lastVersion;
			}
		}

		$versionModel = Version::add(array_merge(array(
			'OBJECT_ID' => $this->id,
			'FILE_ID' => $this->fileId,
			'NAME' => $this->name,
			'CREATED_BY' => $createdBy,
		), $this->getHistoricalData()), $this->errorCollection);

		if(!$versionModel)
		{
			return null;
		}

		$this->cleanVersionsOverLimit($createdBy);
		if ($commentAttachedObjects)
		{
			$this->commentAttachedObjects($versionModel);
		}
		$this->resetHeadVersionToAttachedObject($versionModel);

		if ($this->getGlobalContentVersion() == 1)
		{
			$this->tryToRunBizProcAfterCreate();
		}
		else
		{
			$this->tryToRunBizProcAfterEdit();
		}

		Application::getInstance()->getTaggedCache()->clearByTag("disk_file_{$this->id}");

		return $versionModel;
	}

	private function generateEtag()
	{
		return md5(mt_rand() . mt_rand());
	}

	private function tryToRunBizProcAfterCreate()
	{
		if (!Integration\BizProcManager::isAvailable())
		{
			return;
		}

		$storage = $this->getStorage();
		if($storage && $this->getStorage()->isEnabledBizProc())
		{
			BizProcDocument::runAfterCreate($this->storageId, $this->id);
		}
	}

	private function tryToRunBizProcAfterEdit()
	{
		if (!Integration\BizProcManager::isAvailable())
		{
			return;
		}

		$storage = $this->getStorage();
		if($storage && $this->getStorage()->isEnabledBizProc())
		{
			BizProcDocument::runAfterEdit($this->storageId, $this->id);
		}
	}

	public function commentAttachedObjects(Version $version): void
	{
		$createdBy = $version->getCreatedBy();
		$valueVersionUf = FileUserType::NEW_FILE_PREFIX . $version->getId();

		/** @var User $createUser */
		$createUser = User::loadById($createdBy);
		if (!$createUser)
		{
			return;
		}

		$text = $this->getTextForComment($createUser);
		$attachedObjects = $this->getAttachedObjects([
			'filter' => [
				'=ALLOW_AUTO_COMMENT' => 1,
			],
		]);
		foreach ($attachedObjects as $attachedObject)
		{
			if (!$attachedObject->getAllowAutoComment())
			{
				continue;
			}

			AttachedObject::storeDataByObjectId($this->getId(), [
				'IS_EDITABLE' => $attachedObject->isEditable(),
				'ALLOW_EDIT' => $attachedObject->getAllowEdit(),
				'STATE' => 'commentAttachedObjects',
			]);

			$attachedObject->getConnector()->addComment($createdBy, [
				'text' => $text,
				'versionId' => $valueVersionUf,
				'authorGender' => $createUser->getPersonalGenderExact()
			]);

			AttachedObject::storeDataByObjectId($this->getId(), null);
		}
	}

	private function cleanVersionsOverLimit($createdBy)
	{
		$versionLimitPerFile = Configuration::getVersionLimitPerFile();
		if ($this->getGlobalContentVersion() > 1 && $versionLimitPerFile > 0)
		{
			foreach ($this->getVersions(array('offset' => $versionLimitPerFile, 'limit' => 100)) as $oldVersion)
			{
				$oldVersion->delete($createdBy);
			}
		}
	}

	private function joinVersion()
	{
		$lastVersion = $this->getLastVersion();
		if (!$lastVersion)
		{
			return null;
		}

		$joinData = array('CREATE_TIME' => new DateTime);

		if (!$lastVersion->joinData(
			array_merge(
				$joinData,
				$this->getHistoricalData()
			)
		))
		{
			$this->errorCollection->add($lastVersion->getErrors());

			return null;
		}

		if ($this->prevFileId && $this->prevFileId != $this->fileId)
		{
			CFile::delete($this->prevFileId);
		}

		return $lastVersion;
	}

	private function isNeedToJoinVersion($createdBy)
	{
		if(Configuration::getFileVersionTtl() === 0 && !$this->hasAttachedObjects())
		{
			return true;
		}

		$now = new DateTime;
		if($this->updateTime && $this->updatedBy == $createdBy)
		{
			$updateTimestamp = $this->updateTime->getTimestamp();
			if($now->getTimestamp() - $updateTimestamp < self::SECONDS_TO_JOIN_VERSION)
			{
				return true;
			}
		}

		return false;
	}

	private function resetHeadVersionToAttachedObject(Version $version)
	{
		if(Configuration::isEnabledKeepVersion())
		{
			return;
		}

		AttachedObjectTable::updateBatch(
			array(
				'VERSION_ID' => $version->getId(),
			),
			array(
				'OBJECT_ID' => $version->getObjectId(),
				'!=VERSION_ID' => null,
			)
		);
	}

	private function getTextForComment(User $createUser)
	{
		if(!Configuration::isEnabledKeepVersion())
		{
			$text = Loc::getMessage('DISK_FILE_MODEL_UPLOAD_NEW_HEAD_VERSION_IN_COMMENT_M');
			if($createUser->getPersonalGender() == 'F')
			{
				$text = Loc::getMessage('DISK_FILE_MODEL_UPLOAD_NEW_HEAD_VERSION_IN_COMMENT_F');
			}
		}
		else
		{
			$text = Loc::getMessage('DISK_FILE_MODEL_UPLOAD_NEW_VERSION_IN_COMMENT_M');
			if($createUser->getPersonalGender() == 'F')
			{
				$text = Loc::getMessage('DISK_FILE_MODEL_UPLOAD_NEW_VERSION_IN_COMMENT_F');
			}
		}

		return $text;
	}

	/**
	 * Uploads new version to file.
	 * @see \Bitrix\Disk\File::addVersion().
	 * @param array $fileArray Structure like $_FILES.
	 * @param int $createdBy Id of user.
	 * @return Version|null
	 */
	public function uploadVersion(array $fileArray, $createdBy, array $options = [])
	{
		$this->errorCollection->clear();

		if(!isset($fileArray['MODULE_ID']))
		{
			$fileArray['MODULE_ID'] = Driver::INTERNAL_MODULE_ID;
		}

		if(empty($fileArray['type']))
		{
			$fileArray['type'] = '';
		}
		$fileArray['type'] = TypeFile::normalizeMimeType($fileArray['type'], $this->name);


		$fileId = CFile::saveFile($fileArray, Driver::INTERNAL_MODULE_ID, true, true);
		if(!$fileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_SAVE_FILE)));
			return null;
		}
		$updateTime = isset($fileArray['UPDATE_TIME'])? $fileArray['UPDATE_TIME'] : null;
		/** @var array $fileArray */

		$fileArray = CFile::getFileArray($fileId);
		if(!$fileArray)
		{
			CFile::delete($fileId);
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_SAVE_FILE'), self::ERROR_COULD_NOT_GET_SAVED_FILE)));
			return null;
		}
		if($updateTime)
		{
			$fileArray['UPDATE_TIME'] = $updateTime;
		}
		$version = $this->addVersion($fileArray, $createdBy, false, $options);
		if(!$version)
		{
			CFile::delete($fileId);
		}

		return $version;
	}

	/**
	 * Returns version of file by version id.
	 * @param int $versionId Id of version.
	 * @return static
	 */
	public function getVersion($versionId)
	{
		$version = Version::load(array(
			'ID' => $versionId,
			'OBJECT_ID' => $this->id,
		));
		if($version)
		{
			$version->setAttributes(array('OBJECT' => $this));
		}

		return $version;
	}

	/**
	 * Gets last version of the file.
	 * @return Version|null
	 */
	public function getLastVersion()
	{
		$versions = $this->getVersions(array('limit' => 1));
		return array_shift($versions)?: null;
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
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		$parameters['filter']['OBJECT_ID'] = $this->id;

		if(!isset($parameters['order']))
		{
			$parameters['order'] = array(
				'CREATE_TIME' => 'DESC',
			);
		}

		$versions = Version::getModelList($parameters);
		foreach($versions as $version)
		{
			$version->setAttributes(array('OBJECT' => $this));
		}
		unset($version);

		return $versions;
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
		$this->errorCollection->clear();

		if($version->getObjectId() != $this->getRealObjectId())
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_RESTORE_FROM_ANOTHER_OBJECT'), self::ERROR_COULD_NOT_RESTORE_FROM_OBJECT)));

			return false;
		}

		$forkFileId = \CFile::CloneFile($version->getFileId());
		if(!$forkFileId)
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_COULD_NOT_COPY_FILE'), self::ERROR_COULD_NOT_COPY_FILE)));
			return false;
		}


		if($this->addVersion(\CFile::getFileArray($forkFileId), $createdBy, true) === null)
		{
			return false;
		}

		return true;
	}

	/**
	 * Moves object to another folder.
	 * Support cross-storage move (mark deleted + create new)
	 * @param Folder $folder             Destination folder.
	 * @param int    $movedBy            User id of user, which move file.
	 * @param bool   $generateUniqueName Generates unique name for object if in destination directory.
	 * @return BaseObject|null
	 */
	public function moveTo(Folder $folder, $movedBy, $generateUniqueName = false)
	{
		if(Configuration::isEnabledObjectLock())
		{
			$objectLock = $this->getLock();
			if($objectLock && $objectLock->isExclusive() && !$objectLock->canUnlock($movedBy))
			{
				$this->errorCollection[] = new Error(Loc::getMessage('DISK_FILE_MODEL_ERROR_EXCLUSIVE_LOCK'), self::ERROR_EXCLUSIVE_LOCK);
				return null;
			}
		}

		return parent::moveTo($folder, $movedBy, $generateUniqueName);
	}

	/**
	 * Moves file to folder from another storage.
	 * @see moveInAnotherStorage(), moveInSameStorage() to understand logic.
	 * This method is specific and can use nobody. Be careful!
	 *
	 *
	 * @internal
	 * @param Folder $folder             Destination folder.
	 * @param int    $movedBy            User id of user, which move file.
	 * @param bool   $generateUniqueName Generates unique name for object if in destination directory.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function moveToAnotherFolder(Folder $folder, $movedBy, $generateUniqueName = false)
	{
		$realFolderId = $folder->getRealObject()->getId();
		if($this->getParentId() == $realFolderId)
		{
			return true;
		}

		$possibleNewName = $this->name;
		if($generateUniqueName)
		{
			$possibleNewName = static::generateUniqueName($this->name, $realFolderId);
		}
		$needToRename = $possibleNewName != $this->name;

		if(!static::isUniqueName($possibleNewName, $realFolderId))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_OBJECT_MODEL_ERROR_NON_UNIQUE_NAME'), self::ERROR_NON_UNIQUE_NAME)));
			return false;
		}
		$this->name = $possibleNewName;

		if($needToRename)
		{
			$successUpdate = $this->update(array(
				'NAME' => $possibleNewName
			));
			if(!$successUpdate)
			{
				return false;
			}
		}

		/** @var FileTable $tableClassName */
		$tableClassName = $this->getTableClassName();

		$moveResult = $tableClassName::move($this->id, $realFolderId);
		if(!$moveResult->isSuccess())
		{
			$this->errorCollection->addFromResult($moveResult);
			return false;
		}
		$this->setAttributesFromResult($moveResult);

		Driver::getInstance()->getRightsManager()->setAfterMove($this);

		$subscribersAfterMove = Driver::getInstance()->collectSubscribers($this);
		Driver::getInstance()->sendChangeStatus($subscribersAfterMove);

		if($folder->getRealObject()->getStorageId() != $this->storageId)
		{
			$changeStorageIdResult = $tableClassName::changeStorageId($this->id, $folder->getRealObject()->getStorageId());
			if(!$changeStorageIdResult->isSuccess())
			{
				$this->errorCollection->addFromResult($changeStorageIdResult);
				return false;
			}
		}

		$success = $this->update(array(
			'UPDATE_TIME' => new DateTime(),
			'UPDATED_BY' => $movedBy,
		));
		if(!$success)
		{
			return null;
		}

		return $this;
	}

	/**
	 * Marks deleted object. It equals to move in trash can.
	 * @param int $deletedBy Id of user (or SystemUser::SYSTEM_USER_ID).
	 * @return bool
	 */
	public function markDeleted($deletedBy)
	{
		$alreadyDeleted = $this->isDeleted();
		$status = $this->markDeletedInternal($deletedBy);
		if ($status && !$alreadyDeleted)
		{
			$notifyManager = Driver::getInstance()->getDeletionNotifyManager();
			$notifyManager->send();
		}

		return $status;
	}

	/**
	 * Internal method for deleting file as child of folder.
	 * @param int $deletedBy Id of user.
	 * @param int $deletedType Type of delete (@see ObjectTable::DELETED_TYPE_ROOT, ObjectTable::DELETED_TYPE_CHILD)
	 * @return bool
	 * @internal
	 */
	public function markDeletedInternal($deletedBy, $deletedType = ObjectTable::DELETED_TYPE_ROOT)
	{
		$alreadyDeleted = $this->isDeleted();
		$success = parent::markDeletedInternal($deletedBy, $deletedType);
		if ($success && !$alreadyDeleted)
		{
			Driver::getInstance()->getDeletedLogManager()->mark($this, $deletedBy);
		}

		return $success;
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

		$needRecalculate = $this->deletedType == ObjectTable::DELETED_TYPE_CHILD;
		$status = parent::restoreInternal($restoredBy);
		if($status && $needRecalculate)
		{
			$this->recalculateDeletedTypeAfterRestore($restoredBy);
		}
		if($status)
		{
			$driver = Driver::getInstance();
			if ($this->getStorage()->isUseInternalRights())
			{
				$driver->getRecentlyUsedManager()->push(
					$restoredBy,
					$this
				);
			}
			$driver->getIndexManager()->indexFileByModuleSearch($this);
			$driver->sendChangeStatusToSubscribers($this);

			//it's necessary to reset cache because in default way \Bitrix\Disk\Driver::sendChangeStatusToSubscribers
			// doesn't reset tree cache when send notification on the file. But in case when we restore file - we have to.
			//The reason is that we can restore folder when we restore the file.
			$subscribers = Driver::getInstance()->collectSubscribers($this);
			Driver::getInstance()->cleanCacheTreeBitrixDisk(array_keys($subscribers));
		}

		return $status;
	}

	/**
	 * Deletes file and all connected data and entities (@see Sharing, @see Rights, etc).
	 * @param int $deletedBy Id of user.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function delete($deletedBy)
	{
		$this->errorCollection->clear();

		if(Configuration::isEnabledObjectLock())
		{
			$objectLock = $this->getLock();
			if($objectLock && $objectLock->isExclusive() && !$objectLock->canUnlock($deletedBy))
			{
				$this->errorCollection[] = new Error(
					Loc::getMessage('DISK_FILE_MODEL_ERROR_EXCLUSIVE_LOCK'), self::ERROR_EXCLUSIVE_LOCK
				);
				return false;
			}
		}

		$this->currentState = static::STATE_DELETE_PROCESS;

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
			'REAL_OBJECT_ID' => $this->id,
		]);

		$success = ExternalLinkTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		foreach($this->getSharingsAsReal() as $sharing)
		{
			$sharing->delete($deletedBy);
		}

		//with status unreplied, declined (not approved)
		$success = SharingTable::deleteByFilter(array(
			'REAL_OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		foreach($this->getAttachedObjects() as $attached)
		{
			$attached->delete();
		}
		unset($attached);

		if(Integration\BizProcManager::isAvailable())
		{
			BizProcDocument::deleteWorkflowsFile($this->id);
		}

		SimpleRightTable::deleteBatch(array('OBJECT_ID' => $this->id));

		$success = RightTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		$versionQuery = Version::getList(array('filter' => array(
			'OBJECT_ID' => $this->id,
		)));
		while($versionData = $versionQuery->fetch())
		{
			$version = Version::buildFromArray($versionData);
			$version->setAttributes(array('OBJECT' => $this));
			$version->delete($deletedBy);
		}
		unset($version, $versionQuery);

		$success = VersionTable::deleteByFilter(array(
			'OBJECT_ID' => $this->id,
		));

		if(!$success)
		{
			return false;
		}

		if($this->getLock())
		{
			$this->getLock()->delete($deletedBy);
		}

		//it's possible, that object was already deleted. And we don't have to add it to deleted log. It's unnecessary.
		//we add only directly destroyed objects.
		if(!$this->isDeleted())
		{
			Driver::getInstance()->getDeletedLogManager()->mark($this, $deletedBy);
		}

		\CFile::delete($this->fileId);
		$deleteResult = FileTable::delete($this->id);
		if(!$deleteResult->isSuccess())
		{
			return false;
		}
		Driver::getInstance()->getIndexManager()->dropIndex($this);

		Application::getInstance()->getTaggedCache()->clearByTag("disk_file_{$this->id}");

		if(!$this->isLink())
		{
			//todo potential - very hard operation.
			foreach(File::getModelList(array('filter' => array('REAL_OBJECT_ID' => $this->id))) as $link)
			{
				$link->delete($deletedBy);
			}
		}

		$event = new Event(Driver::INTERNAL_MODULE_ID, "onAfterDeleteFile", array($this->getId(), $deletedBy, array(
            'STORAGE_ID' => $this->getStorageId(),
        )));
		$event->send();

		return true;
	}

	/**
	 * Returns current state of the file.
	 *
	 * For example, STATE_DELETE_PROCESS.
	 *
	 * @return int
	 * @internal
	 */
	public function getCurrentState()
	{
		return $this->currentState;
	}

	protected function getHistoricalData()
	{
		return array(
			'FILE_ID' => $this->fileId,
			'SIZE' => $this->size,

			'GLOBAL_CONTENT_VERSION' => $this->globalContentVersion,

			'OBJECT_CREATED_BY' => $this->createdBy,
			'OBJECT_UPDATED_BY' => $this->updatedBy,

			'OBJECT_CREATE_TIME'=> $this->createTime,
			'OBJECT_UPDATE_TIME'=> $this->updateTime,
		);
	}

	/**
	 * Returns all attached objects by the file.
	 * @param array $parameters Parameters.
	 * @return AttachedObject[]
	 */
	public function getAttachedObjects(array $parameters = array())
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = array();
		}
		$parameters['filter']['=OBJECT_ID'] = $this->id;
		$parameters['filter']['=VERSION_ID'] = null;

		return AttachedObject::getModelList($parameters);
	}

	/**
	 * Returns count of attached objects of file.
	 * @return int
	 */
	public function countAttachedObjects()
	{
		$countQuery = new Query(AttachedObjectTable::getEntity());
		$countQuery
			->addSelect(new ExpressionField('CNT', 'COUNT(1)'))
			->setFilter(array(
				'=OBJECT_ID' => $this->id,
				'=VERSION_ID' => null,
			))
		;

		$totalData = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();

		return $totalData['CNT'];
	}

	public function hasAttachedObjects(): bool
	{
		$query = new Query(AttachedObjectTable::getEntity());
		$query
			->addSelect('ID')
			->setFilter([
				'=OBJECT_ID' => $this->id,
			])
			->setLimit(1)
		;

		$data = $query->exec()->fetch();

		return !empty($data['ID']);
	}

	/**
	 * @param array{id: int, type: string} $entity
	 * @param array{allowEdit: bool, isEditable: bool, createdBy: int} $options
	 * @return AttachedObject|null
	 * @throws Main\NotImplementedException
	 */
	final public function attachToEntity(array $entity, array $options): ?AttachedObject
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		[$connectorClass, $moduleId] = $userFieldManager->getConnectorDataByEntityType($entity['type']);

		$errorCollection = new ErrorCollection();
		$attachedObject = Disk\AttachedObject::add([
			'MODULE_ID' => $moduleId,
			'OBJECT_ID' => $this->getId(),
			'ENTITY_ID' => $entity['id'],
			'ENTITY_TYPE' => $connectorClass,
			'IS_EDITABLE' => (int)$options['isEditable'],
			'ALLOW_EDIT' => (int)$options['allowEdit'],
			'CREATED_BY' => $options['createdBy'],
		], $errorCollection);

		if (!$attachedObject)
		{
			$this->errorCollection->add($errorCollection->getValues());
		}

		return $attachedObject;
	}

	protected function updateLinksAttributes(array $attr)
	{
		$possibleToUpdate = array(
			'GLOBAL_CONTENT_VERSION' => 'globalContentVersion',
			'TYPE_FILE' => 'typeFile',
			'SIZE' => 'size',
			'EXTERNAL_HASH' => 'externalHash',
			'ETAG' => 'etag',
			'UPDATE_TIME' => 'updateTime',
			'SYNC_UPDATE_TIME' => 'syncUpdateTime',
			'UPDATED_BY' => 'updatedBy',
			'UPDATE_USER' => 'updateUser',
		);
		$attr = array_intersect_key($attr, $possibleToUpdate);
		if($attr)
		{
			parent::updateLinksAttributes($attr);
		}
	}

	private function updateRelated()
	{
		Disk\Driver::getInstance()->getTrackedObjectManager()->refresh($this);
	}

	/**
	 * Returns the list of pair for mapping.
	 * Key is field in DataManager, value is object property.
	 * @return array
	 */
	public static function getMapAttributes()
	{
		static $shelve = null;
		if($shelve !== null)
		{
			return $shelve;
		}

		$shelve = array_merge(parent::getMapAttributes(), array(
			'TYPE_FILE' => 'typeFile',
			'GLOBAL_CONTENT_VERSION' => 'globalContentVersion',
			'FILE_ID' => 'fileId',
			'SIZE' => 'size',
			'EXTERNAL_HASH' => 'externalHash',
			'PREVIEW_ID' => 'previewId',
			'VIEW_ID' => 'viewId',
			'ETAG' => 'etag',
		));

		return $shelve;
	}

	/**
	 * Return instance of View for current file.
	 *
	 * @return View\Base
	 */
	public function getView()
	{
		if (!$this->view)
		{
			$isTransformationEnabledInStorage = true;
			$storage = $this->getStorage();
			if ($storage)
			{
				$isTransformationEnabledInStorage = $storage->isEnabledTransformation();
			}

			$previewData = FilePreviewTable::getList(['filter' => ['FILE_ID' => $this->getFileId(),],])->fetch();
			$viewId = isset($previewData['PREVIEW_ID'])? $previewData['PREVIEW_ID'] : null;
			$imageId = isset($previewData['PREVIEW_IMAGE_ID'])? $previewData['PREVIEW_IMAGE_ID'] : null;

			if (TypeFile::isDocument($this))
			{
				$this->view = new View\Document($this->getName(), $this->getFileId(), $viewId, $imageId, $isTransformationEnabledInStorage);
			}
			elseif (TypeFile::isVideo($this))
			{
				$this->view = new View\Video($this->getName(), $this->getFileId(), $viewId, $imageId, $isTransformationEnabledInStorage);
			}
			else
			{
				$this->view = new View\Base($this->getName(), $this->getFileId(), $viewId, $imageId, $isTransformationEnabledInStorage);
			}
		}

		return $this->view;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize(): array
	{
		$urlShowObjectInGrid = Driver::getInstance()->getUrlManager()->getUrlFocusController('showObjectInGrid', [
			'objectId' => $this->getId(),
		]);
		$urlShowObjectInGrid = new Main\Web\Uri($urlShowObjectInGrid);

		return array_merge(parent::jsonSerialize(), [
			'typeFile' => (int)$this->getTypeFile(),
			'globalContentVersion' => (int)$this->getGlobalContentVersion(),
			'fileId' => (int)$this->getFileId(),
			'size' => (int)$this->getSize(),
			'etag' => $this->getEtag(),
			'links' => [
				/** @see \Bitrix\Disk\Controller\File::downloadAction() */
				'download' => Main\Engine\UrlManager::getInstance()->create('disk.file.download', ['fileId' => $this->getId()]),
				'showInGrid' => $urlShowObjectInGrid,
			],
		]);
	}
}