<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main;
use Bitrix\Disk\Volume;


/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Documentgenerator
	extends Volume\Module\Module
	implements Volume\IDeleteConstraint, Volume\IClearFolderConstraint
{
	/** @var string */
	protected static $moduleId = 'documentgenerator';

	/** @var \Bitrix\Disk\Storage[] */
	private $storageList = array();

	/** @var \Bitrix\Disk\Folder[] */
	private $folderList = array();


	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function measure($collectData = array())
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new \Bitrix\Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

		// collect disk statistics
		$this
			->addFilter(0, array(
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => 'Bitrix\\DocumentGenerator\\Integration\\Disk\\ProxyType',
			))
			->addFilter('DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);

		parent::measure();

		return $this;
	}

	/**
	 * Returns module storage.
	 * @return \Bitrix\Disk\Storage[]|array
	 */
	public function getStorageList()
	{
		if (count($this->storageList) == 0)
		{
			if ($this->isMeasureAvailable())
			{
				$storage = \Bitrix\DocumentGenerator\Storage\Disk::getDiskStorage();
				if ($storage instanceof \Bitrix\Disk\Storage)
				{
					$this->storageList[] = $storage;
				}
			}
		}

		return $this->storageList;
	}


	/**
	 * Returns folder list corresponding to module.
	 * @param \Bitrix\Disk\Storage $storage Module's storage.
	 * @return \Bitrix\Disk\Folder[]|array
	 */
	public function getFolderList($storage)
	{
		if ($storage instanceof \Bitrix\Disk\Storage && count($this->folderList[$storage->getId()]) == 0)
		{
			if ($this->isMeasureAvailable())
			{
				$typeFolderCodeList = self::getSpecialFolderCode();
				foreach ($typeFolderCodeList as $code)
				{
					$folder = \Bitrix\Disk\Folder::load(array(
						'=CODE' => $code,
						'=STORAGE_ID' => $storage->getId(),
					));

					if (
						!($folder instanceof \Bitrix\Disk\Folder) ||
						($folder->getCode() !== $code)
					)
					{
						continue;
					}

					$this->folderList[$storage->getId()][$code] = $folder;
				}
				return $this->folderList[$storage->getId()];
			}
		}

		return array();
	}

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode()
	{
		/** @see \Bitrix\DocumentGenerator\Storage\Disk::TEMPLATES_FOLDER_CODE */
		return array('FOR_DOCUMENTGENERATOR_TEMPLATES');
	}

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType()
	{
		return array(
			'Bitrix\\DocumentGenerator\\Integration\\Disk\\ProxyType',
		);
	}

	/**
	 * Check ability to drop folder.
	 * @param \Bitrix\Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(\Bitrix\Disk\Folder $folder)
	{
		if (!$this->isMeasureAvailable())
		{
			return true;
		}
		if ($folder->isDeleted())
		{
			return true;
		}

		static $folderIds;
		if (empty($folderIds))
		{
			$folderIds = array();
			$storageList = $this->getStorageList();
			foreach ($storageList as $storage)
			{
				/** @var \Bitrix\Disk\Folder $folder */
				foreach ($this->getFolderList($storage) as $fldr)
				{
					$folderIds[] = $fldr->getId();
				}
			}
		}

		// disallow delete Voximplant folder
		return (in_array($folder->getId(), $folderIds) === false);
	}

	/**
	 * Check ability to clear folder.
	 *
	 * @param \Bitrix\Disk\Folder $folder Folder to clear.
	 *
	 * @return boolean
	 */
	public function isAllowClearFolder(\Bitrix\Disk\Folder $folder)
	{
		return $this->isAllowDeleteFolder($folder);
	}
}



