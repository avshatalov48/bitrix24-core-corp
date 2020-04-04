<?php


namespace Bitrix\Disk;

use Bitrix\Disk\Document;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\ShowSessionTable;
use Bitrix\Main\Type\DateTime;

final class ShowSession extends Internals\Model
{
	/** @var int */
	protected $objectId;
	/** @var File */
	protected $object;
	/** @var int */
	protected $versionId;
	/** @var Version */
	protected $version;
	/** @var int */
	protected $userId;
	/** @var  User */
	protected $user;
	/** @var int */
	protected $ownerId;
	/** @var  User */
	protected $owner;
	/** @var string */
	protected $service;
	/** @var string */
	protected $serviceFileId;
	/** @var string */
	protected $serviceFileLink;
	/** @var string */
	protected $etag;
	/** @var DateTime */
	protected $createTime;

	/**
	 * Gets the fully qualified name of table class which belongs to current model.
	 * @throws \Bitrix\Main\NotImplementedException
	 * @return string
	 */
	public static function getTableClassName()
	{
		return ShowSessionTable::className();
	}

	/**
	 * Returns create time.
	 *
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	/**
	 * Returns etag. It's not necessary field.
	 *
	 * @return string
	 */
	public function getEtag()
	{
		return $this->etag;
	}

	/**
	 * Returns object.
	 *
	 * @return File|null
	 */
	public function getObject()
	{
		if(!$this->objectId)
		{
			return null;
		}

		if(isset($this->object) && $this->objectId == $this->object->getId())
		{
			return $this->object;
		}
		$this->object = File::loadById($this->objectId);

		return $this->object;
	}

	/**
	 * Returns object id.
	 *
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * Returns version id.
	 *
	 * @return int
	 */
	public function getVersionId()
	{
		return $this->versionId;
	}

	/**
	 * Returns version.
	 *
	 * @return Version|null
	 */
	public function getVersion()
	{
		if(!$this->versionId)
		{
			return null;
		}

		if(isset($this->version) && $this->versionId == $this->version->getId())
		{
			return $this->version;
		}
		$this->version = Version::loadById($this->versionId);

		return $this->version;
	}

	/**
	 * Returns owner.
	 *
	 * @return User|null
	 */
	public function getOwner()
	{
		if(!$this->ownerId)
		{
			return null;
		}

		if(isset($this->owner) && $this->ownerId == $this->owner->getId())
		{
			return $this->owner;
		}
		$this->owner = User::loadById($this->ownerId);

		return $this->owner;
	}

	/**
	 * Returns owner id.
	 *
	 * @return int
	 */
	public function getOwnerId()
	{
		return $this->ownerId;
	}

	/**
	 * Returns service.
	 *
	 * @return string
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * Returns file id.
	 *
	 * @return string
	 */
	public function getServiceFileId()
	{
		return $this->serviceFileId;
	}

	/**
	 * Returns service file link.
	 *
	 * @return string
	 */
	public function getServiceFileLink()
	{
		return $this->serviceFileLink;
	}

	/**
	 * Returns user.
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if(!$this->userId)
		{
			return null;
		}

		if(isset($this->user) && $this->userId == $this->user->getId())
		{
			return $this->user;
		}
		$this->user = User::loadById($this->userId);

		return $this->user;
	}

	/**
	 * Returns user id.
	 *
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Returns the list of pair for mapping data and object properties.
	 * Key is field in DataManager, value is object property.
	 *
	 * @return array
	 */
	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'VERSION_ID' => 'versionId',
			'VERSION' => 'version',
			'USER_ID' => 'userId',
			'USER' => 'user',
			'OWNER_ID' => 'ownerId',
			'OWNER' => 'owner',
			'SERVICE' => 'service',
			'SERVICE_FILE_ID' => 'serviceFileId',
			'SERVICE_FILE_LINK' => 'serviceFileLink',
			'ETAG' => 'etag',
			'CREATE_TIME' => 'createTime',
		);
	}

	/**
	 * Returns the list attributes which is connected with another models.
	 *
	 * @return array
	 */
	public static function getMapReferenceAttributes()
	{
		$userClassName = User::className();
		$fields = User::getFieldsForSelect();

		return array(
			'OBJECT' => File::className(),
			'VERSION' => Version::className(),
			'USER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
			'OWNER' => array(
				'class' => $userClassName,
				'select' => $fields,
			),
		);
	}

	/**
	 * Registers show session by document handler and the file.
	 *
	 * @param Document\DocumentHandler $handler
	 * @param Document\FileData        $fileData
	 * @param ErrorCollection          $errorCollection
	 * @return Internals\Model|null|static
	 */
	public static function register(Document\DocumentHandler $handler, Document\FileData $fileData, ErrorCollection $errorCollection)
	{
		$objectId = $versionId = null;

		if($fileData->getVersion())
		{
			$objectId = $fileData->getVersion()->getObjectId();
			$versionId = $fileData->getVersion()->getId();
		}
		elseif($fileData->getFile())
		{
			$objectId = $fileData->getFile()->getId();
		}
		$metaData = $fileData->getMetaData();

		return static::add(
			array(
				'OBJECT_ID' => $objectId,
				'VERSION_ID' => $versionId,
				'USER_ID' => $handler->getUserId(),
				'OWNER_ID' => $handler->getUserId(),
				'SERVICE' => $handler->getCode(),
				'SERVICE_FILE_ID' => $fileData->getId(),
				'SERVICE_FILE_LINK' => $fileData->getLinkInService(),
				'ETAG' => !empty($metaData['etag'])? $metaData['etag'] : '',
			),
			$errorCollection
		);
	}

	/**
	 * Deletes show session and tries to delete file in the cloud.
	 *
	 * @return bool
	 */
	public function delete()
	{
		$documentHandlersManager = Driver::getInstance()->getDocumentHandlersManager();
		$documentHandler = $documentHandlersManager->getHandlerByCode($this->service);

		if(!$documentHandler)
		{
			$this->errorCollection->add($documentHandlersManager->getErrors());

			return false;
		}

		$documentHandler->setUserId($this->ownerId);
		if(!$documentHandler->queryAccessToken()->hasAccessToken())
		{
			$this->errorCollection[] = new Error('Could not get token for user.');

			return false;
		}

		$fileData = new Document\FileData;
		$fileData->setId($this->serviceFileId);

		$fileMetadata = $documentHandler->getFileMetadata($fileData);
		if(!$fileMetadata)
		{
			if($documentHandler->getErrorByCode($documentHandler::ERROR_CODE_NOT_FOUND))
			{
				return $this->deleteInternal();
			}

			$this->errorCollection->add($documentHandler->getErrors());

			return false;
		}

		$oldMetadata = array();
		if($this->etag)
		{
			$oldMetadata = array(
				'etag' => $this->etag,
			);
		}
		if($documentHandler->wasChangedAfterCreation($fileMetadata, $oldMetadata))
		{
			//$this->errorCollection[] = new Error('File in the cloud was changed.');

			return $this->deleteInternal();
		}

		if($documentHandler->deleteFile($fileData))
		{
			return $this->deleteInternal();
		}

		return false;
	}
}