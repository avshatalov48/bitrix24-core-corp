<?php
namespace Bitrix\Tasks\Integration\Recyclebin;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Recyclebin\Internals\Entity;
use Bitrix\Recyclebin\Internals\Contracts\Recyclebinable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\Control\Tag;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\ScenarioTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\SortingTable;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Kanban\TaskStageTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;

use \CTasks;

if (Loader::includeModule('recyclebin'))
{
	/**
	 * Class Task
	 * @package Bitrix\Tasks\Integration\Recyclebin
	 */
	class Task implements Recyclebinable
	{
		/**
		 * @param $taskId
		 * @param array $task
		 * @return mixed
		 * @throws \Bitrix\Main\ArgumentException
		 * @throws \Bitrix\Main\ObjectPropertyException
		 * @throws \Bitrix\Main\SystemException
		 */
		public static function OnBeforeTaskDelete($taskId, array $task = [])
		{
			$recyclebin = new Entity($taskId, Manager::TASKS_RECYCLEBIN_ENTITY, Manager::MODULE_ID);
			$recyclebin->setTitle($task['TITLE']);

			$additionalData = self::collectAdditionalData($taskId);
			if ($additionalData)
			{
				foreach ($additionalData as $action => $data)
				{
					$recyclebin->add($action, $data);
				}
			}

			$result = $recyclebin->save();
			$resultData = $result->getData();

			return $resultData['ID'];
		}

		/**
		 * @param $taskId
		 * @return array
		 * @throws \Bitrix\Main\ArgumentException
		 * @throws \Bitrix\Main\ObjectPropertyException
		 * @throws \Bitrix\Main\SystemException
		 */
		private static function collectAdditionalData($taskId)
		{
			$data = [];

			$task = TaskTable::getByPrimary(
				$taskId,
				[
					'select' => ['*', 'UF_*']
				]
			);

			$tags = TaskTagTable::getList([
				'select' => [
					'TAG_ID',
				],
				'filter' => [
					'TASK_ID' => $taskId,
				],
			])->fetchAll();

			$tagIds = array_map(static function (array $el): int {
				return (int)$el['TAG_ID'];
			}, $tags);

			$res = $task->fetchObject();

			if ($res)
			{
				$data['TASK'] = $res->toArray();
				$data['TAGS'] = $tagIds;
			}

			// Scenario
			$scenarios = ScenarioTable::getList([
				'select' => ['SCENARIO'],
				'filter' => [
					'=TASK_ID' => $taskId,
				],
			])->fetchAll();
			foreach ($scenarios as $row)
			{
				$data['SCENARIO'][] = $row['SCENARIO'];
			}

			$res = TaskStageTable::getList(['filter' => ['TASK_ID' => $taskId], 'select' => ['STAGE_ID']]);
			while ($row = $res->fetch())
			{
				$data['STAGES'][] = $row;
			}

			$res = \CTaskMembers::GetList([], ['TASK_ID' => $taskId, 'TYPE' => ['O', 'R', 'A', 'U']]);
			while ($row = $res->Fetch())
			{
				$data['MEMBERS'][] = [
					'USER_ID' => $row['USER_ID'],
					'TYPE' => $row['TYPE']
				];
			}

			$res = \CTaskDependence::GetList([], ['TASK_ID' => $taskId]);
			while ($row = $res->Fetch())
			{
				$data['DEPENDENCE_TASK'][] = [
					'DEPENDS_ON_ID' => $row['DEPENDS_ON_ID']
				];
			}

			$res = \CTaskDependence::GetList([], ['DEPENDS_ON_ID' => $taskId]);
			while ($row = $res->Fetch())
			{
				$data['DEPENDENCE_ON'][] = [
					'TASK_ID' => $row['TASK_ID']
				];
			}

			$res = \CTaskReminders::GetList([], ['TASK_ID' => $taskId]);
			while ($row = $res->Fetch())
			{
				$data['REMINDERS'][] = $row;
			}

			$res = \CTaskTemplates::GetList([], ['TASK_ID' => $taskId], [], [], ['ID']);
			while ($row = $res->Fetch())
			{
				$data['TEMPLATES'][] = $row['ID'];
			}

			$tree = Dependence::getSubTree($taskId);
			$subtasks = $tree->find(['__PARENT_ID' => $taskId])->getData();

			$subtaskIds = [];
			foreach ($subtasks as $relations)
			{
				$id = (int)$relations['__ID'];
				if ($id > 0)
				{
					$subtaskIds[] = $id;
				}
			}

			$data['SUBTASK_IDS'] = array_unique($subtaskIds);

			if (\CModule::IncludeModule('crm'))
			{
				$needActivityFields = [
					'OWNER_ID',
					'OWNER_TYPE_ID',
					'TYPE_ID',
					'PROVIDER_ID',
					'PROVIDER_TYPE_ID',
					'PROVIDER_GROUP_ID',
					'CALENDAR_EVENT_ID',
					'PARENT_ID',
					'THREAD_ID',
					'ASSOCIATED_ENTITY_ID',
					'SUBJECT',
					'CREATED',
					'LAST_UPDATED',
					'START_TIME',
					'END_TIME',
					'DEADLINE',
					'COMPLETED',
					'STATUS',
					'RESPONSIBLE_ID',
					'PRIORITY',
					'NOTIFY_TYPE',
					'NOTIFY_VALUE',
					'DESCRIPTION',
					'DESCRIPTION_TYPE',
					'ORIGINATOR_ID'
				];

				$res = \CCrmActivity::GetList([], [
					'TYPE_ID' => \CCrmActivityType::Task,
					'ASSOCIATED_ENTITY_ID' => $taskId
				]);

				while ($a = $res->Fetch())
				{
					$activity = [];
					foreach ($needActivityFields as $fieldCode)
					{
						$activity[$fieldCode] = $a[$fieldCode];
					}

					$data['ACTIVITIES'][] = $activity;
				}
			}
			return $data;
		}

		/**
		 * @param Entity $entity
		 * @return Result|bool
		 */
		public static function moveFromRecyclebin(Entity $entity)
		{
			$result = new Result();

			$taskId = $entity->getEntityId();
			$taskData = $entity->getData();

			try
			{
				$cache = Cache::createInstance();
				$cache->clean(CTasks::CACHE_TASKS_COUNT, CTasks::CACHE_TASKS_COUNT_DIR_NAME);
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			try
			{
				if (!$taskData)
				{
					return false;
				}

				// we should to restore task first
				$taskRestored = false;
				foreach ($taskData as $key => $value)
				{
					if ($value['ACTION'] !== 'TASK')
					{
						continue;
					}

					$restore = self::restoreAdditionalData($taskId, $value);

					if (!$restore->isSuccess())
					{
						return false;
					}
					unset($taskData[$key]);
					$taskRestored = true;
				}

				if (!$taskRestored)
				{
					return false;
				}

				foreach ($taskData as $value)
				{
					$restore = self::restoreAdditionalData($taskId, $value);
					if (!$restore->isSuccess())
					{
						$result->addErrors($restore->getErrors());
					}
				}

				$task = \CTaskItem::getInstance($taskId, 1);
				$task->update([], [
					'FORCE_RECOUNT_COUNTER' => 'Y',
					'PIN_IN_STAGE' => false,
				]);

				$logFields = [
					"TASK_ID" => $taskId,
					"USER_ID" => User::getId(),
					"CREATED_DATE" => new DateTime(),
					"FIELD" => 'RENEW'
				];

				$log = new \CTaskLog();
				$log->Add($logFields);

				Counter\CounterService::addEvent(
					Counter\Event\EventDictionary::EVENT_AFTER_TASK_RESTORE,
					$task->getData(false)
				);

				Integration\SocialNetwork\Log::showLogByTaskId($taskId);
				ItemTable::activateBySourceId($taskId);
				(new TimeLineManager($taskId, User::getId()))->onTaskCreated(true)->save();

			}
			catch (\Exception $e)
			{
				AddMessage2Log('Tasks RecycleBin: '.$e->getMessage().'. TaskId: '.$taskId.'. Data: '.var_export($taskData, true), 'tasks');
				return false;
			}

			return $result;
		}

		/**
		 * Restores entity from recycle bin
		 *
		 * @param $taskId
		 * @param $action
		 * @param array $data
		 * @return Result
		 */
		private static function restoreAdditionalData($taskId, $value)
		{
			$data = unserialize($value['DATA'], ['allowed_classes' => ['Bitrix\Tasks\Util\Type\DateTime', 'Bitrix\Main\Type\DateTime', 'DateTime']]);
			$action = $value['ACTION'];

			$result = new Result();

			try
			{
				$map = [
					'MEMBERS' => [
						'VALUE' => 'TASK_ID',
						'CLASS' => \CTaskMembers::class
					],
					'DEPENDENCE_TASK' => [
						'VALUE' => 'TASK_ID',
						'CLASS' => \CTaskDependence::class
					],
					'DEPENDENCE_ON' => [
						'VALUE' => 'DEPENDS_ON_ID',
						'CLASS' => \CTaskDependence::class
					],
					'REMINDERS' => [
						'VALUE' => '',
						'CLASS' => \CTaskReminders::class
					]
				];

				switch ($action)
				{
					case 'TASK':
						TaskTable::insert($data);
						break;
					case 'STAGES':
						foreach ($data as $value)
						{
							if (StagesTable::getById($value['STAGE_ID'])->fetch())
							{
								StagesTable::pinInTheStage($taskId, $value['STAGE_ID']);
							}
							else
							{
								StagesTable::pinInStage($taskId);
							}
						}
						break;

					case 'MEMBERS':
					case 'REMINDERS':
					case 'DEPENDENCE_ON':
					case 'DEPENDENCE_TASK':
						foreach ($data as $value)
						{
							$currentMap = $map[$action];

							if ($currentMap['VALUE'])
							{
								$value[$currentMap['VALUE']] = $taskId;
							}

							$class = new $currentMap['CLASS'];
							$class->Add($value);
						}
						break;

					case 'ACTIVITIES':
						if (\CModule::IncludeModule('crm'))
						{
							foreach ($data as $value)
							{
								\CCrmActivity::Add($value);
							}
						}
						break;

					case 'TEMPLATES':
						$connection = Application::getConnection();

						foreach ($data as $templateId)
						{
							$connection->queryExecute('UPDATE b_tasks_template SET TASK_ID = ' . $taskId . ' WHERE ID = ' . $templateId);
						}

						break;

					case 'PARENT_DEPENDENCIES':
						$parentId = $data['PARENT_ID'];
						$connection = Application::getConnection();

						foreach ($data['SUBTASKS'] as $subTaskId)
						{
							$filter = [
								'ID' => $subTaskId,
								'PARENT_ID' => $parentId
							];

							if (\CTasks::GetList([], $filter, ['ID'])->Fetch())
							{
								$connection->queryExecute('UPDATE b_tasks SET PARENT_ID = ' . $taskId . ' WHERE ID = ' . $subTaskId);
								Dependence::attach($subTaskId, $taskId);
							}
						}

						if ($parentId && \CTasks::GetList([], ['ID' => $parentId], ['ID'])->Fetch())
						{
							Dependence::attach($taskId, $parentId);
						}
						break;

					case 'TAGS':
						$tagService = new Tag(CurrentUser::get()->getId());
						$tagService->linkTags($taskId, $data);
						break;

					case 'SCENARIO':
						ScenarioTable::insertIgnore($taskId, $data);
						break;

					case 'SUBTASK_IDS':
						$subtaskIds = array_map('intval', $data);

						if (empty($subtaskIds))
						{
							break;
						}

						$registry = TaskRegistry::getInstance();
						$registry->load($subtaskIds);

						foreach ($subtaskIds as $subtaskId)
						{
							$task = $registry->get($subtaskId);
							if (is_null($task))
							{
								continue;
							}

							$result = Dependence::attach($subtaskId, $taskId);
							if (!$result->isSuccess())
							{
								continue;
							}

							TaskTable::update($subtaskId, [
								'PARENT_ID' => $taskId,
							]);
						}
						break;
				}
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			return $result;
		}

		/**
		 * Removes entity from recycle bin
		 *
		 * @param Entity $entity
		 * @param array $params
		 *
		 * @return Result
		 */
		public static function removeFromRecyclebin(Entity $entity, array $params = [])
		{
			global $USER_FIELD_MANAGER;

			$result = new Result;

			$taskData = null;
			foreach ($entity->getData() as $data)
			{
				if (
					is_array($data)
					&& array_key_exists('ACTION', $data)
					&& $data['ACTION'] === 'TASK'
				)
				{
					$taskData = unserialize($data['DATA'], ['allowed_classes' => ['Bitrix\Tasks\Util\Type\DateTime', 'Bitrix\Main\Type\DateTime', 'DateTime']]);
				}
			}

			try
			{
				$taskId = $entity->getEntityId();
				$tablesToClear = [
					ViewedTable::class => ['TASK_ID', 'USER_ID'],
					ParameterTable::class => ['ID'],
					SearchIndexTable::class => ['ID']
				];

				\CTaskFiles::DeleteByTaskID($taskId);
				\CTaskTags::DeleteByTaskID($taskId);
				\CTaskReminders::DeleteByTaskID($taskId);
				FavoriteTable::deleteByTaskId($taskId, ['LOW_LEVEL' => true]);
				SortingTable::deleteByTaskId($taskId);
				UserOption::deleteByTaskId($taskId);
				TaskStageTable::clearTask($taskId);
				TaskCheckListFacade::deleteByEntityIdOnLowLevel($taskId);

				foreach ($tablesToClear as $table => $select)
				{
					/** @var \Bitrix\Main\ORM\Query\Result $tableResult */
					$tableResult = $table::getList([
						"select" => $select,
						"filter" => [
							"=TASK_ID" => $taskId,
						],
					]);

					while ($item = $tableResult->fetch())
					{
						$table::delete($item);
					}
				}

				if ($taskData)
				{
					Integration\Forum\Task\Topic::delete($taskData["FORUM_TOPIC_ID"]);
				}

				Integration\IM\Internals\LinkTask::delete($taskId);

				$USER_FIELD_MANAGER->Delete('TASKS_TASK', $taskId);

				ItemTable::deleteBySourceId($taskId);

				TaskTable::delete($taskId);
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode()));
			}

			Integration\SocialNetwork\Log::deleteLogByTaskId($taskId);

			return $result;
		}

		/**
		 * @param Entity $entity
		 * @return bool|void
		 * @throws NotImplementedException
		 */
		public static function previewFromRecyclebin(Entity $entity)
		{
			throw new NotImplementedException("Coming soon...");
		}

		/**
		 * @return array
		 */
		public static function getNotifyMessages()
		{
			return [
				'NOTIFY'=> [
					'RESTORE' => Loc::getMessage('TASKS_RECYCLEBIN_RESTORE_MESSAGE'),
					'REMOVE' => Loc::getMessage('TASKS_RECYCLEBIN_REMOVE_MESSAGE'),
				],
				'CONFIRM' => [
					'RESTORE' => Loc::getMessage('TASKS_RECYCLEBIN_RESTORE_CONFIRM'),
					'REMOVE' => Loc::getMessage('TASKS_RECYCLEBIN_REMOVE_CONFIRM')
				]
			];
		}

		/**
		 * @return array
		 * @throws \Bitrix\Main\ObjectPropertyException
		 * @throws \Bitrix\Main\SystemException
		 */
		public static function getAdditionalData(): array
		{
			return [
				'LIMIT_DATA' => [
					'RESTORE' => [
						'DISABLE' => TaskLimit::isLimitExceeded() || !\Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(\Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASKS_RECYCLEBIN),
						'SLIDER_CODE' => 'limit_tasks_recycle_bin_restore',
					],
				],
			];
		}

		/**
		 * Checks if tasks are in the recycle bin.
		 *
		 * @param array $taskIds
		 * @return array The map task ids with the result.
		 */
		public static function isInTheRecycleBin(array $taskIds): array
		{
			$resultMap = [];

			foreach ($taskIds as $taskId)
			{
				$resultMap[$taskId] = false;
			}

			$queryObject = RecyclebinTable::getList([
				'select' => ['ENTITY_ID'],
				'filter' => [
					'=MODULE_ID' => 'tasks',
					'=ENTITY_TYPE' => Manager::TASKS_RECYCLEBIN_ENTITY,
					'=ENTITY_ID' => $taskIds
				],
			]);
			while ($data = $queryObject->fetch())
			{
				$resultMap[$data['ENTITY_ID']] = true;
			}

			return $resultMap;
		}
	}
}