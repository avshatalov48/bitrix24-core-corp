<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Stepper;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Update\AgentTrait;
use CAgent;

final class CollectorAgent implements AgentInterface
{
	use AgentTrait;

	private const CURSOR_OPTION = 'tasks_flow_copilot_warm_up_cursor';
	private const LIMIT_OPTION = 'tasks_flow_copilot_warm_up_limit';
	
	private const DEFAULT_LIMIT = 10;
	private const INTERVAL = 60 * 2;
	private const DELAY = 1;

	private static bool $processing = false;

	public static function addAgent(): void
	{
		CAgent::RemoveAgent(self::getAgentName(), 'tasks');

		self::cleanOptions();

		CAgent::AddAgent(
			name: self::getAgentName(),
			module: 'tasks',
			interval: self::INTERVAL,
		);
	}

	public static function execute(): string
	{
		if (self::$processing)
		{
			return self::getAgentName();
		}

		self::$processing = true;

		$agent = new self();
		$result = $agent->run();

		self::$processing = false;

		return $result;
	}

	private function run(): string
	{
		$cursor = $this->getCursor();
		$limit = $this->getLimit();

		$flowsData = FlowTaskTable::query()
			->addSelect('FLOW_ID')
			->addSelect(Query::expr()->setAlias('TASKS_COUNT')->countDistinct('TASK_ID'))
			->setOffset($cursor)->setLimit($limit)
			->setGroup(['FLOW_ID'])
			->exec()
			->fetchAll();

		if (empty($flowsData))
		{
			self::cleanOptions();

			return '';
		}

		foreach ($flowsData as $flowData)
		{
			$flowId = (int)$flowData['FLOW_ID'];
			$tasksCount = (int)$flowData['TASKS_COUNT'];

			if ($tasksCount < Configuration::getMinFlowTasksCount())
			{
				continue;
			}

			Collector::execute($flowId, self::DELAY);
		}

		$this->setCursor($cursor + count($flowsData));

		return self::getAgentName();
	}

	private function getCursor(): int
	{
		return (int)Option::get('tasks', self::CURSOR_OPTION, 0);
	}

	private function setCursor(int $cursor): void
	{
		Option::set('tasks', self::CURSOR_OPTION, $cursor);
	}

	private function getLimit(): int
	{
		return (int)Option::get('tasks', self::LIMIT_OPTION, self::DEFAULT_LIMIT);
	}

	private static function cleanOptions(): void
	{
		Option::delete('tasks', ['name' => self::CURSOR_OPTION]);
		Option::delete('tasks', ['name' => self::LIMIT_OPTION]);
	}
}
