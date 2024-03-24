<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Main\DB;
use Bitrix\Disk;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internals\VolumeTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Calendar extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'calendar';

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

		$attachedEntityList = $this->getAttachedEntityList();
		$attachedEntitySql = '';
		if (count($attachedEntityList) > 0)
		{
			foreach ($attachedEntityList as $attachedEntity)
			{
				if ($attachedEntitySql != '') $attachedEntitySql .= ', ';
				$attachedEntitySql .= "'".$sqlHelper->forSql($attachedEntity)."'";
			}
		}

		// Scan User fields specific to module
		$entityUserFieldSource = $this->prepareUserFieldSourceSql(null, [\CUserTypeFile::USER_TYPE_ID]);
		if ($entityUserFieldSource != '')
		{
			$entityUserFieldSource = " UNION {$entityUserFieldSource} ";
		}

		$prefSql = '';
		if ($connection instanceof DB\MysqlCommonConnection)
		{
			$prefSql = 'ORDER BY NULL';
		}

		// Forum comments attachments
		$attachedForumCommentsSql = '';
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('forum') && \Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			$forumMetaData = \CSocNetLogTools::getForumCommentMetaData('lists_new_element');
			$eventTypeXML = $forumMetaData[0];

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
								attached.ENTITY_TYPE = '". $sqlHelper->forSql(Disk\Uf\ForumMessageConnector::className()). "'
								AND substring_index(message.XML_ID,'_', 1) = '{$eventTypeXML}'
							GROUP BY 
								attached.OBJECT_ID
							{$prefSql}
						) attach_connect
							ON attach_connect.OBJECT_ID = files.ID
				)
			";
		}

		$querySql = "
			SELECT 
				'{$indicatorType}' as INDICATOR_TYPE,
				{$ownerId} as OWNER_ID,
				". $sqlHelper->getCurrentDateTimeFunction(). " as CREATE_TIME,
				COALESCE(SUM(src.FILE_SIZE), 0) as FILE_SIZE,
				COALESCE(SUM(src.FILE_COUNT), 0) as FILE_COUNT,
				COALESCE(SUM(src.DISK_SIZE), 0) as DISK_SIZE,
				COALESCE(SUM(src.DISK_COUNT), 0) as DISK_COUNT,
				COALESCE(SUM(src.VERSION_COUNT), 0) as VERSION_COUNT
			FROM 
			(
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
							AND files.TYPE = '".ObjectTable::TYPE_FILE."'
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
								attached.ENTITY_TYPE IN($attachedEntitySql)
							GROUP BY 
								attached.OBJECT_ID
							{$prefSql}
						) attach_connect
							ON attach_connect.OBJECT_ID = files.ID
				)
				{$attachedForumCommentsSql}
				{$entityUserFieldSource}
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

		$tableName = $sqlHelper->quote(VolumeTable::getTableName());

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}

	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList(): array
	{
		static $entityList;
		if (!isset($entityList))
		{
			$entityList = [];

			$filter = [
				'=ENTITY_ID' => 'CALENDAR_EVENT',
				'=USER_TYPE_ID' => [
					\CUserTypeFile::USER_TYPE_ID,
					Disk\Uf\FileUserType::USER_TYPE_ID,
					Disk\Uf\VersionUserType::USER_TYPE_ID,
				],
			];
			$userFieldList = \Bitrix\Main\UserFieldTable::getList(['filter' => $filter]);
			if ($userFieldList->getSelectedRowsCount() > 0)
			{
				foreach ($userFieldList as $userField)
				{
					$entityName = $userField['ENTITY_ID'];
					if (isset($entityList[$entityName]))
					{
						continue;
					}

					$entity[$entityName][] = $userField;

					/** @var \Bitrix\Main\Entity\Base $ent */
					$ent = \Bitrix\Main\Entity\Base::compileEntity($entityName, [], [
						'namespace' => __NAMESPACE__,
						'uf_id'     => $entityName,
					]);

					$entityList[$entityName] = $ent->getDataClass();
				}
			}
		}

		return $entityList;
	}

	/**
	 * Returns entity list attached to disk object corresponding to module.
	 * @return string[]
	 */
	public function getAttachedEntityList(): array
	{
		return [
			Disk\Uf\CalendarEventConnector::class,
		];
	}
}
