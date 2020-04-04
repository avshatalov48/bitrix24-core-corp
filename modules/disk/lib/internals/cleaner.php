<?php

namespace Bitrix\Disk\Internals;


use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable;
use Bitrix\Disk\ObjectTtl;
use Bitrix\Disk\ShowSession;
use Bitrix\Disk\SystemUser;
use Bitrix\Main\Application;
use Bitrix\Main\FileTable as MainFileTable;
use Bitrix\Main\Entity\Query;

final class Cleaner
{
	const DELETE_TYPE_PORTION = 2;
	const DELETE_TYPE_TIME    = 3;

	/**
	 * Returns the fully qualified name of this class.
	 * @return string
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Deletes show session and connected files from cloud.
	 *
	 * @param int $type Deleting type. You can choose delete files by portion or by time.
	 * @param int $limit Limit which will be used for deleting files by portion or by time.
	 * So, count of files which we want to delete or maximum duration of the removal process.
	 * @return string
	 */
	public static function deleteShowSession($type = self::DELETE_TYPE_PORTION, $limit = 10)
	{
		$portion = $limit;
		if($type === self::DELETE_TYPE_TIME)
		{
			$portion = 100;
		}

		$startTime = time();
		foreach(ShowSession::getModelList(array(
			'filter' => array(
				'=IS_EXPIRED' => true,
			),
			'limit' => $portion,
		)) as $showSession)
		{
			if($type === self::DELETE_TYPE_TIME && (time() - $startTime > $limit))
			{
				break;
			}
			$showSession->delete();
		}
		unset($showSession);


		return static::className() . "::deleteShowSession({$type}, {$limit});";
	}

	/**
	 * Deletes unnecessary files, which don't relate to version or object.
	 *
	 * @param int $type Deleting type. You can choose delete files by portion or by time.
	 * @param int $limit Limit which will be used for deleting files by portion or by time.
	 * So, count of files which we want to delete or maximum duration of the removal process.
	 * @return string
	 */
	public static function deleteUnnecessaryFiles($type = self::DELETE_TYPE_PORTION, $limit = 10)
	{
		$portion = $limit;
		if($type === self::DELETE_TYPE_TIME)
		{
			$portion = 100;
		}

		$query = new Query(MainFileTable::getEntity());
		$query
			->addSelect('ID')
			->addFilter('=EXTERNAL_ID', 'unnecessary')
			->addFilter('=MODULE_ID', Driver::INTERNAL_MODULE_ID)
			->setLimit($portion)
		;

		$workLoad = false;
		$dbResult = $query->exec();
		$startTime = time();
		while($row = $dbResult->fetch())
		{
			$workLoad = true;
			if($type === self::DELETE_TYPE_TIME && (time() - $startTime > $limit))
			{
				break;
			}
			\CFile::delete($row['ID']);
		}

		if(!$workLoad)
		{
			return '';
		}

		return static::className() . "::deleteUnnecessaryFiles({$type}, {$limit});";
	}

	/**
	 * Deletes files which have to die by ttl.
	 *
	 * @param int $type Deleting type. You can choose delete files by portion or by time.
	 * @param int $limit Limit which will be used for deleting files by portion or by time.
	 * So, count of files which we want to delete or maximum duration of the removal process.
	 * @return string
	 */
	public static function deleteByTtl($type = self::DELETE_TYPE_PORTION, $limit = 10)
	{
		$portion = $limit;
		if($type === self::DELETE_TYPE_TIME)
		{
			$portion = 100;
		}

		/** @var ObjectTtl[] $ttls */
		$ttls = ObjectTtl::getModelList(array(
			'with' => array('object'),
			'limit' => $portion,
			'order' => array('DEATH_TIME' => 'ASC')
		));

		$workLoad = false;
		$startTime = time();
		foreach ($ttls as $ttl)
		{
			$workLoad = true;
			if($type === self::DELETE_TYPE_TIME && (time() - $startTime > $limit))
			{
				break;
			}

			if ($ttl->isReadyToDeath())
			{
				$ttl->deleteObject(SystemUser::SYSTEM_USER_ID);
				$ttl->delete(SystemUser::SYSTEM_USER_ID);
			}
		}

		if(!$workLoad)
		{
			return '';
		}

		return static::className() . "::deleteByTtl({$type}, {$limit});";
	}

	/**
	 * Deletes old right setup sessions.
	 *
	 * @return string
	 */
	public static function deleteRightSetupSession()
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$tableName = RightSetupSessionTable::getTableName();
		$statusFinished = RightSetupSessionTable::STATUS_FINISHED;

		$deathTime = $sqlHelper->addSecondsToDateTime(60*60*24*31, 'CREATE_TIME');

