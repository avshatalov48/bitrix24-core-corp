<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Storage;
use Bitrix\Disk\Folder;
use Bitrix\Main\Localization\Loc;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Rest
	extends Volume\Module\Module
	implements Volume\IDeleteConstraint, Volume\IClearConstraint
{
	/** @var string */
	protected static $moduleId = 'rest';

	/** @var Storage[] */
	private $storageList = [];

	/** @var Folder[] */
	private $folderList = [];

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		return [
			\Bitrix\Rest\Configuration\DataProvider\Disk\ProxyDiskType::class
		];
	}

	/**
	 * Returns module storage.
	 * @see \Bitrix\Rest\Configuration\Helper::getStorageBackupParam
	 * @return Storage[]|array
	 */
	public function getStorageList(): array
	{
		if (count($this->storageList) == 0 || !$this->storageList[0] instanceof Storage)
		{
			$entityTypes = self::getEntityType();
			$storage = Storage::load([
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => $entityTypes[0]
			]);
			if ($storage instanceof Storage)
			{
				$this->storageList[] = $storage;
			}
		}

		return $this->storageList;
	}

	/**
	 * Returns folder list corresponding to module.
	 * @param Storage $storage Module's storage.
	 * @return Folder[]|array
	 */
	public function getFolderList($storage): array
	{
		if (
			$storage instanceof Storage
			&& $storage->getId() > 0
		)
		{
			if (
				!isset($this->folderList[$storage->getId()])
				|| empty($this->folderList[$storage->getId()])
			)
			{
				$this->folderList[$storage->getId()] = [];
				if ($this->isMeasureAvailable())
				{
					$this->folderList[$storage->getId()][] = $storage->getRootObject();
				}
			}

			return $this->folderList[$storage->getId()];
		}

		return [];
	}

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return self
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
				'LOGIC' => 'OR',
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => \Bitrix\Rest\Configuration\DataProvider\Disk\ProxyDiskType::class,
			])
			->addFilter('DELETED_TYPE', Disk\Internals\ObjectTable::DELETED_TYPE_NONE);

		parent::measure();

		return $this;
	}


	/**
	 * Check ability to clear storage.
	 * @param Storage $storage Storage to clear.
	 * @return boolean
	 */
	public function isAllowClearStorage(Storage $storage): bool
	{
		static $restStorageId;
		if (empty($restStorageId))
		{
			$storageList = $this->getStorageList();
			if ($storageList[0] instanceof Storage)
			{
				$restStorageId = $storageList[0]->getId();
			}
		}

		// disallow clearance if REST is unavailable
		if ($restStorageId === $storage->getId())
		{
			return $this->isMeasureAvailable();// returns false to prevent fatal error
		}

		return true;
	}

	/**
	 * Check ability to drop folder.
	 * @param Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(Folder $folder): bool
	{
		if ($folder->isDeleted())
		{
			return true;
		}

		static $restStorageId;
		if (empty($restStorageId))
		{
			$storageList = $this->getStorageList();
			if ($storageList[0] instanceof Storage)
			{
				$restStorageId = $storageList[0]->getId();
			}
		}

		// disallow drop any folders within REST storage
		return (bool)($restStorageId != $folder->getStorageId());
	}


	/**
	 * @param Volume\Fragment $fragment Module description structure.
	 * @return string|null
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		Loc::loadMessages(__FILE__);
		return Loc::getMessage('DISK_VOLUME_MODULE_REST');
	}
}
