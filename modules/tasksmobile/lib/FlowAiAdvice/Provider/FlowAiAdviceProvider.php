<?php

namespace Bitrix\TasksMobile\FlowAiAdvice\Provider;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;
use Bitrix\Tasks\Flow\Integration\AI\FlowCopilotFeature;
use Bitrix\Tasks\Flow\Integration\AI\Provider\AdviceProvider;
use Bitrix\Tasks\Flow\Internal\Entity\FlowCopilotAdvice;
use Bitrix\Tasks\Flow\Provider\UserProvider;
use Bitrix\Tasks\Flow\User\User;
use Bitrix\TasksMobile\FlowAiAdvice\Dto\FlowAiAdviceDto;

class FlowAiAdviceProvider
{
	public function getFlowsAiAdvices(array $flowIds, array $flowsTasksCount): array
	{
		if (empty($flowIds) || !$this->isFlowAiAdviceAvailable())
		{
			return [];
		}

		$flowsAiAdvices = array_fill_keys(
			$flowIds,
			[
				'minTasksCountForAdvice' => Configuration::getMinFlowTasksCount(),
				'efficiencyThreshold' => Configuration::getMaxValueForLowEfficiency(),
			],
		);
		$adviceCollection = (new AdviceProvider())->getList($flowIds);

		foreach ($flowIds as $flowId)
		{
			$adviceObject = $adviceCollection?->getByPrimary($flowId);
			$flowTasksCount = $flowsTasksCount[$flowId] ?? 0;

			$flowsAiAdvices[$flowId]['advices'] = $this->getFlowAdvices($adviceObject, $flowTasksCount);
		}

		return array_map(fn($item) => FlowAiAdviceDto::make($item), $flowsAiAdvices);
	}

	public function isFlowAiAdviceAvailable(): bool
	{
		return Loader::includeModule('ai') && FlowCopilotFeature::isOn();
	}

	private function getFlowAdvices(?FlowCopilotAdvice $adviceObject, int $flowTasksCount): array
	{
		if ($flowTasksCount < Configuration::getMinFlowTasksCount())
		{
			return [
				[
					'factor' => Loc::getMessage('TASKSMOBILE_FLOW_AI_ADVICE_1_FACTOR'),
					'advice' => Loc::getMessage('TASKSMOBILE_FLOW_AI_ADVICE_1_ADVICE'),
				],
				[
					'factor' => Loc::getMessage('TASKSMOBILE_FLOW_AI_ADVICE_2_FACTOR'),
					'advice' => Loc::getMessage('TASKSMOBILE_FLOW_AI_ADVICE_2_ADVICE'),
				],
			];
		}

		$advices = [];

		if ($adviceObject)
		{
			$advicesData = $adviceObject->getAdvicesData();

			foreach ($advicesData as $adviceData)
			{
				$advices[] = [
					'factor' => $adviceData->factor,
					'advice' => $adviceData->advice,
				];
			}
		}

		return $this->prepareUserNames($advices);
	}

	private function prepareUserNames($advices): array
	{
		$userProvider =  new UserProvider();
		$userPattern = '/user_(\d+)/';

		$usersInfo = [];
		preg_match_all(
			$userPattern,
			implode(' ', array_map(fn($item) => "{$item['factor']} {$item['advice']}", $advices)),
			$matches,
		);
		if ($matches[1])
		{
			$usersInfo = $userProvider->getUsersInfo(array_unique($matches[1]));
		}

		$prepareUserNamesCallback = function (array $matches) use ($usersInfo) {
			$userId = (int)$matches[1];

			/** @var User $userInfo */
			if ($userInfo = ($usersInfo[$userId] ?? null))
			{
				return "[USER=$userId]$userInfo->name[/USER]";
			}

			return Loc::getMessage('TASKSMOBILE_FLOW_AI_ADVICE_USER_NAME_UNKNOWN');
		};

		foreach ($advices as $key => $item)
		{
			$advices[$key]['factor'] = preg_replace_callback($userPattern, $prepareUserNamesCallback, $item['factor']);
			$advices[$key]['advice'] = preg_replace_callback($userPattern, $prepareUserNamesCallback, $item['advice']);
		}

		return $advices;
	}
}
