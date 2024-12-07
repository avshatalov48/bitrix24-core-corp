<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\LabelTable;
use Bitrix\Tasks\Internals\Task\TagTable;
use Exception;

class TagConverter implements AgentInterface
{
	use AgentTrait;

	public const OPTION_KEY = 'task_tag_converter';
	public const LIMIT = 500;

	private static bool $processing = false;

	public static function isProceed(): bool
	{
		return Option::get('tasks', self::OPTION_KEY, 'null') !== 'null';
	}

	public static function execute(): string
	{
		if (self::$processing)
		{
			return static::getAgentName();
		}

		self::$processing = true;

		$agent = new self();
		$res = $agent->run();

		self::$processing = false;

		return $res;
	}

	private function run(): string
	{
		if (!Loader::includeModule('tasks'))
		{
			$this->convertDone();
			return '';
		}

		$oldTags = $this->getOldList();

		if (empty($oldTags))
		{
			$this->convertDone();
			return '';
		}

		$taskTagRelations = [];

		foreach ($oldTags as $tag)
		{
			$id = $this->getIdInLabelTable($tag);
			if (is_null($id))
			{
				try
				{
					$result = LabelTable::add([
						'NAME' => trim($tag['NAME']),
						'USER_ID' => (int)$tag['GROUP_ID'] === 0 ? $tag['USER_ID'] : 0,
						'GROUP_ID' => $tag['GROUP_ID'],
					]);
				}
				catch (\Exception $e)
				{
					(new Log())->collect("Unable to convert tag {$tag['NAME']}: {$e->getMessage()}");
					continue;
				}

				$id = $result->isSuccess() ? $result->getId() : null;
			}

			if (!is_null($id))
			{
				$taskTagRelations[] = [
					'TAG_ID' => $id,
					'TASK_ID' => $tag['TASK_ID'],
				];
			}
		}

		try
		{
			$this->saveRelations($taskTagRelations);
			$this->markTagsAsConverted($oldTags);
		}
		catch (Exception $e)
		{
			(new Log())->collect("Unable to convert tags: {$e->getMessage()}");
			return '';
		}

		if ($this->needToStop())
		{
			$this->convertDone();
			return '';
		}

		Option::set('tasks', self::OPTION_KEY, 'process');

		return static::getAgentName();
	}

	private function getIdInLabelTable(array $tag): ?int
	{
		$data = LabelTable::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=USER_ID' => (int)$tag['GROUP_ID'] === 0 ? $tag['USER_ID'] : 0,
				'=NAME' => trim($tag['NAME']),
				'=GROUP_ID' => $tag['GROUP_ID'],
			],
		])->fetch();

		if (empty($data))
		{
			return null;
		}

		return (int)$data['ID'];
	}

	private function getOldList(): array
	{
		$tagRows = TagTable::getList([
			'select' => [
				'*',
				'BT_' => 'TASK',
			],
			'order' => [
				'NAME' => 'asc',
			],
			'filter' => [
				'=CONVERTED' => 0,
			],
			'limit' => self::LIMIT,
		])->fetchAll();

		$tags = [];

		foreach ($tagRows as $row)
		{
			$tags[] = [
				'NAME' => $row['NAME'],
				'TASK_ID' => $row['TASK_ID'],
				'USER_ID' => $row['USER_ID'],
				'GROUP_ID' => $row['BT_GROUP_ID'],
			];
		}

		return $tags;
	}

	private function saveRelations(array $relations): void
	{
		if (empty($relations))
		{
			return;
		}

		$relationsToImplode = [];

		foreach ($relations as $props)
		{
			$tagId = (int)$props['TAG_ID'];
			$taskId = (int)$props['TASK_ID'];
			if ($taskId !== 0 && $tagId !== 0)
			{
				$relationsToImplode [] = "({$tagId}, {$taskId})";
			}
		}
		if (empty($relationsToImplode))
		{
			return;
		}
		$relationsToImplode = implode(',', $relationsToImplode);
		$connection = Application::getConnection();
		$sql = $connection->getSqlHelper()->getInsertIgnore(
			LabelTable::getRelationTable(),
			' (TAG_ID, TASK_ID)',
			" VALUES {$relationsToImplode}"
		);

		$connection->query($sql);
	}

	private function markTagsAsConverted(array $tags): void
	{
		$implode = array_map(function ($el): string {
			$userId = (int)$el['USER_ID'];
			$name = Application::getConnection()->getSqlHelper()->forSql($el['NAME']);
			$taskId = (int)$el['TASK_ID'];
			return "({$userId}, {$taskId}, '{$name}')";
		}, $tags);

		$implode = implode(',', $implode);
		$implode = "({$implode})";

		$sql =
			'UPDATE '
			. TagTable::getTableName()
			. " SET CONVERTED = 1 WHERE (USER_ID, TASK_ID, NAME) IN {$implode}"
		;

		Application::getConnection()->query($sql);
	}

	private function convertDone(): void
	{
		Option::delete(
			'tasks',
			[
				'name' => self::OPTION_KEY,
				'site_id' => '-',
			]
		);
	}

	private function needToStop(): bool
	{
		if (empty($this->getOldList()))
		{
			return true;
		}

		return false;
	}
}
