<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Copilot;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Payload\Prompt;
use Bitrix\AI\Payload\Text;
use Bitrix\AI\Prompt\Manager;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\FlowSettings;
use Bitrix\Tasks\Flow\Integration\AI\Provider\CollectedDataProvider;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\AI\Payload\Tokens\HiddenToken;
use Bitrix\AI\Payload\Tokens\TokenType;

class RequestSender
{
	private const FLOWS_PROMPT_CODE = 'flows_recommendations';

	public function sendRequest(int $flowId): void
	{
		if (!Loader::includeModule('ai'))
		{
			return;
		}

		$flow = (new FlowProvider())->getFlow($flowId);

		$provider = new CollectedDataProvider();

		$tuningManager = new \Bitrix\AI\Tuning\Manager();
		$tuningEngineItem = $tuningManager->getItem(FlowSettings::TUNING_CODE_FLOWS_TEXT_ENGINE);
		if ($tuningEngineItem === null)
		{
			return;
		}

		$context = new Context(
			'tasks',
			'flow',
			$flow->getCreatorId(),
		);
		$category = Engine::CATEGORIES['text'];
		$engine = Engine::getByCode(
			$tuningEngineItem->getValue(),
			$context,
			$category,
		);
		if ($engine === null)
		{
			return;
		}

		$prompt = Manager::getByCode(self::FLOWS_PROMPT_CODE);
		if ($prompt === null)
		{
			return;
		}

		$payload = new Prompt($prompt->getCode());
		$collectedData = $provider->get($flowId);
		$flowData = $collectedData->getData()['flow'] ?? [];

		$teamEfficiencyPercentage = (int)($flowData['team_efficiency_percentage'] ?? 0);
		$inputJson = $collectedData->getPayload();
		$payload->setMarkers(
			[
				'flow_efficiency' => ($teamEfficiencyPercentage <= Configuration::getMaxValueForLowEfficiency()) ? 'low' : 'high',
				'skip_workoff_days' => ($flowData['match_work_time'] ?? null) === 'Yes' ? 'true' : 'false',
				'create_tasks_by_template' => ($flowData['create_tasks_by_template'] ?? null) === 'Yes' ? 'true' : 'false',
				'employee_can_change_deadline' => $flow->canResponsibleChangeDeadline() ? 'true' : 'false',
				'distribution_type' => $flowData['distribution_type'] ?? '',
				'input_json' => $inputJson,
			],
		);

		$userMatches = [];
		$userRegExp = Configuration::getUserRegExp();
		preg_match_all($userRegExp, $inputJson, $userMatches);

		$sensitiveFields = array_unique($userMatches[0]);

		$hiddenTokens = [];
		foreach ($sensitiveFields as $sensitiveField)
		{
			$hiddenTokens[] = (new HiddenToken($sensitiveField, TokenType::INTEGER_ID))->setPrefix('user_');
		}

		$payload->setHiddenTokens($hiddenTokens);

		$engine
			->setPayload($payload)
			->setParameters(['flowId' => $flowId])
		;

		$engine->completionsInQueue();
	}

	public function onFlowDataCollected(Event $event): EventResult
	{
		$flowId = (int)$event->getParameter('flowId');
		if ($flowId <= 0)
		{
			return new EventResult(EventResult::ERROR);
		}

		$this->sendRequest($flowId);

		return new EventResult(EventResult::SUCCESS);
	}
}