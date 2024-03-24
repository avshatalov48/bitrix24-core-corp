<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Subscribe extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'subscribe';

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
				INNER JOIN b_posting_file attachment
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
