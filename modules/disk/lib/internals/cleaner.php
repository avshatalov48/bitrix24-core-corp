<?php

namespace Bitrix\Disk\Internals;


use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Rights\Table\RightSetupSessionTable;
use Bitrix\Disk\ObjectLock;
use Bitrix\Disk\ObjectTtl;
use Bitrix\Disk\ShowSession;
use Bitrix\Disk\SystemUser;
use Bitrix\Disk\Version;
use Bitrix\Main\Application;
use Bitrix\Main\FileTable as MainFileTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Type\DateTime;

final class Cleaner
{
	public const DELETE_TYPE_PORTION = 2;
	public const DELETE_TYPE_TIME    = 3;

	/**
	 * Returns the fully qualified name of this class.
	 * @return string
	 */
	public static function className()
	{
		return self::class;
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


		return self::class  . "::deleteShowSession({$type}, {$limit});";
	}

	/**
	 * Releases object locks which are expired by module settings.
	 *
	 * @return void
	 */
	public static function releaseObjectLocks()
	{
		if (!Configuration::isEnabledObjectLock())
		{
			return;
		}

		$minutesToAutoReleaseObjectLock = Configuration::getMinutesToAutoReleaseObjectLock();
		if (!$minutesToAutoReleaseObjectLock || $minutesToAutoReleaseObjectLock < 0)
		{
			return;
		}

		foreach(ObjectLock::getModelList([
			'filter' => [
				'=IS_READY_AUTO_UNLOCK' => true,
			],
			'with' => ['OBJECT'],
			'limit' => 100,
		]) as $lock)
		{
			if (!$lock->shouldProcessAutoUnlock())
			{
				continue;
			}

			$baseObject = $lock->getObject();
			if ($baseObject instanceof File)
			{
				$baseObject->unlock(SystemUser::SYSTEM_USER_ID);
			}
			else
			{
				$lock->delete(SystemUser::SYSTEM_USER_ID);
			}
		}
	}

	public static function releaseObjectLocksAgent(): string
	{
		self::releaseObjectLocks();

		return self::class . "::releaseObjectLocksAgent();";
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

		return self::class  . "::deleteUnnecessaryFiles({$type}, {$limit});";
	}

	public static function deleteVersionsByTtlAgent($type = self::DELETE_TYPE_PORTION, $limit = 10): string
	{
		self::deleteVersionsByTtl($type, $limit);

		return self::class . "::deleteVersionsByTtlAgent({$type}, {$limit});";
	}