		$connection->queryExecute("
			  DELETE FROM {$tableName} WHERE STATUS = {$statusFinished} AND NOW() > {$deathTime}
		");

		return static::className() . "::deleteRightSetupSession();";
	}

	public static function emptyOldDeletedLogEntries()
	{
		$deletedLogManager = Driver::getInstance()->getDeletedLogManager();
		$tableClass = $deletedLogManager->getLogTable();
		$tableClass::deleteOldEntries();

		return static::className() . "::emptyOldDeletedLogEntries();";
	}


	/**
	 * Restores storages's missing root folder.
	 *
	 * @return string
	 */
	public static function restoreMissingRootFolder()
	{
		$connection = Application::getConnection();
		$driver = Driver::getInstance();
		$workLoad = false;

		$result = $connection->query("
			SELECT storage.ID, storage.ROOT_OBJECT_ID, storage.NAME, storage.ENTITY_TYPE, storage.ENTITY_ID, storage.SITE_ID 
			FROM b_disk_storage storage left join b_disk_object folder on storage.ROOT_OBJECT_ID = folder.ID 
			WHERE folder.ID is NULL
			LIMIT 10
		");
		while ($row = $result->fetch())
		{
			$workLoad = true;

			$storage = \Bitrix\Disk\Storage::getById($row['ID']);
			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$storage->delete(\Bitrix\Disk\SystemUser::SYSTEM_USER_ID);
			}
			else
			{
				$connection->queryExecute('DELETE FROM b_disk_storage WHERE ID = '.(int)$row['ID']);
			}

			if ($row['ENTITY_TYPE'] === 'Bitrix\Disk\ProxyType\User')
			{
				$storage = $driver->addUserStorage($row['ENTITY_ID']);
			}
			elseif ($row['ENTITY_TYPE'] === 'Bitrix\Disk\ProxyType\Group')
			{
				$storage = $driver->addGroupStorage($row['ENTITY_ID']);
			}
			elseif ($row['ENTITY_TYPE'] === 'Bitrix\Disk\ProxyType\Common')
			{
				$storage = $driver->addCommonStorage(array(
					'NAME' => $row['NAME'],
					'ENTITY_ID' => $row['ENTITY_ID'],
					'SITE_ID' => $row['SITE_ID'],
					'ENTITY_MISC_DATA' => $row['ENTITY_MISC_DATA'],
				), array());

				$data = unserialize($row['ENTITY_MISC_DATA']);
				if(is_array($data) && !empty($data['BASE_URL']))
				{
					$storage->changeBaseUrl($data['BASE_URL']);
				}
			}
		}

		if (!$workLoad)
		{
			return '';
		}

		return static::className(). '::restoreMissingRootFolder();';
	}


	/**
	 * Deletes files that were left after version merge.
	 *
	 * @param int $limit Limit which will be used for deleting files by portion or by time.
	 * @param string $fromDate Starting date.
	 * @param int $timeLimit Agent life time limit.
	 *
	 * @return string
	 */
	public static function deleteUnregisteredVersionFiles($limit = 100, $fromDate = '', $timeLimit = 20)
	{
		$limit = (int)$limit;
		if ($limit <= 0)
		{
			$limit = 100;
		}
		if (empty($fromDate) || strlen($fromDate) != 10)
		{
			$fromDate = '2018-11-14';
		}
		$timeLimit = (int)$timeLimit;
		if (defined('START_EXEC_TIME') && START_EXEC_TIME > 0)
		{
			$startTime = START_EXEC_TIME;
		}
		else
		{
			$startTime = getmicrotime();
		}

		$connection = Application::getConnection();
		$workLoad = false;

		$result = $connection->query("
			select ID
			from b_file 
			where 
				MODULE_ID = 'disk'
				AND ID not in (SELECT FILE_ID FROM b_disk_version) 
				AND ID not in (SELECT FILE_ID FROM b_disk_object 
					WHERE TYPE = ".\Bitrix\Disk\Internals\ObjectTable::TYPE_FILE." 
						AND ID = REAL_OBJECT_ID AND FILE_ID IS NOT NULL) 
				AND SUBDIR not like 'disk_preview/%'
				AND SUBDIR like 'disk/%'
				AND TIMESTAMP_X >= '{$fromDate} 00:00:00'
			LIMIT {$limit}
		");

		while ($row = $result->fetch())
		{
			$workLoad = true;

			\CFile::Delete($row['ID']);

			if ($timeLimit > 0)
			{
				$currentTime = getmicrotime();
				if (($currentTime - $startTime) >= $timeLimit)
				{
					break;
				}
			}
		}

		if (!$workLoad)
		{
			return '';
		}

		return static::className(). "::deleteUnregisteredVersionFiles('{$limit}', '{$fromDate}', '{$timeLimit}');";
	}
}