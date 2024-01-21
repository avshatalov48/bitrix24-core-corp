<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Task\Search\Builder\IndexBuilder;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Item\Task\Collection\CheckList;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Update\FullTasksIndexer;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\User;
use CAgent;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;
use CSearch;
use CTaskItem;
use Exception;

/**
 * Class SearchIndex
 *
 * @package Bitrix\Tasks\Internals
 */
class SearchIndex
{
	private static array $fields = [
		'ID',
		'TITLE',
		'DESCRIPTION',
		'TAGS',
		'CREATED_BY',
		'RESPONSIBLE_ID',
		'AUDITORS',
		'ACCOMPLICES',
		'GROUP_ID',
		'SE_CHECKLIST',
		'UF_CRM_TASK',
	];

	public static function setTaskSearchIndex(int $taskId): void
	{
		try
		{
			$searchIndex = (new IndexBuilder($taskId))->build();
			SearchIndexTable::set($taskId, 0, $searchIndex);
		}
		catch (SearchIndexException $exception)
		{
			(new Log())->collect("Search index error: {$exception->getMessage()}");
		}
	}

	public static function setCommentSearchIndex(int $taskId, int $commentId, string $commentText): void
	{
		$searchIndex = htmlspecialcharsback($commentText);
		$searchIndex = static::prepareSearchIndex($searchIndex);

		SearchIndexTable::set($taskId, $commentId, $searchIndex);
	}

	public static function getTaskSearchIndex(int $taskId): string
	{
		$query = SearchIndexTable::query();
		$query
			->setSelect(['ID', 'SEARCH_INDEX'])
			->where('TASK_ID', $taskId)
			->where('MESSAGE_ID', 0)
		;

		return (string)$query->exec()->fetchObject()?->getSearchIndex();
	}

	/**
	 * @deprecated
	 * @use IndexBuilder
	 */
	public static function buildTaskSearchIndex($task, array $fields = []): string
	{
		$searchIndex = '';

		if (empty($fields))
		{
			$fields = static::$fields;
		}
		else
		{
			$fields = array_intersect($fields, static::$fields);
			$fields = array_unique($fields);

			if (empty($fields))
			{
				return $searchIndex;
			}
		}

		$taskData = static::getTaskData($task, $fields);

		if (!is_array($taskData) || empty($taskData))
		{
			return $searchIndex;
		}

		$fieldValues = [];

		foreach ($fields as $field)
		{
			$fieldValue = static::getFieldValue($field, $taskData);
			$fieldValues = array_merge($fieldValues, $fieldValue);
		}

		if (!empty($fieldValues))
		{
			$searchIndex = implode(' ', $fieldValues);
			$searchIndex = array_unique(explode(' ', $searchIndex));
			$searchIndex = implode(' ', $searchIndex);

			$searchIndex = static::prepareSearchIndex($searchIndex);
		}

		return $searchIndex;
	}

	/**
	 * Run stepper for full task's indexing (includes indexes of tasks and their comments).
	 */
	public static function runFullTasksIndexing(): void
	{
		if (Option::get("tasks", "needFullTasksIndexing") === 'N')
		{
			Option::set("tasks", "needFullTasksIndexing", "Y");
		}

		$agent = CAgent::GetList([], [
			'MODULE_ID' => 'tasks',
			'NAME' => '%FullTasksIndexer::execAgent();'
		])->Fetch();

		if (!$agent)
		{
			FullTasksIndexer::bind(0);
		}
	}

	/**
	 * @deprecated
	 * Transfer task's search index record to b_tasks_search_index.
	 * Method is deprecated and only used for stepper purposes.
	 */
	public static function transferTaskSearchIndex(int $taskId, string $searchIndex = ''): void
	{
		if ($searchIndex)
		{
			$searchIndex = static::prepareSearchIndex($searchIndex);
		}
		else
		{
			$searchIndex = (new IndexBuilder($taskId))->build();
		}

		SearchIndexTable::set($taskId, 0, $searchIndex);
	}

	public static function prepareStringToSearch(string $index, bool $isFullTextIndexEnabled = true): string
	{
		$index = trim($index);
		$index = mb_strtoupper($index);
		$index = self::prepareToken($index);

		if ($isFullTextIndexEnabled)
		{
			$index = Filter\Helper::matchAgainstWildcard($index);
		}

		return $index;
	}

