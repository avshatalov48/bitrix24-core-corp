<?php

namespace Bitrix\Tasks\Control;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlHelper;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Access\TagAccessController;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\EO_TaskTag;
use Bitrix\Tasks\Internals\Task\EO_TaskTag_Collection;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\TaskTagTable;
use Exception;

class Tag
{
	public const USER_TAGS_CACHE = 'user';
	public const TASK_TAGS_CACHE = 'task';
	public const GROUP_TAGS_CACHE = 'group';

	private $userId;

	private static array $storage = [];

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
	}

	/**
	 * @throws SystemException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 */
	public function set(int $taskId, array $tags, int $oldGroupId = 0, int $newGroupId = 0, bool $onlyAdd = false): void
	{
		if (empty($taskId))
		{
			return;
		}

		if ($oldGroupId + $newGroupId !== 0)
		{
			if (empty($newGroupId))
			{
				$ownerId = (int)TaskRegistry::getInstance()->get($taskId)['CREATED_BY'];
				$this->moveToUser($ownerId, $taskId, $oldGroupId);
			}
			else
			{
				$this->moveToGroup($taskId, $newGroupId);
			}

			$currentTags = $this->getTaskTags($taskId);
			if (
				empty(array_diff($currentTags, $tags))
				&& empty(array_diff($tags, $currentTags))
			)
			{
				self::invalidate();
				return;
			}
		}

		$add = $this->getTagsForAdd($taskId, $tags);
		$delete = $onlyAdd ? [] : $this->getTagsToDelete($taskId, $tags);

		$groupId = $newGroupId > 0 ? $newGroupId : $this->getGroupId($taskId);

		if (!$groupId)
		{
			$this->addToTask($taskId, $add);
			$this->deleteFromTask($taskId, $delete);
		}
		else
		{
			$this->addToGroupTask($taskId, $groupId, $add);
			$this->deleteFromGroupTask($taskId, $groupId, $delete);
		}

		self::invalidate();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getTaskTags(int $taskId): array
	{
		$this->cacheCurrentTags($taskId);
		return $this->getNames(self::$storage[self::TASK_TAGS_CACHE]);
	}

	public function delete(array $tags): void
	{
		if (empty($tags))
		{
			return;
		}

		LabelTable::deleteByFilter(['@ID' => $tags]);
		self::invalidate();
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function deleteFromTask(int $taskId, array $tagsForDelete): void
	{
		if (empty($tagsForDelete))
		{
			return;
		}

		$forDelete = [];
		foreach ($tagsForDelete as $tag)
		{
			$forDelete[] = trim($tag['NAME']);
		}

		$this->cacheCurrentTags($taskId);
		$tags = self::$storage[self::TASK_TAGS_CACHE];

		$idList = [];
		foreach ($tags as $tag)
		{
			if (in_array($tag['NAME'], $forDelete, true))
			{
				$idList[] = $tag['ID'];
				TagAccessController::invalidate($tag['ID']);
			}
		}

		TaskTagTable::deleteList([
			'=TASK_ID' => $taskId,
			'@TAG_ID' => $idList,
		]);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function deleteFromGroupTask(int $taskId, int $groupId, array $tagsForDelete): void
	{
		if (empty($tagsForDelete))
		{
			return;
		}

		$forDelete = [];
		foreach ($tagsForDelete as $tag)
		{
			$forDelete[] = $tag['NAME'];
		}

		$this->cacheGroupTags($groupId);

		$tags = self::$storage[self::GROUP_TAGS_CACHE];

		$idList = [];
		foreach ($tags as $tag)
		{
			if (in_array($tag['NAME'], $forDelete, true))
			{
				$idList[] = $tag['ID'];
				TagAccessController::invalidate($tag['ID']);
			}
		}

		TaskTagTable::deleteList([
			'=TASK_ID' => $taskId,
			'TAG_ID' => $idList,
		]);
	}

	/**
	 * @throws Exception
	 */
	public function edit(int $tagId, string $newName): void
	{
		$newName = trim($newName);
		if (empty($newName))
		{
			return;
		}

		LabelTable::update($tagId, [
			'NAME' => $newName,
		]);

		self::invalidate($tagId);
	}

	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function unlinkTags(int $taskId): void
	{
		TaskTagTable::deleteList([
			'=TASK_ID' => $taskId,
		]);

		self::invalidate();
	}

	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function unlinkTag(int $taskId, int $tagId): void
	{
		TaskTagTable::deleteList([
			'=TASK_ID' => $taskId,
			'=TAG_ID' => $tagId,
		]);

		self::invalidate();
	}

	/**
	 * @throws Exception
	 */
	public function linkTag(int $taskId, int $tagId): void
	{
		TaskTagTable::add([
			'TASK_ID' => $taskId,
			'TAG_ID' => $tagId,
		]);

		self::invalidate();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function linkTags(int $taskId, array $tagIds): void
	{
		if (empty($tagIds))
		{
			return;
		}

		$existingTags = LabelTable::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'@ID' => $tagIds,
			],
		])->fetchAll();

		$existingTagIds = array_map(static function (array $el): int {
			return (int)$el['ID'];
		}, $existingTags);

		$collection = new EO_TaskTag_Collection();

		foreach ($existingTagIds as $tagId)
		{
			$item = new EO_TaskTag();
			$item->setTagId($tagId);
			$item->setTaskId($taskId);
			$collection->add($item);
		}

		$collection->save(true);

		self::invalidate();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isExistsByGroup(int $groupId, string $name): bool
	{
		$name = $this->prepareTag($name);
		if (empty($name))
		{
			return true;
		}
		//no group - no problem
		if ($groupId === 0)
		{
			return false;
		}

		$this->cacheGroupTags($groupId);
		$groupTags = $this->getNames(self::$storage[self::GROUP_TAGS_CACHE]);
		$groupTags = $this->prepareTags($groupTags);

		return in_array($name, $groupTags, true);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function isExists(string $name, int $groupId, int $taskId): bool
	{
		if ($taskId > 0 && $this->isExistsByTask($taskId, $name))
		{
			return true;
		}
		if ($groupId > 0)
		{
			return $this->isExistsByGroup($groupId, $name);
		}

		return $this->isExistsByUser($name);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isExistsByUser(string $name): bool
	{
		$name = $this->prepareTag($name);
		if (empty($name))
		{
			return true;
		}
		$this->cacheUserTags();
		$userTags = $this->getNames(self::$storage[self::USER_TAGS_CACHE]);
		$userTags = $this->prepareTags($userTags);

		return in_array($name, $userTags, true);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function isExistsByTask(int $taskId, string $name): bool
	{
		$name = $this->prepareTag($name);
		if (empty($name))
		{
			return true;
		}
		$this->cacheCurrentTags($taskId);
		$currentTags = $this->getNames(self::$storage[self::TASK_TAGS_CACHE]);
		$currentTags = $this->prepareTags($currentTags);

		return in_array($name, $currentTags, true);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getIdByUser(array $tag, int $userId = 0): int
	{
		$tagName = $this->prepareTag($tag['NAME']);
		if (empty($userId))
		{
			$userId = $this->userId;
		}
		$this->cacheUserTags($userId);

		$currentTags = self::$storage[self::USER_TAGS_CACHE];

		foreach ($currentTags as $currentTag)
		{
			if ($this->prepareTag($currentTag['NAME']) === $tagName)
			{
				return (int)$currentTag['ID'];
			}
		}

		return 0;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getIdByGroup(int $groupId, $tag): int
	{
		$this->cacheGroupTags($groupId);

		$groupTags = self::$storage[self::GROUP_TAGS_CACHE];

		foreach ($groupTags as $groupTag)
		{
			$name = is_array($tag) ? $tag['NAME'] : $tag;
			$name = $this->prepareTag($name);
			if ($this->prepareTag($groupTag['NAME']) === $name)
			{
				return (int)$groupTag['ID'];
			}
		}

		return 0;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getIdByTask(int $taskId, $tag): int
	{
		$this->cacheCurrentTags($taskId);

		$taskTags = self::$storage[self::TASK_TAGS_CACHE];

		foreach ($taskTags as $taskTag)
		{
			$name = is_array($tag) ? $tag['NAME'] : $tag;
			if ($taskTag['NAME'] === $name)
			{
				return (int)$taskTag['ID'];
			}
		}

		return 0;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getTagsForAdd(int $taskId, array $newTags): array
	{
		$this->cacheCurrentTags($taskId);
		$currentTags = $this->getNames(self::$storage[self::TASK_TAGS_CACHE]);

		$addToTask = array_diff($newTags, $currentTags);

		$add = [];
		foreach ($addToTask as $tag)
		{
			if (is_string($tag))
			{
				$add[] = [
					'NAME' => $tag,
					'USER_ID' => $this->userId,
				];
			}
		}

		return $add;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getTagsToDelete(int $taskId, array $newTags): array
	{
		$this->cacheCurrentTags($taskId);
		$currentTags = $this->getNames(self::$storage[self::TASK_TAGS_CACHE]);

		$delete = [];

		foreach (array_diff($currentTags, $newTags) as $tag)
		{
			$delete[] = [
				'NAME' => $tag,
				'USER_ID' => $this->userId,
			];
		}

		return $delete;
	}

	private function getNames(array $tags): array
	{
		$formattedTags = [];
		foreach ($tags as $tag)
		{
			$formattedTags[] = is_array($tag) ? $tag['NAME'] : $tag;
		}

		return $formattedTags;
	}

	public function getGroupId(int $taskId): int
	{
		$task = TaskRegistry::getInstance()->get($taskId);
		if (isset($task))
		{
			return $task['GROUP_ID'];
		}

		return 0;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function addToGroup(int $groupId, array $tags): array
	{
		if (empty($tags))
		{
			return [];
		}

		$implode = [];
		$names = [];

		foreach ($tags as $tag)
		{
			$names[] = trim($tag['NAME']);
			$name = Application::getConnection()->getSqlHelper()->forSql(trim($tag['NAME']));
			$implode [] = "('{$name}', {$groupId})";
		}

		$implode = implode(',', $implode);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			LabelTable::getTableName(),
			' (NAME, GROUP_ID)',
			" VALUES {$implode}"
		);
		Application::getConnection()->query($sql);

		$idRows = LabelTable::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=GROUP_ID' => $groupId,
				'=NAME' => $names,
			],
		])->fetchAll();

		$idList = [];
		foreach ($idRows as $row)
		{
			$idList[] = (int)$row['ID'];
		}

		return $idList;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function addToUser(int $userId, array $tags): array
	{
		if (empty($tags))
		{
			return [];
		}

		$implode = [];
		$names = [];

		foreach ($tags as $tag)
		{
			$names[] = trim($tag['NAME']);
			$name = Application::getConnection()->getSqlHelper()->forSql(trim($tag['NAME']));
			$implode [] = "('{$name}', {$userId})";
		}

		$implode = implode(',', $implode);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			LabelTable::getTableName(),
			' (NAME, USER_ID)',
			" VALUES {$implode}"
		);
		Application::getConnection()->query($sql);

		$idRows = LabelTable::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=USER_ID' => $userId,
				'=NAME' => $names,
			],
		])->fetchAll();

		$idList = [];
		foreach ($idRows as $row)
		{
			$idList[] = $row['ID'];
		}

		return $idList;
	}

	/**
	 * @throws Exception
	 */
	public function addTagToUser(string $tagName): void
	{
		$tagName = trim($tagName);
		if (empty($tagName))
		{
			return;
		}

		LabelTable::add([
			'NAME' => $tagName,
			'USER_ID' => $this->userId,
			'GROUP_ID' => 0,
		]);

		self::invalidate();
	}

	/**
	 * @throws Exception
	 */
	public function addTag(string $tagName, int $groupId): void
	{
		if ($groupId > 0)
		{
			$this->addTagToGroup($tagName, $groupId);
		}
		else
		{
			$this->addTagToUser($tagName);
		}
	}

	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function deleteGroupTags(int $groupId): void
	{
		LabelTable::deleteList([
			'=USER_ID' => 0,
			'=GROUP_ID' => $groupId,
		]);

		self::invalidate();
	}

	/**
	 * @throws Exception
	 */
	public function addTagToGroup(string $tagName, int $groupId): void
	{
		$tagName = trim($tagName);
		if (empty($tagName))
		{
			return;
		}

		LabelTable::add([
			'NAME' => $tagName,
			'USER_ID' => 0,
			'GROUP_ID' => $groupId,
		]);

		self::invalidate();
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function addToGroupTask(int $taskId, int $groupId, array $tagsForAdd): void
	{
		if (empty($tagsForAdd))
		{
			return;
		}

		$idList = [];
		$addToGroup = [];

		foreach ($tagsForAdd as $tag)
		{
			$tagId = $this->getIdByGroup($groupId, $tag);
			$isExistsInCurrentPull = $this->isExistsInCurrentPull($tag['NAME'], $addToGroup);
			if ($tagId === 0)
			{
				if ($isExistsInCurrentPull)
				{
					continue;
				}
				$addToGroup[] = [
					'GROUP_ID' => $groupId,
					'NAME' => $tag['NAME'],
				];
				continue;
			}

			if (!$isExistsInCurrentPull)
			{
				$idList[] = $tagId;
			}
		}

		$idList = array_merge($this->addToGroup($groupId, $addToGroup), $idList);

		if (empty($idList))
		{
			return;
		}
		$implode = [];
		foreach ($idList as $id)
		{
			$implode [] = "({$taskId}, {$id})";
		}
		$implode = implode(',', $implode);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			LabelTable::getRelationTable(),
			' (TASK_ID, TAG_ID)',
			" VALUES {$implode}"
		);
		Application::getConnection()->query($sql);
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function addToTask(int $taskId, array $tagsForAdd, int $userId = 0): void
	{
		if (empty($userId))
		{
			$userId = $this->userId;
		}
		if (empty($tagsForAdd))
		{
			return;
		}

		$idList = [];
		$addToUser = [];

		foreach ($tagsForAdd as $tag)
		{
			$tagId = $this->getIdByUser($tag, $userId);
			$existsInCurrentPull = $this->isExistsInCurrentPull($tag['NAME'], $addToUser);
			if ($tagId === 0)
			{
				if ($existsInCurrentPull)
				{
					continue;
				}
				$addToUser[] = [
					'USER_ID' => $tag['USER_ID'],
					'NAME' => $tag['NAME'],
				];
				continue;
			}

			if (!$existsInCurrentPull)
			{
				$idList[] = $tagId;
			}
		}

		$idList = array_merge($this->addToUser($userId, $addToUser), $idList);

		if (empty($idList))
		{
			return;
		}
		$implode = [];
		foreach ($idList as $id)
		{
			$id = (int)$id;
			$implode [] = "({$taskId}, {$id})";
		}
		$implode = implode(',', $implode);

		$sql = $this->getSqlHelper()->getInsertIgnore(
			LabelTable::getRelationTable(),
			' (TASK_ID, TAG_ID)',
			" VALUES {$implode}"
		);
		Application::getConnection()->query($sql);
	}

	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function moveToGroup(int $taskId, int $groupId): void
	{
		$this->cacheCurrentTags($taskId);

		$taskTags = self::$storage[self::TASK_TAGS_CACHE];
		if (empty($taskTags) || empty($taskId))
		{
			return;
		}

		$this->addToGroupTask($taskId, $groupId, $taskTags);
		$ids = array_map(function ($el) {
			return $el['ID'];
		}, $taskTags);

		if (empty($ids))
		{
			return;
		}
		TaskTagTable::deleteList([
			'=TASK_ID' => $taskId,
			'@TAG_ID' => $ids,
		]);

		self::invalidate();
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function moveToUser(int $userId, int $taskId, int $groupId): void
	{
		if (empty($userId) || empty($taskId) || empty($groupId))
		{
			return;
		}
		$tagsFromGroup = LabelTable::getList([
			'select' => [
				'NAME',
				'ID',
				'TASK_' => 'TASKS',
			],
			'filter' => [
				'=GROUP_ID' => $groupId,
				'=TASK_ID' => $taskId,
			],
		])->fetchAll();

		if (empty($tagsFromGroup))
		{
			return;
		}

		$this->addToTask($taskId, $tagsFromGroup, $userId);
		$ids = array_map(function ($el) {
			return $el['ID'];
		}, $tagsFromGroup);

		if (empty($ids))
		{
			return;
		}
		TaskTagTable::deleteList([
			'=TASK_ID' => $taskId,
			'@TAG_ID' => $ids,
		]);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function cacheUserTags(int $userId = 0): void
	{
		if (empty($userId))
		{
			$userId = $this->userId;
		}
		if (array_key_exists(self::USER_TAGS_CACHE, self::$storage))
		{
			return;
		}
		self::$storage[self::USER_TAGS_CACHE] = LabelTable::getList([
			'select' => [
				'ID',
				'NAME',
				'USER_ID',
			],
			'filter' => [
				'=USER_ID' => $userId,
			],
		])->fetchAll();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function cacheGroupTags(int $groupId): void
	{
		if (array_key_exists(self::GROUP_TAGS_CACHE, self::$storage))
		{
			return;
		}

		if ($groupId === 0)
		{
			self::$storage[self::GROUP_TAGS_CACHE] = [];
		}

		self::$storage[self::GROUP_TAGS_CACHE] = LabelTable::getList([
			'select' => [
				'NAME',
				'ID',
			],
			'filter' => [
				'GROUP_ID' => $groupId,
			],
		])->fetchAll();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function cacheCurrentTags(int $taskId): void
	{
		if (array_key_exists(self::TASK_TAGS_CACHE, self::$storage))
		{
			return;
		}
		self::$storage[self::TASK_TAGS_CACHE] = LabelTable::getList([
			'select' => [
				'ID',
				'NAME',
				'USER_ID',
				'TASK_' => 'TASKS',
			],
			'filter' => [
				'=TASK_ID' => $taskId,
			],
		])->fetchAll();
	}

	private static function invalidate(?int $tagId = null): void
	{
		self::$storage = [];
		TagAccessController::invalidate($tagId);
	}

	private function prepareTag(string $name): string
	{
		return mb_strtolower(trim($name));
	}

	private function prepareTags(array $tags): array
	{
		return array_map(fn (string $name): string => $this->prepareTag($name)
			, $tags
		);
	}

	private function isExistsInCurrentPull(string $name, array $tags): bool
	{
		$name = $this->prepareTag($name);
		$tagNames = array_map(fn (array $tag): string => $this->prepareTag($tag['NAME']), $tags);
		if (in_array($name, $tagNames, true))
		{
			return true;
		}

		return false;
	}

	private function getSqlHelper(): SqlHelper
	{
		return Application::getConnection()->getSqlHelper();
	}
}
