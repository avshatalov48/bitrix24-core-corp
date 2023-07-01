<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Main;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Voximplant extends Volume\Module\Module implements Volume\IVolumeIndicatorLink, Volume\IDeleteConstraint
{
	/** @var string */
	protected static $moduleId = 'voximplant';

	/** @var Disk\Storage[] */
	private $storageList = [];

	/** @var Disk\Folder[] */
	private $folderList = [];

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = []): self
	{
		if (!$this->isMeasureAvailable())
		{
			$this->addError(new \Bitrix\Main\Error('', self::ERROR_MEASURE_UNAVAILABLE));
			return $this;
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$indicatorType = $connection->getSqlHelper()->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		VolumeTable::createTemporally();
		$temporallyTableName = VolumeTable::getTemporallyName();

		// Scan specific folder list in a storage
		$storageList = $this->getStorageList();
		if (count($storageList) > 0)
		{
			foreach ($storageList as $storage)
			{
				$storageId = $storage->getId();
				$parentId = $storage->getRootObjectId();
				$folderIds = [];

				$folderList = $this->getFolderList($storage);
				if (count($folderList) > 0)
				{
					foreach ($folderList as $folder)
					{
						$folderIds[] = $folder->getId();
					}
				}
				if (count($folderIds) > 0)
				{
					$agr = new Volume\FolderTree;
					$agr
						->setOwner($this->getOwner())
						->addFilter('STORAGE_ID', $storageId)
						->addFilter('@FOLDER_ID', $folderIds)
						->purify()
						->measure([self::DISK_FILE]);

					$indicatorTypeFolder = $connection->getSqlHelper()->forSql(Volume\Folder::className());

					$folderIdSql = implode(',', $folderIds);

					$querySql = "
						INSERT INTO {$temporallyTableName} 
						(
							INDICATOR_TYPE,
							OWNER_ID,
							CREATE_TIME,
							FILE_SIZE,
							FILE_COUNT,
							DISK_SIZE,
							DISK_COUNT,
							VERSION_COUNT,
							ATTACHED_COUNT,
							LINK_COUNT,
							SHARING_COUNT,
							UNNECESSARY_VERSION_SIZE,
							UNNECESSARY_VERSION_COUNT
						)
						SELECT 
							'{$indicatorType}',
							{$ownerId},
							" . $connection->getSqlHelper()->getCurrentDateTimeFunction() . ",
							SUM(FILE_SIZE),
							SUM(FILE_COUNT),
							SUM(DISK_SIZE),
							SUM(DISK_COUNT),
							SUM(VERSION_COUNT),
							SUM(ATTACHED_COUNT),
							SUM(LINK_COUNT),
							SUM(SHARING_COUNT),
							SUM(UNNECESSARY_VERSION_SIZE),
							SUM(UNNECESSARY_VERSION_COUNT)
						FROM 
							b_disk_volume
						WHERE 
							INDICATOR_TYPE = '{$indicatorTypeFolder}'
							and OWNER_ID = {$ownerId} 
							and STORAGE_ID = '{$storageId}'
							and FOLDER_ID IN( {$folderIdSql} ) 
							and PARENT_ID = '{$parentId}'
					";

					$connection->queryExecute($querySql);
				}
			}

			$querySql = "
				SELECT 
					INDICATOR_TYPE,
					OWNER_ID,
					" . $connection->getSqlHelper()->getCurrentDateTimeFunction() . ",
					SUM(FILE_SIZE),
					SUM(FILE_COUNT),
					SUM(DISK_SIZE),
					SUM(DISK_COUNT),
					SUM(VERSION_COUNT),
					SUM(ATTACHED_COUNT),
					SUM(LINK_COUNT),
					SUM(SHARING_COUNT),
					SUM(UNNECESSARY_VERSION_SIZE),
					SUM(UNNECESSARY_VERSION_COUNT)
				FROM 
					{$temporallyTableName}
				WHERE 
					INDICATOR_TYPE = '{$indicatorType}'
				GROUP BY
					INDICATOR_TYPE
				ORDER BY NULL
			";

			$columnList = Volume\QueryHelper::prepareInsert(
				[
					'INDICATOR_TYPE',
					'OWNER_ID',
					'CREATE_TIME',
					'FILE_SIZE',
					'FILE_COUNT',
					'DISK_SIZE',
					'DISK_COUNT',
					'VERSION_COUNT',
					'ATTACHED_COUNT',
					'LINK_COUNT',
					'SHARING_COUNT',
					'UNNECESSARY_VERSION_SIZE',
					'UNNECESSARY_VERSION_COUNT',
				],
				$this->getSelect()
			);

			$tableName = VolumeTable::getTableName();

			$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

			VolumeTable::clearTemporally();
		}

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
				$siteID = SITE_ID;
				if (
					(defined('ADMIN_SECTION') && ADMIN_SECTION)
					|| Volume\Cleaner::isCronRun()
				)
				{
					$sites = \CSite::getList('sort', 'desc', ['DEF' => 'Y']);
					if ($site = $sites->fetch())
					{
						$siteID = $site['LID'];
					}
				}

				$storage = \CVoxImplantDiskHelper::getStorageModel($siteID);
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
			$storage instanceof Disk\Storage
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
								!($folder instanceof Disk\Folder)
								|| ($folder->getCode() !== $code)
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
		return ['VI_CALLS'];
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

		static $voxFolderIds;
		if (empty($voxFolderIds))
		{
			$voxFolderIds = [];
			$voxStorageList = $this->getStorageList();
			foreach ($voxStorageList as $voxStorage)
			{
				$voxFolders = $this->getFolderList($voxStorage);
				if (is_array($voxFolders) && count($voxFolders) > 0)
				{
					foreach ($voxFolders as $voxFolder)
					{
						$voxFolderIds[] = $voxFolder->getId();
					}
				}
			}
		}

		// disallow delete Voximplant folder
		return (in_array($folder->getId(), $voxFolderIds) === false);
	}

	/**
	 * Returns calculation result set per folder.
	 * @param array $collectedData List types of collected data to return.
	 * @return array
	 */
	public function getMeasurementFolderResult($collectedData = [])
	{
		$resultList = [];

		$totalSize = 0;
		$storageList = $this->getStorageList();
		foreach ($storageList as $storage)
		{
			$folders = $this->getFolderList($storage);
			$folderIds = [];
			foreach ($folders as $folder)
			{
				$folderIds[] = $folder->getId();
			}

			$agr = new Volume\FolderTree();
			$agr
				->setOwner($this->getOwner())
				->addFilter('STORAGE_ID', $storage->getId())
				->addFilter('@FOLDER_ID', $folderIds)
				->loadTotals();

			if ($agr->getTotalCount() > 0)
			{
				$result = $agr->getMeasurementResult();

				foreach ($result as $row)
				{
					$resultList[] = $row;
					$totalSize += $row['FILE_SIZE'];
				}
			}
		}
		if ($totalSize > 0)
		{
			foreach ($resultList as $id => $row)
			{
				$percent = $row['FILE_SIZE'] * 100 / $totalSize;
				$resultList[$id]['PERCENT'] = round($percent, 1);
			}
		}

		return $resultList;
	}

	/**
	 * @param string[] $filter Filter with module id.
	 * @return Volume\Fragment
	 */
	public static function getFragment(array $filter): Volume\Fragment
	{
		if ($filter['INDICATOR_TYPE'] == Volume\Folder::className() || $filter['INDICATOR_TYPE'] == Volume\FolderTree::className())
		{
			// Chat specific
			$chatList = \Bitrix\Im\Model\ChatTable::getList([
				'select' => ['ID', 'TITLE', 'LAST_MESSAGE_ID'],
				'filter' => ['=DISK_FOLDER_ID' => $filter['FOLDER_ID']],
			]);
			if ($chat = $chatList->fetch())
			{
				$filter['SPECIFIC'] = [
					'chat' => $chat,
					'userInChat' => [],
					'userCount' => 0
				];
				$chatUserList = \Bitrix\Im\Model\RelationTable::getList([
					'select' => ['USER_ID'],
					'filter' => ['=CHAT_ID' => $chat['ID']]
				]);
				if ($chatUserList->getSelectedRowsCount() > 0)
				{
					foreach ($chatUserList as $chatUser)
					{
						$filter['SPECIFIC']['userInChat'][] = $chatUser['USER_ID'];
						$filter['SPECIFIC']['userCount']++;
					}
				}
			}

			return new Volume\Fragment($filter);
		}

		return parent::getFragment($filter);
	}

	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string|null
	 * @throws ArgumentTypeException
	 */
	public static function getTitle(Volume\Fragment $fragment): ?string
	{
		if ($fragment->getIndicatorType() == Volume\Folder::className() || $fragment->getIndicatorType() == Volume\FolderTree::className())
		{
			$folder = $fragment->getFolder();
			if (!$folder instanceof Disk\Folder)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
			}

			return $folder->getOriginalName();
		}

		return parent::getTitle($fragment);
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 * @throws ArgumentTypeException
	 */
	public static function getUpdateTime(Volume\Fragment $fragment): ?\Bitrix\Main\Type\DateTime
	{
		$timestampUpdate = null;
		if ($fragment->getIndicatorType() == Volume\Folder::className() || $fragment->getIndicatorType() == Volume\FolderTree::className())
		{
			$folder = $fragment->getFolder();
			if (!$folder instanceof Disk\Folder)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
			}
			$timestampUpdate = $folder->getUpdateTime()->toUserTime();
		}

		return $timestampUpdate;
	}


	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment): string
	{
		$url = '';
		if ($fragment->getIndicatorType() == Volume\Folder::className() || $fragment->getIndicatorType() == Volume\FolderTree::className())
		{
			$folder = $fragment->getFolder();
			if (!$folder instanceof Disk\Folder)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.Disk\Folder::className());
			}
			$urlManager = Disk\Driver::getInstance()->getUrlManager();

			$url = $urlManager->getUrlFocusController('openFolderList', ['folderId' => $folder->getId()]);
		}

		return $url;
	}
}



