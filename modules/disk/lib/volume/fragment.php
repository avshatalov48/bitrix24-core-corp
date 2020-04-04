<?php

namespace Bitrix\Disk\Volume;


class Fragment
{
	/** @var string */
	private $title = '';

	/** @var string */
	private $indicatorType = '';

	/** @var int */
	private $fileSize = -1;

	/** @var int */
	private $fileCount = -1;

	/** @var int */
	private $diskSize = -1;

	/** @var int */
	private $diskCount = -1;

	/** @var int */
	private $versionCount = -1;

	/** @var int */
	private $previewSize = -1;

	/** @var int */
	private $previewCount = -1;

	/** @var int */
	private $attachedCount = -1;

	/** @var int */
	private $linkCount = -1;

	/** @var int */
	private $sharingCount = -1;

	/** @var int */
	private $unnecessaryVersionSize = -1;

	/** @var int */
	private $unnecessaryVersionCount = -1;

	/** @var array */
	private $specific = array();

	/** @var int */
	private $storageId;

	/** @var \Bitrix\Disk\Storage */
	private $storage;

	/** @var int */
	private $folderId;

	/** @var \Bitrix\Disk\Folder */
	private $folder;

	/** @var int */
	private $fileId;

	/** @var \Bitrix\Disk\File */
	private $file;

	/** @var string */
	private $moduleId;

	/** @var string */
	private $entityType;

	/** @var string */
	private $entityId;

	/**
	 * Finds entity object by filter.
	 * @param string[] $parameters Array filter set to find entity object.
	 */
	public function __construct(array $parameters)
	{
		if (isset($parameters['INDICATOR_TYPE']) && strlen($parameters['INDICATOR_TYPE']) > 0)
		{
			$this->indicatorType = $parameters['INDICATOR_TYPE'];
		}
		if (isset($parameters['STORAGE_ID']) && (int)$parameters['STORAGE_ID'] > 0)
		{
			$this->storageId = $parameters['STORAGE_ID'];
		}
		if (isset($parameters['TITLE']) && strlen($parameters['TITLE']) > 0)
		{
			$this->title = $parameters['TITLE'];
		}
		if (isset($parameters['FOLDER_ID']) && (int)$parameters['FOLDER_ID'] > 0)
		{
			$this->folderId = $parameters['FOLDER_ID'];
		}
		if (isset($parameters['FILE_ID']) && (int)$parameters['FILE_ID'] > 0)
		{
			$this->fileId = $parameters['FILE_ID'];
		}
		if (isset($parameters['MODULE_ID']) && strlen($parameters['MODULE_ID']) > 0)
		{
			$this->moduleId = $parameters['MODULE_ID'];
		}
		if (isset($parameters['ENTITY_TYPE']) && strlen($parameters['ENTITY_TYPE']) > 0)
		{
			$this->entityType = $parameters['ENTITY_TYPE'];
		}
		if (isset($parameters['ENTITY_ID']) && strlen($parameters['ENTITY_ID']) > 0)
		{
			$this->entityId = $parameters['ENTITY_ID'];
		}
		if (isset($parameters['SPECIFIC']))
		{
			$this->specific = $parameters['SPECIFIC'];
		}
		if (isset($parameters['FILE_SIZE']))
		{
			$this->fileSize = $parameters['FILE_SIZE'];
		}
		if (isset($parameters['FILE_COUNT']))
		{
			$this->fileCount = $parameters['FILE_COUNT'];
		}
		if (isset($parameters['DISK_SIZE']))
		{
			$this->diskSize = $parameters['DISK_SIZE'];
		}
		if (isset($parameters['DISK_COUNT']))
		{
			$this->diskCount = $parameters['DISK_COUNT'];
		}
		if (isset($parameters['VERSION_COUNT']))
		{
			$this->versionCount = $parameters['VERSION_COUNT'];
		}
		if (isset($parameters['PREVIEW_SIZE']))
		{
			$this->previewSize = $parameters['PREVIEW_SIZE'];
		}
		if (isset($parameters['PREVIEW_COUNT']))
		{
			$this->previewCount = $parameters['PREVIEW_COUNT'];
		}
		if (isset($parameters['ATTACHED_COUNT']))
		{
			$this->attachedCount = $parameters['ATTACHED_COUNT'];
		}
		if (isset($parameters['LINK_COUNT']))
		{
			$this->linkCount = $parameters['LINK_COUNT'];
		}
		if (isset($parameters['SHARING_COUNT']))
		{
			$this->sharingCount = $parameters['SHARING_COUNT'];
		}
		if (isset($parameters['UNNECESSARY_VERSION_SIZE']))
		{
			$this->unnecessaryVersionSize = $parameters['UNNECESSARY_VERSION_SIZE'];
		}
		if (isset($parameters['UNNECESSARY_VERSION_COUNT']))
		{
			$this->unnecessaryVersionCount = $parameters['UNNECESSARY_VERSION_COUNT'];
		}
	}

