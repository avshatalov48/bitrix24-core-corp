<?php

namespace Bitrix\Crm\Copilot\AiQualityAssessment;

use Bitrix\Main\Loader;
use CPullWatch;

final class PullManager
{
	public const ADD_CALL_SCORING_PULL_COMMAND = 'call_scoring_add';

	/** @var CPullWatch|string|null */
	private CPullWatch|string|null $pullWatch = null;

	public function __construct()
	{
		if ($this->includePullModule())
		{
			$this->pullWatch = CPullWatch::class;
		}
	}

	private function includePullModule(): bool
	{
		return Loader::includeModule('pull');
	}

	public function sendPullEvent(int $activityId, array $params, string $command): void
	{
		if (!$this->includePullModule())
		{
			return;
		}

		$pushParams = $this->preparePushParamsByCommand($activityId, $params);

		$this->pullWatch::AddToStack(
			self::ADD_CALL_SCORING_PULL_COMMAND,
			[
				'module_id' => 'crm',
				'command' => $command,
				'params' => $pushParams,
			]
		);
	}

	private function preparePushParamsByCommand(int $activityId, array $params): array
	{
		return [
			'activityId' => $activityId,
			'jobId' => $params['jobId'] ?? null,
			'ratedUserId' => $params['ratedUserId'] ?? null,
			'assessmentSettingsId' => $params['assessmentSettingsId'] ?? null,
		];
	}

	public function subscribe(int $userId, int $activityId): void
	{
		if (!$this->includePullModule())
		{
			return;
		}

		$this->pullWatch::Add($userId, self::ADD_CALL_SCORING_PULL_COMMAND);
	}
}