	/**
	 * @deprecated
	 * @use IndexBuilder
	 */
	private static function getTaskData($task, $fields)
	{
		$taskData = $task;

		if (!is_array($task) && is_object($task))
		{
			if (is_a($task, Task::class))
			{
				try
				{
					$taskData = $task->getData($fields);
				}
				catch (Exception)
				{
					$taskData = false;
				}
			}
			else if (is_a($task, CTaskItem::class))
			{
				try
				{
					$taskData = $task->getData(false, [], false);
				}
				catch (Exception)
				{
					$taskData = false;
				}
			}
		}

		return $taskData;
	}

	/**
	 * @deprecated
	 * @use IndexBuilder
	 */
	private static function getFieldValue($field, $taskData): array
	{
		$fieldValue = [];

		switch ($field)
		{
			case 'TAGS':
				/** @var array|Collection $tags */
				$tags = $taskData[$field];
				$tags = (is_object($tags)? $tags->export() : (array)$tags);

				if ($tags)
				{
					$fieldValue[] = implode(' ', $tags);
				}
				break;

			case 'CREATED_BY':
			case 'RESPONSIBLE_ID':
				$fieldValue[] = implode(' ', User::getUserName([$taskData[$field]]));
				break;

			case 'AUDITORS':
				if (array_key_exists('AUDITORS', $taskData))
				{
					/** @var array|Collection $auditors */
					$auditors = $taskData[$field];
					$auditors = (is_object($auditors)? $auditors->toArray() : (array)$auditors);

					if ($auditors)
					{
						$fieldValue[] = implode(' ', User::getUserName(array_unique($auditors)));
					}
				}
				break;

			case 'ACCOMPLICES':
				if (array_key_exists('ACCOMPLICES', $taskData))
				{
					/** @var array|Collection $accomplices */
					$accomplices = $taskData[$field];
					$accomplices = (is_object($accomplices)? $accomplices->toArray() : (array)$accomplices);

					if ($accomplices)
					{
						$fieldValue[] = implode(' ', User::getUserName(array_unique($accomplices)));
					}
				}
				break;

			case 'GROUP_ID':
				$groupId = $taskData[$field];
				$groups = Group::getData([$groupId]);
				$groupName = ($groups[$groupId]['NAME'] ?? null);

				$fieldValue[] = $groupName;
				break;

			case 'SE_CHECKLIST':
				if (!isset($taskData[$field]) && isset($taskData['CHECKLIST']))
				{
					$field = 'CHECKLIST';
				}

				/** @var array|CheckList $checkList */
				$checkList = $taskData[$field];
				$checkList = (is_object($checkList)? $checkList->export() : (array)$checkList);

				foreach ($checkList as $item)
				{
					$fieldValue[] = $item['TITLE'];
				}
				break;

			case 'UF_CRM_TASK':
				if (Loader::includeModule('crm'))
				{
					if (!isset($taskData[$field]))
					{
						break;
					}
					/** @var array|Collection $crmItems */
					$crmItems = $taskData[$field];
					$crmItems = (is_object($crmItems)? $crmItems->toArray() : (array)$crmItems);

					foreach ($crmItems as $item)
					{
						if ($item)
						{
							$crmElement = explode('_', $item);
							$type = $crmElement[0];
							$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
							$title = CCrmOwnerType::GetCaption($typeId, $crmElement[1]);

							$fieldValue[] = $title;
						}
					}
				}
				break;

			// ID, TITLE, DESCRIPTION
			default:
				if (array_key_exists($field, $taskData) && !empty($taskData[$field]))
				{
					$fieldValue[] = $taskData[$field];
				}
				break;
		}

		return $fieldValue;
	}

	private static function prepareSearchIndex(string $searchIndex): string
	{
		$searchIndex = UI::convertBBCodeToHtmlSimple($searchIndex);
		if (Loader::includeModule('search'))
		{
			$searchIndex = CSearch::killTags($searchIndex);
		}
		$searchIndex = trim(str_replace(["\r", "\n", "\t"], " ", $searchIndex));
		$searchIndex = mb_strtoupper($searchIndex);

		return static::prepareToken($searchIndex);
	}

	private static function prepareToken(string $index): string
	{
		return str_rot13($index);
	}
}