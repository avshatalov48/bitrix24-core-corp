<?php

namespace Bitrix\Disk\Volume\Storage;

use Bitrix\Main;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk\Internals\StorageTable;
use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Uploaded extends Volume\Storage\Storage implements Volume\IVolumeIndicatorLink, Volume\IVolumeTimeLimit
{
	/** @var Volume\Timer */
	private $timer;

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function measure($collectData = array())
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$indicatorType = $sqlHelper->forSql(static::className());
		$indicatorTypeFolder = $sqlHelper->forSql(Volume\Folder::className());
		$ownerId = (string)$this->getOwner();

		\Bitrix\Disk\Internals\VolumeTable::createTemporally();

		$filter = $this->getFilter();

		$filterStorage = array();
		foreach ($filter as $key => $val)
		{
			switch ($key)
			{
				case 'STORAGE_ID':
				case '=STORAGE_ID':
					$filterStorage[] = $val;
					break;
				case '@STORAGE_ID':
					$filterStorage = $val;
					break;
			}
		}

		// must have filter by storage
		if (count($filterStorage) == 0)
		{
			$filterStorage = $this->getStorageIdList($filter);
		}



		foreach ($filterStorage as $storageId)
		{
			/*
			$storageIds = array();
			$parentIds = array();
			$folderIds = array();

			$folderParamList = $this->getUploadFolderList($filter);
			if (count($folderParamList) > 0)
			{
				foreach ($folderParamList as $folderParam)
				{
					$storageIds[] = $folderParam['STORAGE_ID'];
					$parentIds[] = $folderParam['PARENT_ID'];
					$folderIds[] = $folderParam['ID'];
				}
			}
			*/

			// check for existing result
			if ($this->getFilterId() < 0)
			{
				$measureResult = \Bitrix\Disk\Internals\VolumeTable::getList(array(
					'filter' => array(
						'=INDICATOR_TYPE' => static::className(),
						'=OWNER_ID' => $this->getOwner(),
						'=STORAGE_ID' => $storageId,
					),
					'select' => array('ID')
				));
				if ($measureResult->getSelectedRowsCount() > 0)
				{
					continue;
				}
			}

			// Scan specific Upload folder in a storage
			$folderParamList = $this->getUploadFolderList(array('STORAGE_ID' => $storageId));
			if (count($folderParamList) == 0)
			{
				continue;
			}
			$folderParam = array_shift($folderParamList);

			$parentId = $folderParam['PARENT_ID'];
			$folderId = $folderParam['ID'];

			$agr = new Volume\Folder();
			$agr
				->setOwner($this->getOwner())
				->addFilter('=STORAGE_ID', $storageId)
				->addFilter('=PARENT_ID', $folderId)
				->measure(array(self::DISK_FILE));

			$hasData = false;
			// check for result
			$measureResult = \Bitrix\Disk\Internals\VolumeTable::getList(array(
				'filter' => array(
					'=INDICATOR_TYPE' => Volume\Folder::className(),
					'=OWNER_ID' => $this->getOwner(),
					'=STORAGE_ID' => $storageId,
					'FOLDER_ID' => $folderId,
					'PARENT_ID' => $parentId,
				),
				'select' => array('ID')
			));
			if ($measureResult->getSelectedRowsCount() > 0)
			{
				$hasData = true;
			}


			/*
				$querySql = "
					INSERT INTO b_disk_volume_tmp 
					(
						INDICATOR_TYPE,
						OWNER_ID,
						STORAGE_ID,
						FOLDER_ID,
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
						STORAGE_ID,
						FOLDER_ID,
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
						and STORAGE_ID = {$storageId} 
						and FOLDER_ID = {$folderId}  
						and PARENT_ID = {$parentId} 
					GROUP BY
						STORAGE_ID
				";

				$connection->queryExecute($querySql);
			*/

			if ($hasData)
			{
				$querySql = "
					SELECT 
						'{$indicatorType}' as INDICATOR_TYPE,
						{$ownerId} as OWNER_ID,
						". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
						STORAGE_ID as STORAGE_ID,
						FOLDER_ID as FOLDER_ID,
						SUM(FILE_SIZE) as FILE_SIZE,
						SUM(FILE_COUNT) as FILE_COUNT,
						SUM(DISK_SIZE) as DISK_SIZE,
						SUM(DISK_COUNT) as DISK_COUNT,
						SUM(VERSION_COUNT) as VERSION_COUNT,
						SUM(ATTACHED_COUNT) as ATTACHED_COUNT,
						SUM(LINK_COUNT) as LINK_COUNT,
						SUM(SHARING_COUNT) as SHARING_COUNT,
						SUM(UNNECESSARY_VERSION_SIZE) as UNNECESSARY_VERSION_SIZE,
						SUM(UNNECESSARY_VERSION_COUNT) as UNNECESSARY_VERSION_COUNT
					FROM 
						b_disk_volume
					WHERE 
						INDICATOR_TYPE = '{$indicatorTypeFolder}'
						and OWNER_ID = {$ownerId}
						and STORAGE_ID = {$storageId} 
						and FOLDER_ID = {$folderId}  
						and PARENT_ID = {$parentId} 
					GROUP BY
						STORAGE_ID
				";

				$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();
				$columns = array(
					'INDICATOR_TYPE',
					'OWNER_ID',
					'CREATE_TIME',
					'STORAGE_ID',
					'FOLDER_ID',
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
				);

				if ($this->getFilterId() > 0)
				{
					$filterId = $this->getFilterId();
					$columnList = Volume\QueryHelper::prepareUpdateOnSelect($columns, $this->getSelect(), 'destinationTbl', 'sourceQuery');
					$connection->queryExecute("UPDATE {$tableName} destinationTbl, ({$querySql}) sourceQuery SET {$columnList} WHERE destinationTbl.ID = {$filterId}");
				}
				else
				{
					$columnList = Volume\QueryHelper::prepareInsert($columns, $this->getSelect());
					$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");
				}
			}
			else
			{
				\Bitrix\Disk\Internals\VolumeTable::add(array(
					'INDICATOR_TYPE' => static::className(),
					'OWNER_ID' =>	$ownerId,
					'STORAGE_ID' =>	$storageId,
					'FOLDER_ID' =>	$folderId,
					'PARENT_ID' => $parentId,
				));
			}

			if ($this->timer instanceof Volume\Timer && !$this->checkTimeEnd())
			{
				break;
			}

			\Bitrix\Disk\Internals\VolumeTable::clearTemporally();
		}

		\Bitrix\Disk\Internals\VolumeTable::dropTemporally();

		return $this;
	}

	/**
	 * Returns users storage list.
	 * @param array $filter Filter storage list.
	 * @return int[]
	 */
	private function getStorageIdList($filter = array())
	{
		$filter['=ENTITY_TYPE'] = \Bitrix\Disk\ProxyType\User::className();

		$result = StorageTable::getList(array(
			"select" => array('ID'),
			"filter" => $filter,
		));

		$storageIdList = array();
		foreach ($result as $row)
		{
			$storageIdList[] = $row['ID'];
		}

		return $storageIdList;
	}

	/**
	 * Returns upload folder's id list.
	 * @param array $filter Filter storage list.
	 * @return array
	 */
	private function getUploadFolderList($filter = array())
	{
		$filter['=STORAGE.ENTITY_TYPE'] = \Bitrix\Disk\ProxyType\User::className();
		$filter['=CODE'] = \Bitrix\Disk\Folder::CODE_FOR_UPLOADED_FILES;

		$result = \Bitrix\Disk\Folder::getList(array(
			'select' => array('ID', 'STORAGE_ID', 'PARENT_ID'),
			'filter' => $filter,
		));

		$folderIdListRes = array();
		foreach ($result as $row)
		{
			$folderIdListRes[$row['STORAGE_ID']] = $row;
		}

		return $folderIdListRes;
	}


	/**
	 * @param Volume\Fragment $fragment Folder entity object.
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function getUrl(Volume\Fragment $fragment)
	{
		$folder = $fragment->getFolder();
		if (!$folder instanceof \Bitrix\Disk\Folder)
		{
			throw new ArgumentTypeException("Fragment must be subclass of ".\Bitrix\Disk\Folder::className());
		}
		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();

		$url = $urlManager->getUrlFocusController('openFolderList', array('folderId' => $folder->getId()));

		return $url;
	}


	/**
	 * Sets start up time.
	 * @return void
	 */
	public function startTimer()
	{
		$this->timer = new Volume\Timer();
		$this->timer->startTimer();
	}

	/**
	 * Checks timer for time limitation/
	 * @return bool
	 */
	public function checkTimeEnd()
	{
		return $this->timer->checkTimeEnd();
	}

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached()
	{
		return $this->timer->hasTimeLimitReached();
	}

	/**
	 * Sets limitation time in seconds.
	 * @param int $timeLimit Timeout in seconds.
	 * @return void
	 */
	public function setTimeLimit($timeLimit)
	{
		$this->timer->setTimeLimit($timeLimit);
	}

	/**
	 * Gets limitation time in seconds.
	 * @return int
	 */
	public function getTimeLimit()
	{
		return $this->timer->getTimeLimit();
	}

	/**
	 * Gets step identification.
	 * @return string|null
	 */
	public function getStepId()
	{
		return $this->timer->getStepId();
	}

	/**
	 * Sets step identification.
	 * @param string $stepId Step id.
	 * @return void
	 */
	public function setStepId($stepId)
	{
		$this->timer->setStepId($stepId);
	}
}
