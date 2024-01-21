<?php

namespace Bitrix\Crm\Counter\ProblemDetector;

use Bitrix\Crm\Counter\ProblemDetector\Logger\ProblemSerializer;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use COption;

class Logger
{
	use Singleton;

	private const COUNTER_PROBLEM_DETECTOR_TAG = '#crm_counter_problem_detector';

	private ProblemSerializer $problemSerializer;

	public function __construct()
	{
		$this->problemSerializer = ProblemSerializer::getInstance();
	}

	public function log(ProblemList $problemList, array $extra = []): void
	{
		$problemLevel = $this->getProblemLevel($problemList->problemCount());

		$logData = [];
		foreach ($problemList->getProblems() as $problem)
		{
			if (!$problem->hasProblem())
			{
				continue;
			}

			$logData[] = $this->problemSerializer->serializeProblems($problem);
		}

		$log = [
			'tag' => self::COUNTER_PROBLEM_DETECTOR_TAG,
			'level' => $problemLevel,
			'portal' => $this->getPortalName(),
			'count' => $problemList->problemCount(),
			'providers' => $this->problemSerializer->makeProviderSummary($problemList),
			'problems' => $logData,
			'extra' => $extra
		];

		$logStr = json_encode($log);

		AddMessage2Log(
			$logStr,
			'crm',
			0
		);
	}

	private function getPortalName(): string
	{
		$portalName = COption::getOptionString('main', 'server_name', Application::getInstance()->getContext()->getServer()->getServerName());

		return $portalName ?: '';
	}

	private function getProblemLevel(int $problemCount): string
	{

		if ($problemCount <= 3)
		{
			return '#crm_counter_problem_level_low';
		}
		elseif ($problemCount <= 10)
		{
			return '#crm_counter_problem_level_medium';
		}
		elseif ($problemCount <= 50)
		{
			return '#crm_counter_problem_level_high';
		}
		else
		{
			return '#crm_counter_problem_level_critical';
		}
	}
}