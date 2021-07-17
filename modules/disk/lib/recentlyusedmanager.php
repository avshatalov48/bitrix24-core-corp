<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\RecentlyUsedTable;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;

final class RecentlyUsedManager
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	/**
	 * Constructor RecentlyUsedManager.
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Push in recently used new object.
	 * @param mixed|int|User|\CAllUser $user User.
	 * @param int|\Bitrix\Disk\Internals\Model $object Object id.
	 * @return bool
	 */
	public function push($user, $object)
	{
		$userId = User::resolveUserId($user);
		if (!$userId)
		{
			$this->errorCollection->addOne(new Error('Could not get user id.'));
			return false;
		}
		$objectId = ($object instanceof Disk\Internals\Model ? $object->getId() : (int)$object);

		$rows = RecentlyUsedTable::getList(
			[
				'select' => ['ID'],
				'filter' => [
					'=OBJECT_ID' => $objectId,
					'=USER_ID' => $userId,
				]
			]
		);

		$alreadyUpdateTime = false;
		foreach ($rows as $row)
		{
			if (!$alreadyUpdateTime)
			{
				$result = RecentlyUsedTable::update($row['ID'], [
					'CREATE_TIME' => new DateTime(),
				]);

				$alreadyUpdateTime = $result->isSuccess();
			}
			else
			{
				RecentlyUsedTable::delete($row['ID']);
			}
		}

		if (!$alreadyUpdateTime)
		{
			$result = RecentlyUsedTable::add(array(
				'OBJECT_ID' => $objectId,
				'USER_ID' => $userId,
			));

			$alreadyUpdateTime = $result->isSuccess();
		}

		if ($object instanceof File)
		{
			Disk\Driver::getInstance()->getTrackedObjectManager()->pushFile($userId, $object);
		}

		if (!$alreadyUpdateTime)
		{
			return false;
		}

		RecentlyUsedTable::deleteOldObjects($userId);

		return true;
	}

	public function remove($user, $objectId): bool
	{
		$userId = User::resolveUserId($user);
		if (!$userId)
		{
			$this->errorCollection[] = new Error('Could not get user id.');

			return false;
		}

		RecentlyUsedTable::deleteBatch([
			'USER_ID' => $userId,
			'OBJECT_ID' => (int)$objectId,
		]);

		return true;
	}

	/**
	 * Fixes cold start, when we don't have any data in RecentlyUsedTable.
	 * @param mixed|int|User|\CAllUser $user User.
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function fixColdStart($user)
	{
		$userId = User::resolveUserId($user);
		if(!$userId)
		{
			$this->errorCollection->addOne(new Error('Could not get user id.'));
			return false;
		}
		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			$this->errorCollection->addOne(new Error('Could not get storage by user id.'));
			return false;
		}

		$fromDate = DateTime::createFromTimestamp(time() - 14*24*3600);

		$objects = array();
		$query = FileTable::getList(array(
			'select' => array('ID', 'UPDATE_TIME'),
			'filter' => array(
				'STORAGE_ID' => $storage->getId(),
				'TYPE' => ObjectTable::TYPE_FILE,
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
				'>UPDATE_TIME' => $fromDate,
				array(
					'LOGIC' => 'OR',
					array(
						'CREATED_BY' => $userId,
					),
					array(
						'UPDATED_BY' => $userId,
					),
				),
			),
			'order' => array('UPDATE_TIME' => 'DESC'),
			'limit' => RecentlyUsedTable::MAX_COUNT_FOR_USER,
		));
		while($row = $query->fetch())
		{
			$objects[] = array(
				'USER_ID' => $userId,
				'OBJECT_ID' => $row['ID'],
				'CREATE_TIME' => $row['UPDATE_TIME'],
			);
		}
		unset($row, $query, $fromDate);

		Collection::sortByColumn($objects, array('CREATE_TIME' => SORT_ASC));
		RecentlyUsedTable::insertBatch($objects);

		return true;
	}

	/**
	 * Returns list of recently files by user.
	 * @param mixed|int|User|\CAllUser $user User.
	 * @param array                    $filter Filter.
	 * @return File[]
	 * @internal
	 */
	public function getFileModelListByUser($user, array $filter = array())
	{
		$userId = User::resolveUserId($user);
		if(!$userId)
		{
			$this->errorCollection->addOne(new Error('Could not get user id.'));
			return array();
		}

		$driver = Driver::getInstance();
		$storage = $driver->getStorageByUserId($userId);
		if(!$storage)
		{
			$this->errorCollection->addOne(new Error('Could not get storage by user id.'));
			return array();
		}

		if($this->isFirstRun($userId))
		{
			if(!$this->hasData($userId))
			{
				$this->fixColdStart($userId);
			}
			$this->saveFirstRun($userId);
		}

		$securityContext = $storage->getCurrentUserSecurityContext();
		$parameters = array(
			'filter' => array(
				'RECENTLY_USED.USER_ID' => $userId,
				'DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
				'TYPE' => ObjectTable::TYPE_FILE,
			),
			'order' => array('RECENTLY_USED.CREATE_TIME' => 'DESC'),
			'limit' => RecentlyUsedTable::MAX_COUNT_FOR_USER,
		);

		if($filter)
		{
			$parameters['filter'] = array_merge($parameters['filter'], $filter);
		}

		$parameters = $driver->getRightsManager()->addRightsCheck(
			$securityContext,
			$parameters,
			array('ID', 'CREATED_BY')
		);

		return File::getModelList($parameters);
	}

	public function getFileListByUser($user, array $filter = array())
	{
		$items = array();
		$urlManager = Driver::getInstance()->getUrlManager();
		foreach($this->getFileModelListByUser($user, $filter) as $file)
		{
			$id = FileUserType::NEW_FILE_PREFIX . $file->getId();
			$items[$id] = array(
				'id' => $id,
				'name' => $file->getName(),
				'type' => 'file',
				'size' => \CFile::formatSize($file->getSize()),
				'sizeInt' => $file->getSize(),
				'modifyBy' => $file->getUpdateUser()->getFormattedName(),
				'modifyDate' => $file->getUpdateTime()->format('d.m.Y'),
				'modifyDateInt' => $file->getUpdateTime()->getTimestamp(),
				'ext' => $file->getExtension(),
			);
			if (TypeFile::isImage($file))
			{
				$items[$id]['previewUrl'] = $urlManager->getUrlForShowFile($file);
			}
			$fileType = $file->getView()->getEditorTypeFile();
			if(!empty($fileType))
			{
				$items[$id]['fileType'] = $fileType;
			}

		}
		unset($file);

		return $items;
	}

	private function hasData($userId)
	{
		return (bool)RecentlyUsedTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'USER_ID' => $userId,
			),
			'limit' => 1,
		))->fetch();
	}

	private function isFirstRun($userId)
	{
		$userSettings = \CUserOptions::getOption(Driver::INTERNAL_MODULE_ID, 'recently_used', array('r' => ''), $userId);
		return empty($userSettings['r']);
	}

	private function saveFirstRun($userId)
	{
		\CUserOptions::setOption(Driver::INTERNAL_MODULE_ID, 'recently_used', array('r' => '1'), false, $userId);
	}
}