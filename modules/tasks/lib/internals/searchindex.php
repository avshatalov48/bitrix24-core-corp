<?php
namespace Bitrix\Tasks\Internals;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Task\Search\Builder\IndexBuilder;
use Bitrix\Tasks\Internals\Task\Search\Exception\SearchIndexException;
use Bitrix\Tasks\Internals\Task\SearchIndexTable;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Update\FullTasksIndexer;
use CAgent;
use CSearch;

/**
 * Class SearchIndex
 *
 * @package Bitrix\Tasks\Internals
 */
class SearchIndex
{
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

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
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