<?php

namespace Bitrix\Disk\Document;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Version;
use Bitrix\Main\IO;

class FileData implements IErrorable
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string */
	protected $mimeType;
	/** @var string */
	protected $src;
	/** @var int */
	protected $size;
	/** @var bool */
	protected $needConvert = true;
	/** @var string */
	protected $linkInService;
	/** @var File|null */
	protected $file;
	/** @var Version|null */
	protected $version;
	/** @var array */
	protected $metaData = array();
	/** @var AttachedObject */
	protected $attachedObject;

	/** @var  ErrorCollection */
	protected $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLinkInService()
	{
		return $this->linkInService;
	}

	/**
	 * @param string $linkInService
	 * @return $this
	 */
	public function setLinkInService($linkInService)
	{
		$this->linkInService = $linkInService;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * @param string $mimeType
	 * @return $this
	 */
	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isNeededToConvert()
	{
		return (bool)$this->needConvert;
	}

	/**
	 * @param boolean $needConvert
	 * @return $this
	 */
	public function setNeedConvert($needConvert)
	{
		$this->needConvert = $needConvert;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * @param int $size
	 * @return $this
	 */
	public function setSize($size)
	{
		$this->size = $size;

		return $this;
	}

	/**
	 * @param bool $getFromFileIfPossible
	 * @return string|null
	 */
	public function getSrc($getFromFileIfPossible = true)
	{
		if(!$this->src && $getFromFileIfPossible && $this->file instanceof File)
		{
			$fileArray = \CFile::makeFileArray($this->file->getFileId());
			if(!is_array($fileArray))
			{
				return null;
			}
			$this->src = $fileArray['tmp_name'];
		}
		return $this->src;
	}

	/**
	 * @param string $src
	 * @return $this
	 */
	public function setSrc($src)
	{
		$this->src = $src;

		return $this;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return File|null
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @param File|null $file
	 * @return $this
	 */
	public function setFile($file)
	{
		$this->file = $file;

		return $this;
	}

	/**
	 * @return Version|null
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param Version|null $version
	 * @return $this
	 */
	public function setVersion($version)
	{
		$this->version = $version;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getMetaData()
	{
		return $this->metaData;
	}

	/**
	 * @param array $metaData
	 * @return $this
	 */
	public function setMetaData($metaData)
	{
		$this->metaData = $metaData;

		return $this;
	}

	public function toArray()
	{
		return array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'mimeType' => $this->getMimeType(),
			'src' => $this->getSrc(true),
			'size' => $this->getSize(),
			'needConvert' => $this->isNeededToConvert(),
			'linkInService' => $this->getLinkInService(),
			'file' => $this->getFile(),
			'metaData' => $this->getMetaData(),
		);
	}

	/**
	 * @return AttachedObject
	 */
	public function getAttachedObject()
	{
		return $this->attachedObject;
	}

	/**
	 * @param AttachedObject $attachedObject
	 * @return $this
	 */
	public function setAttachedObject($attachedObject)
	{
		$this->attachedObject = $attachedObject;

		return $this;
	}
}
