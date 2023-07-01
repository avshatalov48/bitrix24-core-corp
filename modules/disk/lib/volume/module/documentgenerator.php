<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Main;
use Bitrix\Disk;
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

	/** @var Disk\Storage[] */
	private $storageList = [];

	/** @var Disk\Folder[] */
	private $folderList = [];


	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function measure(array $collectData = []): self
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new \Bitrix\Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

		// collect disk statistics
		$this
			->addFilter(0, [
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => \Bitrix\DocumentGenerator\Integration\Disk\ProxyType::class,
			])
			->addFilter('DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);

		parent::measure();

		return $this;
	}

	/**
	 * Returns module storage.
	 * @return Disk\Storage[]|array
	 */
	public function getStorageList(): array
	{
		if (count($this->storageList) == 0)
		{
			if ($this->isMeasureAvailable())
			{
				$storage = \Bitrix\DocumentGenerator\Storage\Disk::getDiskStorage();
				if ($storage instanceof Disk\Storage)
				{
					$this->storageList[] = $storage;
				}
			}
		}

		return $this->storageList;
	}


	/**
	 * Returns folder list corresponding to module.
	 * @param Disk\Storage $storage Module's storage.
	 * @return Disk\Folder[]|array
	 */
	public function getFolderList($storage): array
	{
		if (
			$storage instanceof Disk\Storage &&
			$storage->getId() > 0
		)
		{
			if (
				!isset($this->folderList[$storage->getId()]) ||
				empty($this->folderList[$storage->getId()])
			)
			{
				$this->folderList[$storage->getId()] = [];
				if ($this->isMeasureAvailable())
				{
					$typeFolderCodeList = self::getSpecialFolderCode();
					if (count($typeFolderCodeList) > 0)
					{
						foreach ($typeFolderCodeList as $code)
						{
							$folder = Disk\Folder::load([
								'=CODE' => $code,
								'=STORAGE_ID' => $storage->getId(),
							]);
							if (
								!($folder instanceof Disk\Folder) ||
								($folder->getCode() !== $code)
							)
							{
								continue;
							}
							$this->folderList[$storage->getId()][$code] = $folder;
						}
					}
				}
			}

			return $this->folderList[$storage->getId()];
		}

		return [];
	}

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode(): array
	{
		/** @see \Bitrix\DocumentGenerator\Storage\Disk::TEMPLATES_FOLDER_CODE */
		return ['FOR_DOCUMENTGENERATOR_TEMPLATES'];
	}

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		return [
			\Bitrix\DocumentGenerator\Integration\Disk\ProxyType::class
		];
	}

	/**
	 * Check ability to drop folder.
	 * @param Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(Disk\Folder $folder): bool
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
			$folderIds = [];
			$storageList = $this->getStorageList();
			foreach ($storageList as $storage)
			{
				/** @var Disk\Folder $fldr */
				foreach ($this->getFolderList($storage) as $fldr)
				{
					$folderIds[] = $fldr->getId();
				}
			}
		}

		// disallow delete Module's folder
		return (in_array($folder->getId(), $folderIds) === false);
	}

	/**
	 * Check ability to clear folder.
	 *
	 * @param Disk\Folder $folder Folder to clear.
	 *
	 * @return boolean
	 */
	public function isAllowClearFolder(Disk\Folder $folder): bool
	{
		return $this->isAllowDeleteFolder($folder);
	}
}



