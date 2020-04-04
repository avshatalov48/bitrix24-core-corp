<?php
/**
 * Created by PhpStorm.
 * User: maxyc
 * Date: 17.05.18
 * Time: 10:51
 */

namespace Bitrix\Tasks\Integration\Trash;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Result;
use Bitrix\Tasks\Integration\Forum;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Trash\Internals\Contracts\Trashable;
use Bitrix\Trash\Internals\Entity;

class Task implements Trashable
{
	/**
	 * @param $taskId
	 * @param array $task
	 *
	 * @return \Bitrix\Main\Result
	 */
	public static function OnBeforeTaskDelete($taskId, array $task = [])
	{
		$trash = new Entity($taskId, Manager::TASKS_TRASH_ENTITY, Manager::MODULE_ID);
		$trash->setTitle($task['TITLE']);

		$additionalData = self::collectTaskAdditionalData($taskId);
		if ($additionalData)
		{
			foreach ($additionalData as $action => $data)
			{
				$trash->add($action, $data);
			}
		}

		$result = $trash->save();

		return $result;
	}

	private static function collectTaskAdditionalData($taskId)
	{
		$data = [];

		$res = \CTaskMembers::GetList([], ['TASK_ID' => $taskId]);
		if ($res)
		{
			while ($row = $res->Fetch())
			{
				$data['MEMBERS'][] = [
					'USER_ID' => $row['USER_ID'],
					'TYPE'    => $row['TYPE']
				];
			}
		}

		$res = \CTaskDependence::GetList([], ['TASK_ID' => $taskId]);
		if ($res)
		{
			while ($row = $res->Fetch())
			{
				$data['DEPENDENCE_TASK'][] = [
					'DEPENDS_ON_ID' => $row['DEPENDS_ON_ID']
				];
			}
		}

		$res = \CTaskDependence::GetList([], ['DEPENDS_ON_ID' => $taskId]);
		if ($res)
		{
			while ($row = $res->Fetch())
			{
				$data['DEPENDENCE_ON'][] = [
					'TASK_ID' => $row['TASK_ID']
				];
			}
		}

		//		$res = \CTaskTags::GetList([], ['TASK_ID' => $taskId]);
		//		if ($res)
		//		{
		//			while ($row = $res->Fetch())
		//			{
		//				$data['TAGS'][] = [
		//					'USER_ID' => $row['USER_ID'],
		//					'NAME'    => $row['NAME']
		//				];
		//			}
		//		}

		//		try
		//		{
		//			$list = ParameterTable::getList(
		//				[
		//					"select" => ['*'],
		//					"filter" => [
		//						"=TASK_ID" => $taskId,
		//					],
		//				]
		//			);
		//			while ($row = $list->fetch())
		//			{
		//				$data['PARAMS'][] = [
		//					'CODE'  => $row['CODE'],
		//					'VALUE' => $row['VALUE']
		//				];
		//			}
		//		}
		//		catch (\Exception $e)
		//		{
		//		}

		return $data;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public static function moveFromTrash(Entity $entity)
	{
		$result = new Result();

		$connection = Application::getConnection();

		try
		{
			$connection->queryExecute(
				'UPDATE '.TaskTable::getTableName().' SET ZOMBIE=\'N\' WHERE ID='.$entity->getEntityId()
			);
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		$dataEntity = $entity->getData();

		try
		{
			if ($dataEntity)
			{
				foreach ($dataEntity as $value)
				{
					$data = unserialize($value['DATA']);
					$action = $value['ACTION'];

					self::restoreTaskAdditionalData($entity->getEntityId(), $action, $data);
				}
			}
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		return $result;
	}

	private static function restoreTaskAdditionalData($taskId, $action, array $data = [])
	{
		$result = new Result();

		try
		{
			foreach ($data as $value)
			{
				switch ($action)
				{
					case 'MEMBERS':
						$member = new \CTaskMembers;
						$member->Add(
							[
								'TASK_ID' => $taskId,
								'USER_ID' => $value['USER_ID'],
								'TYPE'    => $value['TYPE']
							]
						);
						break;

					//					case 'TAGS':
					//						$tag = new \CTaskTags;
					//						$tag->Add(
					//							[
					//								'TASK_ID' => $taskId,
					//								'USER_ID' => $value['USER_ID'],
					//								'NAME'    => $value['NAME']
					//							]
					//						);
					//						break;

					case 'DEPENDENCE_TASK':
						$tag = new \CTaskDependence;
						$tag->Add(
							[
								'TASK_ID'       => $taskId,
								'USER_ID'       => $value['USER_ID'],
								'DEPENDS_ON_ID' => $value['DEPENDS_ON_ID']
							]
						);
						break;

					//					case 'PARAMS':
					//						ParameterTable::add(
					//							[
					//								'TASK_ID' => $taskId,
					//								'CODE'    => $value['CODE'],
					//								'VALUE'   => $value['VALUE']
					//							]
					//						);
					//						break;
				}
			}
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		return $result;

	}

	/**
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public static function removeFromTrash(Entity $entity)
	{
		$result = new Result;

		try
		{
			$res = Application::getConnection()->query(
				'SELECT FORUM_TOPIC_ID FROM b_tasks WHERE ID = '.$entity->getEntityId()
			);
			$task = $res->fetch();

			Forum\Task\Topic::delete($task["FORUM_TOPIC_ID"]);

			$connection = Application::getConnection();

			$connection->queryExecute('DELETE FROM b_tasks WHERE ID='.$entity->getId());
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage(), $e->getCode()));
		}

		return $result;
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool|void
	 * @throws NotImplementedException
	 */
	public static function previewFromTrash(Entity $entity)
	{
		throw new NotImplementedException("Coming soon...");
	}
}