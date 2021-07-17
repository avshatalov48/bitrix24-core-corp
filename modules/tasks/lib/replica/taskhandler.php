<?php
namespace Bitrix\Tasks\Replica;
//	GUID varchar(50) DEFAULT NULL,
//	EXCHANGE_ID varchar(196) DEFAULT NULL,
//	EXCHANGE_MODIFIED varchar(196) DEFAULT NULL,
//	GROUP_ID int(11) DEFAULT NULL,
//	PARENT_ID int(11) DEFAULT NULL,
//	FORKED_BY_TEMPLATE_ID int(11) DEFAULT NULL,

use Bitrix\Main;
use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\SearchIndex;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;

class TaskHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_tasks";
	protected $moduleId = "tasks";

	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array(
		"RESPONSIBLE_ID" => "b_user.ID",
	);
	protected $translation = array(
		"ID" => "b_tasks.ID",
		"CREATED_BY" => "b_user.ID",
		"CHANGED_BY" => "b_user.ID",
		"STATUS_CHANGED_BY" => "b_user.ID",
		"CLOSED_BY" => "b_user.ID",
		"RESPONSIBLE_ID" => "b_user.ID",
		//"PARENT_ID" => "b_tasks.ID",
		//SITE_ID ???
		"FORUM_TOPIC_ID" => "b_forum_topic.ID",
	);
	protected $children = array(
		array("ID" => "b_tasks_member.TASK_ID"),
		array("ID" => "b_tasks_tag.TASK_ID"),
		array("ID" => "b_tasks_viewed.TASK_ID"),
		array("ID" => "b_tasks_log.TASK_ID"),
		array("ID" => "b_tasks_elapsed_time.TASK_ID"),
		array("ID" => "b_tasks_reminder.TASK_ID"),
		array("ID" => "b_tasks_checklist_items.TASK_ID"),
	);

	protected $fileHandler = null;

	/**
	 * TaskHandler constructor.
	 */
	public function __construct()
	{
		$this->fileHandler = new \Bitrix\Tasks\Replica\TaskAttachmentHandler();
	}

	/**
	 * Registers event handlers for database operations like add new, update or delete.
	 *
	 * @return void
	 */
	public function initDataManagerEvents()
	{
		parent::initDataManagerEvents();
		$this->fileHandler->initDataManagerEvents();
	}

	/**
	 * Tasks event onTaskAdd handler.
	 *
	 * @param integer $id Task identifier.
	 * @param array &$data Task record.
	 *
	 * @return void
	 * @see \CTask::Add()
	 */
	public function onTaskAdd($id, &$data)
	{
		$op = \Bitrix\Replica\Db\Operation::writeInsert($this->tableName, array('ID'), array('ID' => $id));
		$nodes = $op->getTableRecord()->getNodes(true, false);
		if ($nodes)
		{
			$this->fileHandler->onAfterAdd($id, $data);
		}
	}

	/**
	 * Tasks event onBeforeTaskUpdate handler.
	 *
	 * @param integer $id Task identifier.
	 * @param array &$data Task record.
	 * @param array &$copy Task record before update.
	 *
	 * @return void
	 * @see \CTask::Update()
	 */
	public function onBeforeTaskUpdate($id, &$data, &$copy)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($this->tableName, array('ID'), array('ID' => $id));
		if ($map)
		{
			$this->fileHandler->onBeforeUpdate($id);
		}
	}

	/**
	 * Tasks event onTaskUpdate handler.
	 *
	 * @param integer $id Task identifier.
	 * @param array &$data Task record.
	 * @param array &$copy Task record before update.
	 *
	 * @return void
	 * @see \CTask::Update()
	 */
	public function onTaskUpdate($id, &$data, &$copy)
	{
		$op = \Bitrix\Replica\Db\Operation::writeUpdate($this->tableName, array('ID'), array('ID' => $id));
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($this->tableName, array('ID'), array('ID' => $id));
		if ($map)
		{
			$nodes = current($map);
			$this->fileHandler->onAfterUpdate($id, $data, $nodes);
		}
	}

	/**
	 * Tasks event onTaskDelete handler.
	 *
	 * @param integer $id Task identifier.
	 *
	 * @return void
	 * @see \CTask::Delete()
	 */
	public function onTaskDelete($id)
	{
		$op = \Bitrix\Replica\Db\Operation::writeUpdate($this->tableName, array('ID'), array('ID' => $id));
	}

	/**
	 * Tasks event onBeforeTaskZombieDelete handler.
	 *
	 * @param integer $id Task identifier.
	 *
	 * @return void
	 * @see \CTask::terminateZombie()
	 */
	public function onBeforeTaskZombieDelete($id)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($this->tableName, array('ID'), array('ID' => $id));
		if ($map)
		{
			$this->fileHandler->onBeforeDelete($id);
		}
	}

	/**
	 * Tasks event onTaskZombieDelete handler.
	 *
	 * @param integer $id Task identifier.
	 *
	 * @return void
	 * @see \CTask::terminateZombie()
	 */
	public function onTaskZombieDelete($id)
	{
		$this->fileHandler->onAfterDelete($id);
		$op = \Bitrix\Replica\Db\Operation::writeDelete($this->tableName, array('ID'), array('ID' => $id));
	}

	/**
	 * Method will be invoked after writing an missed record.
	 *
	 * @param array $record All fields of the record.
	 *
	 * @return void
	 */
	public function afterWriteMissing(array $record)
	{
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($this->tableName, array('ID'), array('ID' => $record["ID"]));
		if ($map)
		{
			$this->fileHandler->onAfterAdd($record["ID"], $record);
		}
	}

	/**
	 * Called before record transformed for log writing.
	 *
	 * @param array &$record Database record.
	 *
	 * @return void
	 */
	public function beforeLogFormat(array &$record)
	{
		$this->fileHandler->replaceFilesWithGuids($record["ID"], $record);
	}

	/**
	 * Method will be invoked before new database record inserted.
	 * When an array returned the insert will be cancelled and map for
	 * returned record will be added.
	 *
	 * @param array &$newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	public function beforeInsertTrigger(array &$newRecord)
	{
		unset($newRecord["ADD_IN_REPORT"]);
		$newRecord["GROUP_ID"] = 0;
		unset($newRecord["FORKED_BY_TEMPLATE_ID"]);
		unset($newRecord["STAGE_ID"]);
		$fixed = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1", $newRecord["DESCRIPTION"]);
		if ($fixed != null)
		{
			$newRecord["DESCRIPTION"] = $fixed;
		}
		$this->fileHandler->replaceGuidsWithFiles($newRecord);
	}

	/**
	 * Method will be invoked before an database record updated.
	 *
	 * @param array $oldRecord All fields before update.
	 * @param array &$newRecord All fields after update.
	 *
	 * @return void
	 */
	public function beforeUpdateTrigger(array $oldRecord, array &$newRecord)
	{
		unset($newRecord["ADD_IN_REPORT"]);
		unset($newRecord["GROUP_ID"]);
		unset($newRecord["FORKED_BY_TEMPLATE_ID"]);
		unset($newRecord["STAGE_ID"]);
		$fixed = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1", $newRecord["DESCRIPTION"]);
		if ($fixed != null)
		{
			$newRecord["DESCRIPTION"] = $fixed;
		}
		$this->fileHandler->replaceGuidsWithFiles($newRecord);
	}

	/**
	 * Method will be invoked after new database record inserted.
	 *
	 * @param array $newRecord All fields of inserted record.
	 *
	 * @return void
	 */
	public function afterInsertTrigger(array $newRecord)
	{
		global $CACHE_MANAGER;

		$newRecord['ACCOMPLICES'] = array();
		$newRecord['AUDITORS'] = array();
		$list = \Bitrix\Tasks\Internals\Task\MemberTable::getList(array(
				"filter" => array(
				"=TASK_ID" => $newRecord["ID"],
			),
		));
		while ($item = $list->fetch())
		{
			if ($item['TYPE'] == 'A')
				$newRecord['ACCOMPLICES'][] = $item['USER_ID'];
			elseif ($item['TYPE'] == 'U')
				$newRecord['AUDITORS'][] = $item['USER_ID'];
		}

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_ADD,
			$newRecord
		);

		\CTaskNotifications::sendAddMessage(
			array_merge($newRecord, array('CHANGED_BY' => $newRecord['CREATED_BY'])),
			array('SPAWNED_BY_AGENT' => true)
		);

		//\CTaskSync::AddItem($arFields); // MS Exchange

		\CTasks::Index($newRecord, $newRecord["TAGS"]);
		SearchIndex::setTaskSearchIndex($newRecord['ID'], $newRecord);

		$arParticipants = array_unique(array_merge(
			array($newRecord["CREATED_BY"], $newRecord["RESPONSIBLE_ID"]),
			$newRecord["ACCOMPLICES"],
			$newRecord["AUDITORS"]
		));

		foreach($arParticipants as $userId)
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_".$userId);
		}

		// Emit pull event
		try
		{
			$eventGUID = sha1(uniqid('AUTOGUID', true));
			$arPullRecipients = array();

			foreach($arParticipants as $userId)
				$arPullRecipients[] = (int) $userId;

			$arPullData = array(
				'TASK_ID' => (int) $newRecord["ID"],
				'AFTER' => array(
					'GROUP_ID' => 0
				),
				'TS' => time(),
				'event_GUID' => $eventGUID
			);

			\CTasks::EmitPullWithTagPrefix(
				$arPullRecipients,
				'TASKS_GENERAL_',
				'task_add',
				$arPullData
			);

			\CTasks::EmitPullWithTag(
				$arPullRecipients,
				'TASKS_TASK_' . (int) $newRecord["ID"],
				'task_add',
				$arPullData
			);
		}
		catch (\Exception $e)
		{
		}
	}

	/**
	 * Method will be invoked after an database record updated.
	 *
	 * @param array $oldRecord All fields before update.
	 * @param array $newRecord All fields after update.
	 *
	 * @return void
	 */
	public function afterUpdateTrigger(array $oldRecord, array $newRecord)
	{
		if ($oldRecord['ZOMBIE'] !== 'Y' && $newRecord['ZOMBIE'] === 'Y')
		{
			$this->deleteAction($oldRecord);
		}
		else
		{
			$this->updateAction($oldRecord, $newRecord);
		}
	}

	protected function updateAction(array $oldRecord, array $newRecord)
	{
		global $CACHE_MANAGER;

		CounterService::getInstance()->collectData((int) $newRecord['ID']);

		$newRecord['ACCOMPLICES'] = array();
		$newRecord['AUDITORS'] = array();
		$list = \Bitrix\Tasks\Internals\Task\MemberTable::getList(array(
			"filter" => array(
				"=TASK_ID" => $newRecord["ID"],
			),
		));
		while ($item = $list->fetch())
		{
			if ($item['TYPE'] == 'A')
				$newRecord['ACCOMPLICES'][] = $item['USER_ID'];
			elseif ($item['TYPE'] == 'U')
				$newRecord['AUDITORS'][] = $item['USER_ID'];
		}

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_UPDATE,
			[
				'NEW_RECORD' => $newRecord,
				'OLD_RECORD' => $oldRecord
			]
		);

		$arParticipants = array_unique(array_merge(
			array($newRecord["CREATED_BY"], $newRecord["RESPONSIBLE_ID"]),
			$newRecord["ACCOMPLICES"],
			$newRecord["AUDITORS"]
		));

		// Emit pull event
		try
		{
			$eventGUID = sha1(uniqid('AUTOGUID', true));
			$arPullRecipients = array();

			foreach($arParticipants as $userId)
				$arPullRecipients[] = (int) $userId;

			$arPullData = array(
				'TASK_ID' => (int) $newRecord["ID"],
				'BEFORE' => array(
					'GROUP_ID' => 0
				),
				'AFTER' => array(
					'GROUP_ID' => 0
				),
				'TS' => time(),
				'event_GUID' => $eventGUID
			);

			\CTasks::EmitPullWithTagPrefix(
				$arPullRecipients,
				'TASKS_GENERAL_',
				'task_update',
				$arPullData
			);

			\CTasks::EmitPullWithTag(
				$arPullRecipients,
				'TASKS_TASK_' . (int) $newRecord["ID"],
				'task_update',
				$arPullData
			);
		}
		catch (\Exception $e)
		{
		}

		if (($status = intval($newRecord["STATUS"])) && $status > 0 && $status < 8
			&& ( (int) $oldRecord['STATUS'] !== (int) $newRecord['STATUS'] )	// only if status changed
		)
		{
			if ($status == 7)
			{
				$arTask["DECLINE_REASON"] = $newRecord["DECLINE_REASON"];
			}
			\CTaskNotifications::sendStatusMessage($oldRecord, $status, $newRecord);
		}
		\CTaskNotifications::sendUpdateMessage($newRecord, $oldRecord, true, array());

		//\CTaskComments::onAfterTaskUpdate($newRecord['ID'], $oldRecord, $newRecord);

		\CTasks::Index($newRecord, $newRecord["TAGS"]); // search index
		SearchIndex::setTaskSearchIndex($newRecord['ID'], $newRecord);

		// clear cache
		$CACHE_MANAGER->ClearByTag("tasks_".$newRecord["ID"]);

		foreach($arParticipants as $userId)
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_".$userId);
		}

	}

	/**
	 * Method will be invoked after an database record deleted.
	 *
	 * @param array $oldRecord All fields before delete.
	 *
	 * @return void
	 */
	public function deleteAction(array $oldRecord)
	{
		global $CACHE_MANAGER;

		CounterService::getInstance()->collectData((int) $oldRecord['ID']);

		$oldRecord['ACCOMPLICES'] = array();
		$oldRecord['AUDITORS'] = array();
		$list = \Bitrix\Tasks\Internals\Task\MemberTable::getList(array(
			"filter" => array(
				"=TASK_ID" => $oldRecord["ID"],
			),
		));
		while ($item = $list->fetch())
		{
			if ($item['TYPE'] == 'A')
				$oldRecord['ACCOMPLICES'][] = $item['USER_ID'];
			elseif ($item['TYPE'] == 'U')
				$oldRecord['AUDITORS'][] = $item['USER_ID'];
		}

		$arParticipants = array_unique(array_merge(
			array($oldRecord["CREATED_BY"], $oldRecord["RESPONSIBLE_ID"]),
			$oldRecord["ACCOMPLICES"],
			$oldRecord["AUDITORS"]
		));

		\CTaskNotifications::sendDeleteMessage($oldRecord);

		// Emit pull event
		try
		{
			$eventGUID = sha1(uniqid('AUTOGUID', true));
			$arPullRecipients = array();

			foreach($arParticipants as $userId)
				$arPullRecipients[] = (int) $userId;

			$arPullData = array(
				'TASK_ID' => (int) $oldRecord["ID"],
				'BEFORE' => array(
					'GROUP_ID' => 0
				),
				'TS' => time(),
				'event_GUID' => $eventGUID
			);

			\CTasks::EmitPullWithTagPrefix(
				$arPullRecipients,
				'TASKS_GENERAL_',
				'task_remove',
				$arPullData
			);

			\CTasks::EmitPullWithTag(
				$arPullRecipients,
				'TASKS_TASK_' . (int) $oldRecord["ID"],
				'task_remove',
				$arPullData
			);
		}
		catch (\Exception $e)
		{
		}

		if (\CModule::IncludeModule("search"))
		{
			\CSearch::DeleteIndex("tasks", $oldRecord["ID"]);
		}
		$this->clearSearchIndex((int)$oldRecord['ID']);

		CounterService::addEvent(
			EventDictionary::EVENT_AFTER_TASK_DELETE,
			$oldRecord
		);

		// clear cache
		$CACHE_MANAGER->ClearByTag("tasks_".$oldRecord["ID"]);

		foreach($arParticipants as $userId)
		{
			$CACHE_MANAGER->ClearByTag("tasks_user_".$userId);
		}
	}

	/**
	 * @param int $taskId
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	private function clearSearchIndex(int $taskId): void
	{
		$tableResult = SearchIndexTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=TASK_ID' => $taskId,
			],
		]);
		while ($item = $tableResult->fetch())
		{
			SearchIndexTable::delete($item);
		}
	}
}
