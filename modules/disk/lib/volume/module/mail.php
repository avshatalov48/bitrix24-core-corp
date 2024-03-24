<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Mail
	extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'mail';

	/** @var Disk\Storage[] */
	private $storageList = [];

	/** @var Disk\Folder[] */
	private $folderList = [];

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array
	{
		return [
			\Bitrix\Mail\Disk\ProxyType\Mail::class
		];
	}

	/**
	 * Returns module storage.
	 * @see \Bitrix\Mail\Helper\Attachment\Storage::getStorage
	 * @return Disk\Storage[]|array
	 */
	public function getStorageList(): array
	{
		if (count($this->storageList) == 0 || !$this->storageList[0] instanceof Disk\Storage)
		{
			$entityTypes = self::getEntityType();
			$storage = Disk\Storage::load([
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => $entityTypes[0]
			]);

			if ($storage instanceof Disk\Storage)
			{
				$this->storageList[] = $storage;
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
		$sqlHelper = $connection->getSqlHelper();
		$indicatorType = $sqlHelper->forSql(static::className());
		$ownerId = (string)$this->getOwner();

		// collect disk statistics
		$this
			->addFilter(0, [
				'LOGIC' => 'OR',
				'MODULE_ID' => self::getModuleId(),
				'ENTITY_TYPE' => \Bitrix\Mail\Disk\ProxyType\Mail::className(),
			])
			->addFilter('DELETED_TYPE', Disk\Internals\ObjectTable::DELETED_TYPE_NONE);

		parent::measure();

		// collect none disk statistics
		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $sqlHelper->getCurrentDateTimeFunction(). " as CREATE_TIME,
				COALESCE(SUM(files.FILE_SIZE), 0) as FILE_SIZE,
				COALESCE(COUNT(files.ID), 0) as FILE_COUNT,
				0 as DISK_SIZE,
				0 as DISK_COUNT
			FROM
				b_file files
				INNER JOIN b_mail_msg_attachment attachment
					ON files.id = attachment.FILE_ID
			GROUP BY
				INDICATOR_TYPE,
				OWNER_ID
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
			],
			$this->getSelect()
		);

		$tableName = $sqlHelper->quote(VolumeTable::getTableName());

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}
}
