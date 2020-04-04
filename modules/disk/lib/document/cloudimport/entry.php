<?php

namespace Bitrix\Disk\Document\CloudImport;


use Bitrix\Disk\Internals\CloudImportTable;
use Bitrix\Disk\Internals\Model;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\User;
use Bitrix\Disk\Version;
use Bitrix\Main\Type\DateTime;

class Entry extends Model
{
	/** @var int */
	protected $id;
	/** @var int */
	protected $objectId;
	/** @var BaseObject */
	protected $object;
	/** @var int */
	protected $versionId;
	/** @var Version */
	protected $version;
	/** @var int */
	protected $tmpFileId;
	/** @var TmpFile */
	protected $tmpFile;
	/** @var int */
	protected $downloadedContentSize;
	/** @var int */
	protected $contentSize;
	/** @var string */
	protected $contentUrl;
	/** @var string */
	protected $mimeType;
	/** @var int */
	protected $userId;
	/** @var User */
	protected $user;
	/** @var string */
	protected $service;
	/** @var string */
	protected $serviceObjectId;
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
		return CloudImportTable::className();
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getObjectId()
	{
		return $this->objectId;
	}

	/**
	 * @return BaseObject
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
		$this->object = BaseObject::loadById($this->objectId);

		return $this->object;
	}

	/**
	 * @return int
	 */
	public function getVersionId()
	{
		return $this->versionId;
	}

	/**
	 * @return Version
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
	 * @return int
	 */
	public function getTmpFileId()
	{
		return $this->tmpFileId;
	}

	/**
	 * @return TmpFile
	 */
	public function getTmpFile()
	{
		if(!$this->tmpFileId)
		{
			return null;
		}

		if(isset($this->tmpFile) && $this->tmpFileId == $this->tmpFile->getId())
		{
			return $this->tmpFile;
		}
		$this->tmpFile = TmpFile::loadById($this->tmpFileId);

		return $this->tmpFile;
	}

	/**
	 * @return int
	 */
	public function getDownloadedContentSize()
	{
		return $this->downloadedContentSize;
	}

	/**
	 * @return int
	 */
	public function getContentSize()
	{
		return $this->contentSize;
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * @return string
	 */
	public function getContentUrl()
	{
		return $this->contentUrl;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @return User
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
	 * @return string
	 */
	public function getService()
	{
		return $this->service;
	}

	/**
	 * @return string
	 */
	public function getServiceObjectId()
	{
		return $this->serviceObjectId;
	}

	/**
	 * @return string
	 */
	public function getEtag()
	{
		return $this->etag;
	}

	/**
	 * @return DateTime
	 */
	public function getCreateTime()
	{
		return $this->createTime;
	}

	public function isDownloaded()
	{
		return $this->getDownloadedContentSize() == $this->getContentSize();
	}

	public function increaseDownloadedContentSize($size)
	{
		return $this->update(array(
			'DOWNLOADED_CONTENT_SIZE' => $this->getDownloadedContentSize() + $size,
		));
	}

	public function setContentSize($size)
	{
		return $this->update(array(
			'CONTENT_SIZE' => $size,
		));
	}

	public function linkTmpFile(TmpFile $tmpFile)
	{
		$update = $this->update(array(
			'TMP_FILE_ID' => $tmpFile->getId(),
		));
		if($update)
		{
			$this->setAttributes(array(
				'TMP_FILE' => $tmpFile,
			));
		}

		return $update;
	}

	public function linkObject(BaseObject $object)
	{
		$update = $this->update(array(
			'OBJECT_ID' => $object->getId(),
		));
		if($update)
		{
			$this->setAttributes(array(
				'OBJECT' => $object,
			));
		}

		return $update;
	}

	public function linkVersion(Version $version)
	{
		$update = $this->update(array(
			'OBJECT_ID' => $version->getObjectId(),
			'VERSION_ID' => $version->getId(),
		));
		if($update)
		{
			$this->setAttributes(array(
				'VERSION' => $version,
				'OBJECT' => $version->getObject(),
			));
		}

		return $update;
	}

	public static function getMapAttributes()
	{
		return array(
			'ID' => 'id',
			'OBJECT_ID' => 'objectId',
			'OBJECT' => 'object',
			'VERSION_ID' => 'versionId',
			'VERSION' => 'version',
			'TMP_FILE_ID' => 'tmpFileId',
			'TMP_FILE' => 'tmpFile',
			'DOWNLOADED_CONTENT_SIZE' => 'downloadedContentSize',
			'CONTENT_SIZE' => 'contentSize',
			'CONTENT_URL' => 'contentUrl',
			'MIME_TYPE' => 'mimeType',
			'USER_ID' => 'userId',
			'USER' => 'user',
			'SERVICE' => 'service',
			'SERVICE_OBJECT_ID' => 'serviceObjectId',
			'ETAG' => 'etag',
			'CREATE_TIME' => 'createTime',
		);
	}

	public static function getMapReferenceAttributes()
	{
		return array(
			'OBJECT' => BaseObject::className(),
			'VERSION' => Version::className(),
			'TMP_FILE' => TmpFile::className(),
			'USER' => array(
				'class' => User::className(),
				'select' => User::getFieldsForSelect(),
			),
		);
	}
}