	public static function deleteVersionsByTtl($type = self::DELETE_TYPE_PORTION, $limit = 10): void
	{
		$dayLimit = Configuration::getFileVersionTtl();
		if ($dayLimit === -1)
		{
			return;
		}

		$portion = $limit;
		if ($type === self::DELETE_TYPE_TIME)
		{
			$portion = 100;
		}

		$connection = Application::getConnection();
		$ttlTime = $connection->getSqlHelper()->convertToDbDateTime(
			DateTime::createFromTimestamp(time() - $dayLimit * 86400)
		);

		$result = $connection->query( "
			SELECT v.ID
			FROM b_disk_version v
			INNER JOIN b_disk_object obj ON obj.ID = v.OBJECT_ID
			LEFT JOIN b_disk_attached_object atta ON v.OBJECT_ID = atta.OBJECT_ID AND v.ID = atta.VERSION_ID
			WHERE
				atta.OBJECT_ID IS NULL AND
				v.CREATE_TIME < {$ttlTime} AND
				v.FILE_ID <> obj.FILE_ID
			ORDER BY v.ID ASC
			LIMIT {$portion}
		");

		$versionIds = [];
		foreach ($result as $row)
		{
			$versionIds[] = $row['ID'];
		}

		if (!$versionIds)
		{
			return;
		}

		$versions = Version::getModelList([
			'filter' => [
				'@ID' => $versionIds,
			]
		]);

		$startTime = time();
		foreach ($versions as $version)
		{
			if ($type === self::DELETE_TYPE_TIME && (time() - $startTime > $limit))
			{
				break;
			}

			$version->delete(SystemUser::SYSTEM_USER_ID);
		}
	}

	public static function deleteTrashCanFilesByTtlAgent($type = self::DELETE_TYPE_PORTION, $limit = 10): string
	{
		self::deleteTrashCanFilesByTtl($type, $limit);

		return self::class . "::deleteTrashCanFilesByTtlAgent({$type}, {$limit});";
	}

	public static function deleteTrashCanFilesByTtl($type = self::DELETE_TYPE_PORTION, $limit = 10): void
	{
		$ttl = Configuration::getTrashCanTtl();
		if ($ttl === -1)
		{
			return;
		}

		$portion = $limit;
		if ($type === self::DELETE_TYPE_TIME)
		{
			$portion = 100;
		}

		$connection = Application::getConnection();
		$ttlTime = $connection->getSqlHelper()->convertToDbDateTime(
			DateTime::createFromTimestamp(time() - $ttl * 86400)
		);
		$deletedTypeNone = ObjectTable::DELETED_TYPE_NONE;
		$fileType = ObjectTable::TYPE_FILE;

		$result = $connection->query( "
			SELECT obj.ID
			FROM b_disk_object obj
			INNER JOIN b_disk_deleted_log_v2 log ON log.OBJECT_ID = obj.ID
			WHERE
			    obj.STORAGE_ID = log.STORAGE_ID AND
			    log.CREATE_TIME < {$ttlTime} AND
			    obj.DELETED_TYPE > {$deletedTypeNone} AND 
			    obj.TYPE = {$fileType}
			LIMIT {$portion}
		");

		$objectIds = [];
		foreach ($result as $row)
		{
			$objectIds[] = $row['ID'];
		}

		if (!$objectIds)
		{
			return;
		}

		$objects = BaseObject::getModelList([
			'filter' => [
				'@ID' => $objectIds,
			]
		]);

		$startTime = time();
		foreach ($objects as $object)
		{
			if ($type === self::DELETE_TYPE_TIME && (time() - $startTime > $limit))
			{
				break;
			}

			if ($object instanceof File)
			{
				$object->delete(SystemUser::SYSTEM_USER_ID);
			}
		}
	}

	public static function deleteTrashCanEmptyFolderByTtlAgent($type = self::DELETE_TYPE_PORTION, $limit = 10): string
	{
		self::deleteTrashCanEmptyFolderByTtl($type, $limit);

		return self::class . "::deleteTrashCanEmptyFolderByTtlAgent({$type}, {$limit});";
	}

	public static function deleteTrashCanEmptyFolderByTtl($type = self::DELETE_TYPE_PORTION, $limit = 10): void
	{
		$ttl = Configuration::getTrashCanTtl();
		if ($ttl === -1)
		{
			return;
		}

		$portion = $limit;
		if ($type === self::DELETE_TYPE_TIME)
		{
			$portion = 100;
		}

		$connection = Application::getConnection();
		$ttlTime = $connection->getSqlHelper()->convertToDbDateTime(
			DateTime::createFromTimestamp(time() - $ttl * 86400)
		);
		$deletedTypeNone = ObjectTable::DELETED_TYPE_NONE;
		$folderType = ObjectTable::TYPE_FOLDER;

		$result = $connection->query( "
			SELECT obj.ID
			FROM b_disk_object obj
			INNER JOIN b_disk_deleted_log_v2 log ON log.OBJECT_ID = obj.ID
			WHERE
				NOT EXISTS(SELECT 'x' FROM b_disk_object p WHERE p.PARENT_ID = obj.ID) AND
			    obj.STORAGE_ID = log.STORAGE_ID AND
			    log.CREATE_TIME < {$ttlTime} AND
			    obj.DELETED_TYPE > {$deletedTypeNone} AND 
			    obj.TYPE = {$folderType}
			LIMIT {$portion}
		");

		$objectIds = [];
		foreach ($result as $row)
		{
			$objectIds[] = $row['ID'];
		}

		if (!$objectIds)
		{
			return;
		}

		$objects = BaseObject::getModelList([
			'filter' => [
				'@ID' => $objectIds,
			]
		]);

		$startTime = time();
		foreach ($objects as $object)
		{
			if ($type === self::DELETE_TYPE_TIME && (time() - $startTime > $limit))
			{
				break;
			}

			if ($object instanceof Folder)
			{
				$object->deleteTree(SystemUser::SYSTEM_USER_ID);
			}
		}
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

		return self::class  . "::deleteByTtl({$type}, {$limit});";
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

		return self::class  . "::deleteRightSetupSession();";
	}

	public static function emptyOldDeletedLogEntries()
	{
		$deletedLogManager = Driver::getInstance()->getDeletedLogManager();
		$tableClass = $deletedLogManager->getLogTable();
		$tableClass::deleteOldEntries();

		return self::class  . "::emptyOldDeletedLogEntries();";
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

			if ($row['ENTITY_TYPE'] === \Bitrix\Disk\ProxyType\User::class)
			{
				$storage = $driver->addUserStorage($row['ENTITY_ID']);
			}
			elseif ($row['ENTITY_TYPE'] === \Bitrix\Disk\ProxyType\Group::class)
			{
				$storage = $driver->addGroupStorage($row['ENTITY_ID']);
			}
			elseif ($row['ENTITY_TYPE'] === \Bitrix\Disk\ProxyType\Common::class)
			{
				$storage = $driver->addCommonStorage(array(
					'NAME' => $row['NAME'],
					'ENTITY_ID' => $row['ENTITY_ID'],
					'SITE_ID' => $row['SITE_ID'],
					'ENTITY_MISC_DATA' => $row['ENTITY_MISC_DATA'],
				), array());

				$data = unserialize($row['ENTITY_MISC_DATA'], ['allowed_classes' => false]);
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

		return self::class . '::restoreMissingRootFolder();';
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
		if (empty($fromDate) || mb_strlen($fromDate) != 10)
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
			$startTime = microtime(true);
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
				$currentTime = microtime(true);
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

		return self::class . "::deleteUnregisteredVersionFiles('{$limit}', '{$fromDate}', '{$timeLimit}');";
	}
}