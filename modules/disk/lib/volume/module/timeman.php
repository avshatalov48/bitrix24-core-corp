<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Timeman extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'timeman';

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

		// Forum comments attachments
		$attachedForumCommentsSql = '';
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('forum') && \Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			$eventTypeXML = ['TIMEMAN'];
			$eventTypeList = ['timeman_entry', 'report'];
			foreach ($eventTypeList as $eventId)
			{
				$forumMetaData = \CSocNetLogTools::getForumCommentMetaData($eventId);
				$eventTypeXML[] = $forumMetaData[0];
			}

			$attachedForumCommentsSql = "
				UNION
				(
					SELECT
						SUM(ver.SIZE) as FILE_SIZE,
						COUNT(ver.FILE_ID) as FILE_COUNT,
						SUM(ver.SIZE) as DISK_SIZE,
						COUNT(DISTINCT files.ID) as DISK_COUNT,
						COUNT(ver.ID) as VERSION_COUNT
					FROM
						b_disk_version ver
						INNER JOIN b_disk_object files
							ON files.ID  = ver.OBJECT_ID
							AND files.TYPE = '".Disk\Internals\ObjectTable::TYPE_FILE."'
							AND files.ID = files.REAL_OBJECT_ID
						INNER JOIN 
						(
							SELECT 
								attached.OBJECT_ID as OBJECT_ID
							FROM 
								b_disk_attached_object attached
								INNER JOIN b_forum_message message 
									ON message.ID = attached.ENTITY_ID
							WHERE
								attached.ENTITY_TYPE = '". $connection->getSqlHelper()->forSql(Disk\Uf\ForumMessageConnector::className()). "'
								AND substring_index(message.XML_ID,'_', 1) IN('". implode("','", $eventTypeXML). "')
							GROUP BY 
								attached.OBJECT_ID
							ORDER BY NULL
						) attach_connect
							ON attach_connect.OBJECT_ID = files.ID
				)
			";
		}

		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $connection->getSqlHelper()->getCurrentDateTimeFunction(). " as CREATE_TIME,
				SUM(src.FILE_SIZE) as FILE_SIZE,
				SUM(src.FILE_COUNT) as FILE_COUNT,
				SUM(src.DISK_SIZE) as DISK_SIZE,
				SUM(src.DISK_COUNT) as DISK_COUNT,
				SUM(src.VERSION_COUNT) as VERSION_COUNT
			FROM 
			(
				(
					SELECT 
						SUM(files.FILE_SIZE) as FILE_SIZE,
						COUNT(DISTINCT files.ID) as FILE_COUNT,
						0 as DISK_SIZE,
						0 as DISK_COUNT,
						0 as VERSION_COUNT
					FROM 
						b_file files
					WHERE
						files.MODULE_ID = '".self::getModuleId()."'
				)
				{$attachedForumCommentsSql}
			) src
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
			],
			$this->getSelect()
		);

		$tableName = VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}
}
