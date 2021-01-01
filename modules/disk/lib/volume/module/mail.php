<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Mail
	extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'mail';

	/** @var \Bitrix\Disk\Storage[] */
	private $storageList = array();

	/** @var \Bitrix\Disk\Folder[] */
	private $folderList = array();

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType()
	{
		return array(
			'Bitrix\\Mail\\Disk\\ProxyType\\Mail'
		);
	}

	/**
	 * Returns module storage.
	 * @see \Bitrix\Mail\Helper\Attachment\Storage::getStorage
	 * @return \Bitrix\Disk\Storage[]|array
	 */
	public function getStorageList()
	{
		if (count($this->storageList) == 0 || !$this->storageList[0] instanceof \Bitrix\Disk\Storage)
		{
			$entityTypes = self::getEntityType();
			$storage = \Bitrix\Disk\Storage::load(array(
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => $entityTypes[0]
			));

			if ($storage instanceof \Bitrix\Disk\Storage)
			{
				$this->storageList[] = $storage;
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
			$storage->getId() > 0
		)
		{
			if (
				!isset($this->folderList[$storage->getId()]) ||
				empty($this->folderList[$storage->getId()])
			)
			{
				$this->folderList[$storage->getId()] = array();
				if ($this->isMeasureAvailable())
				{
					$this->folderList[$storage->getId()][] = $storage->getRootObject();
				}
			}

			return $this->folderList[$storage->getId()];
		}

		return array();
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

		// collect disk statistics
		$this
			->addFilter(0, array(
				'LOGIC' => 'OR',
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => \Bitrix\Mail\Disk\ProxyType\Mail::className(),
			))
			->addFilter('DELETED_TYPE', \Bitrix\Disk\Internals\ObjectTable::DELETED_TYPE_NONE);

		parent::measure();

		// collect none disk statistics
		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
				SUM(files.FILE_SIZE) as FILE_SIZE,
				COUNT(files.ID) as FILE_COUNT,
				0 as DISK_SIZE,
				0 as DISK_COUNT
			FROM
				b_file files
				INNER JOIN b_mail_msg_attachment attachment
					ON files.id = attachment.FILE_ID
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
			),
			$this->getSelect()
		);

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}
}
