<?php

namespace Bitrix\Tasks\Update;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\Log\Log;
use Bitrix\Tasks\Internals\Task\EO_Template_Collection;
use Bitrix\Tasks\Internals\Task\Template\ScenarioTable;
use Bitrix\Tasks\Internals\Task\TemplateTable;

class SyncTemplateScenario implements AgentInterface
{
	use AgentTrait;

	public const CURSOR_KEY = 'sync_templates_scenario_cursor';
	public const LIMIT = 500;
	private static bool $processing = false;

	private function __construct()
	{

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
			return '';
		}

		try
		{
			// fetch templates to sync
			$cursor = $this->getCursor();
			$templates = $this->getList($cursor);
			$latestTemplateId = 0;
			// insert default scenario
			foreach ($templates as $template)
			{
				ScenarioTable::insertIgnore($template->getId(), ScenarioTable::SCENARIO_DEFAULT);
				$latestTemplateId = $template->getId();
			}

			if ($latestTemplateId)
			{
				$this->setCursor($latestTemplateId);
			}

			if ($templates->count() < self::LIMIT)
			{
				// sync is over, some clean up!
				Option::delete('tasks', ['name' => self::CURSOR_KEY]);
				return '';
			}
		}
		catch (\Exception $e)
		{
			(new Log())->collect('Unable to sync template scenario. '.$e->getMessage());
			return '';
		}

		return static::getAgentName();
	}

	/**
	 * @param int $cursor
	 * @return EO_Template_Collection
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getList(int $cursor): \Bitrix\Tasks\Internals\Task\EO_Template_Collection
	{
		return TemplateTable::getList([
			'select' => ['ID'],
			'filter' => ['<=ID' => $cursor],
			'order' => ['ID' => 'DESC'],
			'limit' => self::LIMIT
		])->fetchCollection();
	}

	/**
	 * @return int
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getLatestTemplateId(): int
	{
		$latestTemplate = TemplateTable::getList([
			'select' => ['ID'],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])->fetchObject();
		return $latestTemplate ? $latestTemplate->getId() : 0;
	}

	/**
	 * @return int
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getCursor(): int
	{
		$cursor = Option::get('tasks', self::CURSOR_KEY, 'n/a');
		if ($cursor !== 'n/a')
		{
			return $cursor;
		}
		// cursor is not set, return latest template id
		return $this->getLatestTemplateId();
	}

	private function setCursor(int $cursor): void
	{
		Option::set('tasks', self::CURSOR_KEY, $cursor);
	}
}