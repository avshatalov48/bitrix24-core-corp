<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Main;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ObjectException;
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

		$connection = \Bitrix\Main\Application::getConnection();
		$indicatorType = $connection->getSqlHelper()->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		VolumeTable::createTemporally();
		$temporallyTableName = VolumeTable::getTemporallyName();

		// Scan specific folder list in a storage
		$storageList = $this->getStorageList();
		foreach ($storageList as $storage)
		{
			$storageId = $storage->getId();
			$parentId = $storage->getRootObjectId();
			$folderIds = array();

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
				$agr = new Volume\Folder();
				$agr
					->setOwner($this->getOwner())
					->addFilter('STORAGE_ID', $storageId)
					->addFilter('@PARENT_ID', $folderIds)
					->purify()
					->measure(array(self::DISK_FILE));

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
						". $connection->getSqlHelper()->getCurrentDateTimeFunction(). ",
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
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). ",
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
			array(
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
			),
			$this->getSelect()
		);

		$tableName = VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		VolumeTable::dropTemporally();

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
				$siteID = SITE_ID;
				if (
					(defined('ADMIN_SECTION') && ADMIN_SECTION) ||
					\Bitrix\Disk\Volume\Cleaner::isCronRun()
				)
				{
					$sites = \CSite::GetList($by = 'sort', $order = 'desc', array('DEF' => 'Y'));
					if ($site = $sites->Fetch())
					{
						$siteID = $site['LID'];
					}
				}

				$storage = \CVoxImplantDiskHelper::GetStorageModel($siteID);
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
		if (
			$storage instanceof \Bitrix\Disk\Storage &&
			$storage->getId() > 0 &&
			(
				!isset($this->folderList[$storage->getId()]) ||
				empty($this->folderList[$storage->getId()])
			)
		)
		{
			$this->folderList[$storage->getId()] = array();
			if ($this->isMeasureAvailable())
			{
				$typeFolderCodeList = self::getSpecialFolderCode();
				if (count($typeFolderCodeList) > 0)
				{
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
		return array('VI_CALLS');
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

		static $voxFolderIds;
		if (empty($voxFolderIds))
		{
			$voxFolderIds = array();
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
	public function getMeasurementFolderResult($collectedData = array())
	{
		$resultList = array();

		$totalSize = 0;
		$storageList = $this->getStorageList();
		foreach ($storageList as $storage)
		{
			$folders = $this->getFolderList($storage);
			$folderIds = array();
			foreach ($folders as $folder)
			{
				$folderIds[] = $folder->getId();
			}

			$agr = new Volume\Folder();
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
	 * @throws ArgumentTypeException
	 * @throws ObjectException
	 */
	public static function getFragment(array $filter)
	{
		if($filter['INDICATOR_TYPE'] == Volume\Folder::className())
		{
			// Chat specific
			$chatList = \Bitrix\Im\Model\ChatTable::getList(array(
				'select' => array('ID', 'TITLE', 'LAST_MESSAGE_ID'),
				'filter' => array('=DISK_FOLDER_ID' => $filter['FOLDER_ID'])
			));
			if ($chat = $chatList->fetch())
			{
				$filter['SPECIFIC'] = array(
					'chat' => $chat,
					'userInChat' => array(),
					'userCount' => 0
				);
				$chatUserList = \Bitrix\Im\Model\RelationTable::getList(array(
					'select' => array('USER_ID'),
					'filter' => array('=CHAT_ID' => $chat['ID'])
				));
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
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		if($fragment->getIndicatorType() == Volume\Folder::className())
		{
			$folder = $fragment->getFolder();
			if (!$folder instanceof \Bitrix\Disk\Folder)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
			}
			$title = $folder->getOriginalName();

			return $title;
		}
		return parent::getTitle($fragment);
	}

	/**
	 * Returns last update time of the entity object.
	 * @param Volume\Fragment $fragment Entity object.
	 * @return \Bitrix\Main\Type\DateTime|null
	 * @throws ArgumentTypeException
	 */
	public static function getUpdateTime(Volume\Fragment $fragment)
	{
		$timestampUpdate = null;
		if($fragment->getIndicatorType() == Volume\Folder::className())
		{
			$folder = $fragment->getFolder();
			if (!$folder instanceof \Bitrix\Disk\Folder)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
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
	public static function getUrl(Volume\Fragment $fragment)
	{
		$url = '';
		if($fragment->getIndicatorType() == Volume\Folder::className())
		{
			$folder = $fragment->getFolder();
			if (!$folder instanceof \Bitrix\Disk\Folder)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\Folder::className());
			}
			$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();

			$url = $urlManager->getUrlFocusController('openFolderList', array('folderId' => $folder->getId()));
		}

		return $url;
	}
}



