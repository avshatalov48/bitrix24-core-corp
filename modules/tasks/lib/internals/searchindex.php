<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\Item\Task;
use Bitrix\Tasks\Item\Task\Collection\CheckList;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Update\FullTasksIndexer;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\User;

/**
 * Class SearchIndex
 *
 * @package Bitrix\Tasks\Internals
 */
class SearchIndex
{
	private static $fields = [
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

	/**
	 * @param $taskId
	 * @param array $taskData
	 * @param string $searchIndex
	 * @throws LoaderException
	 * @throws SqlQueryException
	 */
	public static function setTaskSearchIndex($taskId, $taskData = [], $searchIndex = '')
	{
		if (!$searchIndex)
		{
			if (empty($taskData))
			{
				$searchIndex = static::getTaskSearchIndex($taskId);
			}
			else
			{
				$searchIndex = static::buildTaskSearchIndex($taskData);
			}
		}
		else
		{
			$searchIndex = static::prepareSearchIndex($searchIndex);
		}

		SearchIndexTable::set($taskId, 0, $searchIndex);
	}

	/**
	 * @param $taskId
	 * @param $commentId
	 * @param $commentText
	 * @throws LoaderException
	 * @throws SqlQueryException
	 */
	public static function setCommentSearchIndex($taskId, $commentId, $commentText)
	{
		$searchIndex = htmlspecialcharsback($commentText);
		$searchIndex = static::prepareSearchIndex($searchIndex);

		SearchIndexTable::set($taskId, $commentId, $searchIndex);
	}

	/**
	 * Return task's search index
	 *
	 * @param $taskId
	 * @return string
	 * @throws LoaderException
	 */
	public static function getTaskSearchIndex($taskId)
	{
		$searchIndex = '';
		if ((int)$taskId > 0)
		{
			$task = \CTaskItem::getInstanceFromPool($taskId, 1);
			$searchIndex = static::buildTaskSearchIndex($task);
		}

		return $searchIndex;
	}

	/**
	 * Build search index for task based on $fields.
	 *
	 * @param array|\CTaskItem|Task $task
	 * @param array $fields
	 * @return string
	 * @throws LoaderException
	 */
	public static function buildTaskSearchIndex($task, array $fields = [])
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
	 *
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public static function runFullTasksIndexing()
	{
		if (Option::get("tasks", "needFullTasksIndexing") === 'N')
		{
			Option::set("tasks", "needFullTasksIndexing", "Y");
		}

		$agent = \CAgent::GetList([], [
			'MODULE_ID' => 'tasks',
			'NAME' => '%FullTasksIndexer::execAgent();'
		])->Fetch();

		if (!$agent)
		{
			FullTasksIndexer::bind(0);
		}
	}

	/**
	 * Transfer task's search index record to b_tasks_search_index.
	 * Method is deprecated and only used for stepper purposes.
	 *
	 * @param $taskId
	 * @param string $searchIndex
	 * @throws LoaderException
	 * @throws SqlQueryException
	 *
	 * @deprecated
	 */
	public static function transferTaskSearchIndex($taskId, $searchIndex = '')
	{
		if ($searchIndex)
		{
			$searchIndex = static::prepareSearchIndex($searchIndex);
		}
		else
		{
			$searchIndex = static::getTaskSearchIndex($taskId);
		}

		SearchIndexTable::set($taskId, 0, $searchIndex);
	}

	/**
	 * @param $string
	 * @param $isFullTextIndexEnabled
	 * @return string
	 */
	public static function prepareStringToSearch($string, $isFullTextIndexEnabled = true): string
	{
		$string = trim($string);
		$string = ToUpper($string);
		$string = self::prepareToken($string);

		if ($isFullTextIndexEnabled)
		{
			$string = Filter\Helper::matchAgainstWildcard($string, '*');
		}

		return $string;
	}

	/**
	 * @param $task
	 * @param $fields
	 * @return array|bool|mixed|null
	 */
	private static function getTaskData($task, $fields)
	{
		$taskData = $task;

		if (!is_array($task) && is_object($task))
		{
			if (is_a($task, '\Bitrix\Tasks\Item\Task'))
			{
				try
				{
					/** @var Task $task */
					$taskData = $task->getData($fields);
				}
				catch (\Exception $exception)
				{
					$taskData = false;
				}
			}
			else if (is_a($task, '\CTaskItem'))
			{
				try
				{
					/** @var \CTaskItem $task */
					$taskData = $task->getData(false, [], false);
				}
				catch (\Exception $exception)
				{
					$taskData = false;
				}
			}
		}

		return $taskData;
	}

	/**
	 * @param $field
	 * @param $taskData
	 * @return array
	 * @throws LoaderException
	 */
	private static function getFieldValue($field, $taskData)
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
							$typeId = \CCrmOwnerType::ResolveID(\CCrmOwnerTypeAbbr::ResolveName($type));
							$title = \CCrmOwnerType::GetCaption($typeId, $crmElement[1]);

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

	/**
	 * @param $searchIndex
	 * @return string
	 * @throws LoaderException
	 */
	private static function prepareSearchIndex($searchIndex)
	{
		$searchIndex = UI::convertBBCodeToHtmlSimple($searchIndex);
		if (Loader::includeModule('search'))
		{
			$searchIndex = \CSearch::killTags($searchIndex);
		}
		$searchIndex = trim(str_replace(["\r", "\n", "\t"], " ", $searchIndex));
		$searchIndex = ToUpper($searchIndex);
		$searchIndex = static::prepareToken($searchIndex);

		return $searchIndex;
	}

	/**
	 * @param $string
	 * @return string
	 */
	private static function prepareToken($string)
	{
		return str_rot13($string);
	}
}