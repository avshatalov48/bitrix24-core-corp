<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Volume;
use Bitrix\Disk\Internals\ObjectTable;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Socialnetwork extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'socialnetwork';

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

		$attachedEntityList = $this->getAttachedEntityList();
		$attachedEntitySql = '';
		if (count($attachedEntityList) > 0)
		{
			foreach ($attachedEntityList as $attachedEntity)
			{
				if ($attachedEntitySql != '')
				{
					$attachedEntitySql .= ', ';
				}
				$attachedEntitySql .= "'".$connection->getSqlHelper()->forSql($attachedEntity)."'";
			}
		}

		// Scan User fields specific to module
		$entityUserFieldSource = $this->prepareUserFieldSourceSql(null, array(\CUserTypeFile::USER_TYPE_ID));
		if ($entityUserFieldSource != '')
		{
			$entityUserFieldSource = " UNION {$entityUserFieldSource} ";
		}

		// Forum comments attachments
		$attachedForumCommentsSql = '';
		if (\Bitrix\Main\ModuleManager::isModuleInstalled('forum') && \Bitrix\Main\Loader::includeModule('forum'))
		{
			$eventTypeXML = array();
			$eventTypeList = array('sonet', 'forum', 'photo_photo', 'news');
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
								attached.ENTITY_TYPE = '". $connection->getSqlHelper()->forSql(\Bitrix\Disk\Uf\ForumMessageConnector::className()). "'
								AND substring_index(message.XML_ID,'_', 1) IN('". implode("','", $eventTypeXML). "')
							GROUP BY 
								attached.OBJECT_ID
							ORDER BY NULL
						) attach_connect
							ON attach_connect.OBJECT_ID = files.ID
				)
			";
		}

		if (\Bitrix\Main\ModuleManager::isModuleInstalled('crm') && \Bitrix\Main\Loader::includeModule('crm'))
		{
			$logTable = \Bitrix\Socialnetwork\LogTable::getTableName();

			$excludeEventType = array(
				\CCrmLiveFeedEntity::Lead,
				\CCrmLiveFeedEntity::Contact,
				\CCrmLiveFeedEntity::Company,
				\CCrmLiveFeedEntity::Deal,
				\CCrmLiveFeedEntity::Activity,
				\CCrmLiveFeedEntity::Invoice,
			);

			$attachedSql = "
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
							b_disk_attached_object attached, 
							{$logTable} live_feed_log 
						WHERE
							attached.ENTITY_ID = live_feed_log.ID
							AND attached.ENTITY_TYPE IN($attachedEntitySql)
							AND live_feed_log.ENTITY_TYPE NOT IN ('". implode("','", $excludeEventType). "')
						GROUP BY 
							attached.OBJECT_ID
						ORDER BY NULL
					) attach_connect
						ON attach_connect.OBJECT_ID = files.ID
			";
		}
		else
		{
			$attachedSql = "
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
						ORDER BY NULL
					) attach_connect
						ON attach_connect.OBJECT_ID = files.ID
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
				({$attachedSql})
				{$attachedForumCommentsSql}
				{$entityUserFieldSource}
			) src
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
				'VERSION_COUNT',
			),
			$this->getSelect()
		);

		$tableName = \Bitrix\Disk\Internals\VolumeTable::getTableName();

		$connection->queryExecute("INSERT INTO {$tableName} ({$columnList}) {$querySql}");

		return $this;
	}

	/**
	 * Returns entity list with user field corresponding to module.
	 * @return string[]
	 */
	public function getEntityList()
	{
		static $entityList;
		if (!isset($entityList))
		{
			$entityList = array();

			$filter = array(
				'=ENTITY_ID' => array(
					\Bitrix\Socialnetwork\Livefeed\Provider::DATA_ENTITY_TYPE_BLOG_POST, //'BLOG_POST',
					\Bitrix\Socialnetwork\Livefeed\Provider::DATA_ENTITY_TYPE_BLOG_COMMENT, //'BLOG_COMMENT',
					\Bitrix\Socialnetwork\Livefeed\LogEvent::PROVIDER_ID, //'SONET_LOG',
					\Bitrix\Socialnetwork\Livefeed\LogComment::PROVIDER_ID, //'SONET_COMMENT',
					'FORUM_MESSAGE',
				),
				'=USER_TYPE_ID' => array(
					\CUserTypeFile::USER_TYPE_ID,
					\Bitrix\Disk\Uf\FileUserType::USER_TYPE_ID,
					\Bitrix\Disk\Uf\VersionUserType::USER_TYPE_ID,
				),
			);
			$userFieldList = \Bitrix\Main\UserFieldTable::getList(array('filter' => $filter));
			if ($userFieldList->getSelectedRowsCount() > 0)
			{
				foreach ($userFieldList as $userField)
				{
					$entityName = $userField['ENTITY_ID'];
					if (isset($entityList[$entityName]))
					{
						continue;
					}

					//$entity[$entityName][] = $userField;

					/** @var \Bitrix\Main\Entity\Base $ent */
					$ent = \Bitrix\Main\Entity\Base::compileEntity($entityName, array(), array(
						'namespace' => __NAMESPACE__,
						'uf_id'     => $entityName,
					));

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
	public function getAttachedEntityList()
	{
		$attachedEntityList = array(
			\Bitrix\Disk\Uf\BlogPostConnector::className(),
			\Bitrix\Disk\Uf\BlogPostCommentConnector::className(),
			\Bitrix\Disk\Uf\SonetLogConnector::className(),
			\Bitrix\Disk\Uf\SonetCommentConnector::className(),
			//\Bitrix\Disk\Uf\ForumMessageConnector::className(),
		);

		return $attachedEntityList;
	}

	/**
	 * @param Volume\Fragment $fragment Module description structure.
	 * @return string
	 */
	public static function getTitle(Volume\Fragment $fragment)
	{
		Loc::loadMessages(__FILE__);
		return Loc::getMessage('DISK_VOLUME_MODULE_SOCIALNETWORK');
	}
}