	/**
	 * Returns title of the entity object.
	 * @return string
	 */
	public function getTitle()
	{
		/*
		if ($this->title == '')
		{
			/** @var \Bitrix\Disk\Volume\IVolumeIndicator $class * /
			$class = $this->indicatorType;
			$this->title = $class::getTitle($this);
		}
		*/
		return $this->title;
	}

	/**
	 * Returns entity specific corresponding to module.
	 * @return array
	 */
	public function getSpecific()
	{
		return $this->specific;
	}

	/**
	 * Returns type of the entity object.
	 * @return string
	 */
	public function getIndicatorType()
	{
		return $this->indicatorType;
	}

	/**
	 * Returns volume size of objects selecting by filter.
	 * @return integer
	 */
	public function getFileSize()
	{
		return $this->fileSize;
	}

	/**
	 * Returns amount of objects selecting by filter.
	 * @return integer
	 */
	public function getFileCount()
	{
		return $this->fileCount;
	}

	/**
	 * Returns volume size of objects on disk.
	 * @return integer
	 */
	public function getDiskSize()
	{
		return $this->diskSize;
	}

	/**
	 * Returns amount of objects on disk.
	 * @return integer
	 */
	public function getDiskCount()
	{
		return $this->diskCount;
	}

	/**
	 * Returns total amount of objects selecting by filter.
	 * @return integer
	 */
	public function getVersionCount()
	{
		return $this->versionCount;
	}

	/**
	 * Returns volume size of preview files.
	 * @return integer
	 */
	public function getPreviewSize()
	{
		return $this->previewSize;
	}

	/**
	 * Returns amount of preview files.
	 * @return integer
	 */
	public function getPreviewCount()
	{
		return $this->previewCount;
	}

	/**
	 * Returns total amount of attached objects selecting by filter.
	 * @return integer
	 */
	public function getAttachedCount()
	{
		return $this->attachedCount;
	}

	/**
	 * Returns total amount of external links to objects selecting by filter.
	 * @return integer
	 */
	public function getLinkCount()
	{
		return $this->linkCount;
	}
	/**
	 * Returns total number sharing of objects selecting by filter.
	 * @return integer
	 */
	public function getSharingCount()
	{
		return $this->sharingCount;
	}

	/**
	 * Returns total amount of files without links and attached object.
	 * @return integer
	 */
	public function getUnnecessaryVersionSize()
	{
		return $this->unnecessaryVersionSize;
	}

	/**
	 * Returns total count of files without links and attached object.
	 * @return integer
	 */
	public function getUnnecessaryVersionCount()
	{
		return $this->unnecessaryVersionCount;
	}

	/**
	 * Returns disk storage Id.
	 * @return integer|null
	 */
	public function getStorageId()
	{
		return $this->storageId;
	}

	/**
	 * Returns disk storage.
	 * @return \Bitrix\Disk\Storage|null
	 */
	public function getStorage()
	{
		if (!$this->storage instanceof \Bitrix\Disk\Storage && $this->storageId > 0)
		{
			$this->storage = \Bitrix\Disk\Storage::loadById($this->storageId);
		}
		return $this->storage;
	}

	/**
	 * Returns disk folder Id.
	 * @return integer|null
	 */
	public function getFolderId()
	{
		return $this->folderId;
	}

	/**
	 * Returns disk folder.
	 * @return \Bitrix\Disk\Folder|null
	 */
	public function getFolder()
	{
		if (!$this->folder instanceof \Bitrix\Disk\Folder && $this->folderId > 0)
		{
			$this->folder = \Bitrix\Disk\Folder::loadById($this->folderId);
		}
		return $this->folder;
	}

	/**
	 * Returns disk file Id.
	 * @return integer|null
	 */
	public function getFileId()
	{
		return $this->fileId;
	}

	/**
	 * Returns disk file.
	 * @return \Bitrix\Disk\File|null
	 */
	public function getFile()
	{
		if (!$this->file instanceof \Bitrix\Disk\File && $this->fileId > 0)
		{
			$this->file = \Bitrix\Disk\File::loadById($this->fileId);
		}
		return $this->file;
	}

	/**
	 * Returns module Id.
	 * @return string|null
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * Returns entity type.
	 * @return string|null
	 */
	public function getEntityType()
	{
		return $this->entityType;
	}

	/**
	 * Returns entity id.
	 * @return string|null
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}
}