<?php

namespace Bitrix\Crm\RepeatSale;

use Bitrix\Bitrix24\Feature;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Event;

class AgentsManager
{
	use Singleton;

	public const SCHEDULER_AGENT_NAME = 'Bitrix\\Crm\\Agent\\RepeatSale\\SchedulerAgent::run();';
	public const JOB_EXECUTOR_AGENT_NAME = 'Bitrix\\Crm\\Agent\\RepeatSale\\JobExecutorAgent::run();';

	private const AGENT_NAMES = [
		self::SCHEDULER_AGENT_NAME,
		self::JOB_EXECUTOR_AGENT_NAME,
	];

	protected \CAgent $cAgent;
	protected Logger $logger;

	public static function onLicenseHasChanged(Event $event): void
	{
		$licenseType = $event->getParameter('licenseType');

		if ($licenseType === null)
		{
			return;
		}

		$agentsManager = new self();
		if (Feature::isFeatureEnabledFor('crm_repeat_sale', $licenseType))
		{
			$agentsManager->enableAgents();
		}
		else
		{
			$agentsManager->disableAgents();
		}
	}

	protected function __construct()
	{
		$this->cAgent = new \CAgent();
		$this->logger = new Logger();
	}

	public function addFlowEnablerAgent(int $offset): void
	{
		/**
		 * @see \Bitrix\Crm\Agent\RepeatSale\FlowEnablerAgent
		 */
		$this->cAgent::AddAgent(
			'Bitrix\Crm\Agent\RepeatSale\FlowEnablerAgent::run();',
			'crm',
			'N',
			3600,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + $offset, 'FULL'),
		);
	}

	public function addOnlyCalcSchedulerAgent(): void
	{
		$this->cAgent::AddAgent(
			'Bitrix\Crm\Agent\RepeatSale\OnlyCalcSchedulerAgent::run();',
			'crm',
			'N',
			3600,
			'',
			'Y',
			\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 60 * 60 * 24 * 7, 'FULL'),
		);
	}

	public function removeOnlyCalcSchedulerAgent(): void
	{
		$this->cAgent::RemoveAgent(
			'Bitrix\\Crm\\Agent\\RepeatSale\\OnlyCalcSchedulerAgent::run();',
			'crm',
		);
	}

	public function addAgents(): void
	{
		$agents = $this->getList();
		$addedAgents = [];

		if (!isset($agents[self::SCHEDULER_AGENT_NAME]))
		{
			/**
			 * @see \Bitrix\Crm\Agent\RepeatSale\SchedulerAgent
			 */
			$this->cAgent::AddAgent(
				'\\Bitrix\\Crm\\Agent\\RepeatSale\\SchedulerAgent::run();',
				'crm',
				'N',
				3600 * 8,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 10, 'FULL'),
			);

			$addedAgents[] = self::SCHEDULER_AGENT_NAME;
		}

		if (!isset($agents[self::JOB_EXECUTOR_AGENT_NAME]))
		{
			/**
			 * @see \Bitrix\Crm\Agent\RepeatSale\JobExecutorAgent
			 */
			$this->cAgent::AddAgent(
				'\\Bitrix\\Crm\\Agent\\RepeatSale\\JobExecutorAgent::run();',
				'crm',
				'N',
				60,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 80, 'FULL'),
			);

			$addedAgents[] = self::JOB_EXECUTOR_AGENT_NAME;
		}

		if (!empty($addedAgents))
		{
			$this->logger->debug('AgentsManager add agents: ', $addedAgents);
		}
	}

	public function enableAgents(): void
	{
		$fields = ['ACTIVE' => 'Y'];
		$this->updateAgents($fields);

		$this->logger->info('AgentsManager enable agents', []);
	}

	public function disableAgents(): void
	{
		$fields = ['ACTIVE' => 'N'];
		$this->updateAgents($fields);

		$this->logger->info('AgentsManager disable agents', []);
	}

	private function updateAgents(array $fields): void
	{
		$agents = $this->getList();

		foreach ($agents as $agent)
		{
			if ($agent['ACTIVE'] !== $fields['ACTIVE'])
			{
				$this->cAgent::Update($agent['ID'], $fields);
			}
		}
	}

	private function getList(): array
	{
		$result = [];
		foreach (self::AGENT_NAMES as $agentName)
		{
			$data = $this->getAgentDataByName($agentName);
			if (empty($data))
			{
				continue;
			}

			$result[$data['NAME']] = $data;
		}

		return $result;
	}

	private function getAgentDataByName(string $agentName): array
	{
		$res = $this->cAgent::GetList([], ['=NAME' => $agentName]);
		if ($agent = $res->Fetch())
		{
			return $agent;
		}

		return [];
	}
}
