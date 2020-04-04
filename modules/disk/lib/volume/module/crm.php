<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Internals\VolumeTable;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Disk\Volume;
use Bitrix\Crm\Integration\StorageFileType as IMS;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Crm
	extends Volume\Module\Module
	implements Volume\IVolumeIndicatorLink, Volume\IDeleteConstraint, Volume\IVolumeTimeLimit
{
	/** @var string */
	protected static $moduleId = 'crm';

	/** @var \Bitrix\Disk\Storage[]|array */
	private $storageList = array();

	/** @var \Bitrix\Disk\Folder[]|array */
	private $folderList = array();

	/** @implements Volume\IVolumeTimeLimit */
	use Volume\TimeLimit;

	/**
	 * Returns measure process stages list.
	 * @return string[]
	 */
	public function getMeasureStages()
	{
		return array(
			'UserFields',
			'ActElemFile',
			'ActElemDisk',
			'CrmEvent',
			'CrmFolder',
		);
	}

	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return $this
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

		$tableName = VolumeTable::getTableName();

		$stageId = $this->getStage();
		if (empty($stageId))
		{
			$stageId = 'UserFields';
			$this->setStage($stageId);
		}

		switch($stageId)
		{
			case 'UserFields':
			{
				// Scan User fields specific to module
				$entityUserFieldSource = $this->prepareUserFieldSourceSql(null, array(\CUserTypeFile::USER_TYPE_ID));
				if ($entityUserFieldSource != '')
				{
					$querySql = "
						INSERT INTO {$tableName}
						(
							INDICATOR_TYPE,
							OWNER_ID,
							CREATE_TIME,
							TITLE,
							FILE_SIZE,
							FILE_COUNT,
							DISK_SIZE,
							DISK_COUNT,
							VERSION_COUNT
						)
						SELECT 
							'{$indicatorType}' as INDICATOR_TYPE,
							{$ownerId} as OWNER_ID,
							". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
							'UserFields',
							SUM(src.FILE_SIZE) as FILE_SIZE,
							SUM(src.FILE_COUNT) as FILE_COUNT,
							SUM(src.DISK_SIZE) as DISK_SIZE,
							SUM(src.DISK_COUNT) as DISK_COUNT,
							SUM(src.VERSION_COUNT) as VERSION_COUNT
						FROM 
						(
							{$entityUserFieldSource}
						) src
					";
					$connection->queryExecute($querySql);
				}
				unset($querySql);

				$this->setStage('ActElemFile');// go next

				if (!$this->checkTimeEnd())
				{
					break;
				}
			}


			case 'ActElemFile':
			{
				$crmActivityElememtTable = \CCrmActivity::ELEMENT_TABLE_NAME;

				$querySql = "
					INSERT INTO {$tableName}
					(
						INDICATOR_TYPE,
						OWNER_ID,
						CREATE_TIME,
						TITLE,
						FILE_SIZE,
						FILE_COUNT,
						DISK_SIZE,
						DISK_COUNT,
						VERSION_COUNT
					)
					SELECT
						'{$indicatorType}' as INDICATOR_TYPE,
						{$ownerId} as OWNER_ID,
						". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
						'ActElemFile' as TITLE, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						COUNT(f.id) as FILE_COUNT, 
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT
					FROM 
						b_file f
						INNER JOIN (
							SELECT ELEMENT_ID
							FROM {$crmActivityElememtTable}
							WHERE STORAGE_TYPE_ID = '".\Bitrix\Crm\Integration\StorageType::File."'
							GROUP BY ELEMENT_ID  
							ORDER BY NULL
						) elem
							ON elem.ELEMENT_ID = f.ID
				";
				$connection->queryExecute($querySql);
				unset($querySql);

				$this->setStage('ActElemDisk');// go next

				if (!$this->checkTimeEnd())
				{
					break;
				}
			}


			case 'ActElemDisk':
			{
				/**
				 * @param Volume\IVolumeIndicatorModule $indicator
				 * @return int[]
				 */
				$getExcludeFolderId = function ($indicator)
				{
					$folderIds = array();
					$storageList = $indicator->getStorageList();
					foreach ($storageList as $storage)
					{
						$folderList = $indicator->getFolderList($storage);
						foreach ($folderList as $folder)
						{
							$folderIds[] = $folder->getId();

							$childFolders = \Bitrix\Disk\Internals\FolderTable::getList(array(
								'select' => array('ID'),
								'filter' => array(
									'=TYPE' => \Bitrix\Disk\Internals\ObjectTable::TYPE_FOLDER,
									'=PATH_CHILD.PARENT_ID' => $folder->getId()
								)
							));
							foreach ($childFolders as $row)
							{
								$folderIds[] = $row['ID'];
							}
						}
					}

					return $folderIds;
				};

				// exclude CRM regular folders content
				$excludeFolderIds = $getExcludeFolderId($this);

				// exclude voximplant folders content
				$vox = new Volume\Module\Voximplant();
				$excludeFolderIds = array_merge($excludeFolderIds, $getExcludeFolderId($vox));

				$excludeFolderSql = '';
				if (count($excludeFolderIds) > 0)
				{
					$excludeFolderSql = '
						AND files.PARENT_ID NOT IN(
							SELECT object_id FROM b_disk_object_path 
							WHERE PARENT_id IN('. implode(',', $excludeFolderIds). ')
						)
					';
				}

				$crmActivityElememtTable = \CCrmActivity::ELEMENT_TABLE_NAME;

				$querySql = "
					INSERT INTO {$tableName}
					(
						INDICATOR_TYPE,
						OWNER_ID,
						CREATE_TIME,
						TITLE,
						FILE_SIZE,
						FILE_COUNT,
						DISK_SIZE,
						DISK_COUNT,
						VERSION_COUNT
					)
					SELECT
						'{$indicatorType}' as INDICATOR_TYPE,
						{$ownerId} as OWNER_ID,
						". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
						'ActElemDisk' as TITLE, 
						SUM(f.FILE_SIZE) as FILE_SIZE,
						COUNT(f.id) as FILE_COUNT, 
						SUM(f.FILE_SIZE) as DISK_SIZE,
						COUNT(f.id) as DISK_COUNT,
						COUNT(f.id) as VERSION_COUNT
					FROM 
						b_disk_object files 
						INNER JOIN b_file f 
							ON files.FILE_ID = f.ID 
						INNER JOIN 
						(
							SELECT ELEMENT_ID 
							FROM {$crmActivityElememtTable} 
							WHERE STORAGE_TYPE_ID = '".\Bitrix\Crm\Integration\StorageType::Disk."'
							GROUP BY ELEMENT_ID
							ORDER BY NULL
						) elem
							ON files.ID = elem.ELEMENT_ID
					WHERE
						files.TYPE = '".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE."'
						AND files.ID = files.REAL_OBJECT_ID
						{$excludeFolderSql}
				";
				$connection->queryExecute($querySql);
				unset($querySql);

				$this->setStage('CrmEvent');// go next

				if (!$this->checkTimeEnd())
				{
					break;
				}
			}


			case 'CrmEvent':
			{
				$crmEventTable = \Bitrix\Crm\EventTable::getTableName();

				// analise b_crm_event with non empty field FILES
				$querySql = "
					INSERT INTO {$tableName}
					(
						INDICATOR_TYPE,
						OWNER_ID,
						CREATE_TIME,
						TITLE,
						FILE_SIZE,
						FILE_COUNT,
						DISK_SIZE,
						DISK_COUNT,
						VERSION_COUNT
					)
					SELECT 
						'{$indicatorType}' as INDICATOR_TYPE,
						{$ownerId} as OWNER_ID,
						". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
						'CrmEvent' as TITLE,
						SUM(f.FILE_SIZE) as FILE_SIZE,
						count(f.ID) as FILE_COUNT,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT
					FROM 
					(
						select  
							CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(src.fids, ' ', NS.n), ' ', -1) AS UNSIGNED) as ID
						from (
							select 1 as n union
							select 2 union
							select 3 union
							select 4 union
							select 5 union
							select 6 union
							select 7 union
							select 8 union
							select 9 union
							select 10 union
							select 11 union
							select 12 union
							select 13 union
							select 14 union
							select 15 union
							select 16 union
							select 17 union
							select 18 union
							select 19 union
							select 20
						) NS
						inner join
						(
							select
								@xml := replace(
									replace(
										replace(
											replace(
												e.FILES, 
												'a:','<a><len>'
											),
											';}','</i></a>'
										),
										':{i:','</len><i>'
									),
									';i:','</i><i>'
								) as xml,
								CAST(ExtractValue(@xml, '/a/len') AS UNSIGNED) as len,
								ExtractValue(@xml, '/a/i[position() mod 2 = 0]') as fids
							from 
								{$crmEventTable} e
							where 
								e.FILES is not null
								and e.FILES <> ''
								and e.FILES <> 'a:0:{}'
						) src 
						ON NS.n <= src.len
						
					) file_ids
					INNER JOIN b_file f
							ON file_ids.ID = f.ID
				";
				$connection->queryExecute($querySql);
				unset($querySql);

				$this->setStage('CrmFolder');// go next

				if (!$this->checkTimeEnd())
				{
					break;
				}
			}


			case 'CrmFolder':
			{
				// Scan specific folder list in a storage
				VolumeTable::createTemporally();
				$temporallyTableName = VolumeTable::getTemporallyName();

				$storageList = $this->getStorageList();
				foreach ($storageList as $storage)
				{
					$storageId = $storage->getId();
					$parentId = $storage->getRootObjectId();
					$folderIds = array();

					$folders = $this->getFolderList($storage);
					foreach ($folders as $folder)
					{
						$folderIds[] = $folder->getId();
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
								TITLE,
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
								".$connection->getSqlHelper()->getCurrentDateTimeFunction()." as CREATE_TIME,
								'CrmFolder' as TITLE,
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
						unset($querySql);
					}
				}

				$querySql = "
					SELECT 
						INDICATOR_TYPE,
						OWNER_ID,
						CREATE_TIME,
						TITLE,
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
						'TITLE',
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
				$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

				VolumeTable::dropTemporally();

				$this->setStage(null);
			}
		}

		return $this;
	}

	/**
	 * Returns module storage.
	 * @return \Bitrix\Disk\Storage[]|array
	 */
	public function getStorageList()
	{
		if (count($this->storageList) == 0 || !$this->storageList[0] instanceof \Bitrix\Disk\Storage)
		{
			if ($this->isMeasureAvailable())
			{
				$this->storageList[0] = \Bitrix\Crm\Integration\DiskManager::getStorage();
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
				$typeFolderXmlId = self::getSpecialFolderXmlId();
				if (count($typeFolderXmlId) > 0)
				{
					foreach ($typeFolderXmlId as $xmlId)
					{
						$folder = \Bitrix\Disk\Folder::load(array(
							'=XML_ID' => $xmlId,
							'=STORAGE_ID' => $storage->getId(),
						));

						if (!$folder instanceof \Bitrix\Disk\Folder)
						{
							continue;
						}
						if ($folder->getXmlId() !== $xmlId)
						{
							continue;
						}

						$this->folderList[$storage->getId()][$xmlId] = $folder;
					}
				}

				return $this->folderList[$storage->getId()];
			}
		}

		return array();
	}

	/**
	 * Returns special folder xml_id code list.
	 * @return string[]
	 */
	public static function getSpecialFolderXmlId()
	{
		static $typeFolderXmlId;
		if(!isset($typeFolderXmlId))
		{
			\Bitrix\Main\Loader::includeModule(self::getModuleId());

			$typeFolderXmlId = array(
				//IMS::getFolderXmlID(IMS::EmailAttachment),
				//IMS::getFolderXmlID(IMS::CallRecord),
				IMS::getFolderXmlID(IMS::Rest),
			);
		}

		return $typeFolderXmlId;
	}


	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList()
	{
		static $entityList = array();
		if(count($entityList) == 0)
		{
			\Bitrix\Main\Loader::includeModule(self::getModuleId());

			$entityList = array(
				'\\Bitrix\\Crm\\CompanyTable',
				'\\Bitrix\\Crm\\ContactTable',
				'\\Bitrix\\Crm\\DealTable',
				'\\Bitrix\\Crm\\RequisiteTable',
				'\\Bitrix\\Crm\\InvoiceTable',
				'\\Bitrix\\Crm\\LeadTable',
				'\\Bitrix\\Crm\\QuoteTable',
			);
		}
		return $entityList;
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

		static $crmFolderIds;
		if (empty($crmFolderIds))
		{
			$crmFolderIds = array();
			$crmStorageList = $this->getStorageList();
			foreach ($crmStorageList as $crmStorage)
			{
				$crmFolders = $this->getFolderList($crmStorage);
				if (is_array($crmFolders) && count($crmFolders) > 0)
				{
					foreach ($crmFolders as $crmFolder)
					{
						$crmFolderIds[] = $crmFolder->getId();
					}
				}
			}
		}

		// disallow delete Crm folder
		return (in_array($folder->getId(), $crmFolderIds) === false);
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
		if (count($storageList) > 0)
		{
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
					->addFilter('=STORAGE_ID', $storage->getId())
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

	/**
	 * @param Volume\Fragment $fragment File entity object.
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function getActivity(Volume\Fragment $fragment)
	{
		if($fragment->getIndicatorType() == Volume\File::className())
		{
			$file = $fragment->getFolder();
			if (!$file instanceof \Bitrix\Disk\File)
			{
				throw new ArgumentTypeException('Fragment must be subclass of '.\Bitrix\Disk\File::className());
			}

			return $file->getOriginalName();
		}

		return parent::getTitle($fragment);
	}
}

