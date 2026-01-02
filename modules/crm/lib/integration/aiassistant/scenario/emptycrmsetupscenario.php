<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Scenario;

use Bitrix\AiAssistant\Core\Dto\ChatContextDto;
use Bitrix\AiAssistant\Core\Enum\ScenarioCompletedStatus;
use Bitrix\AiAssistant\Definition\Dto\DefinitionMetadataDto;
use Bitrix\AiAssistant\Definition\Dto\SystemPromptDto;
use Bitrix\AiAssistant\Definition\Dto\UsesToolsDto;
use Bitrix\AiAssistant\Definition\Scenario\BaseScenario;
use Bitrix\AiAssistant\Trigger\Action\FirstDealAction;
use Bitrix\AiAssistant\Trigger\Service\TriggerManagerService;
use Bitrix\Main\DI\ServiceLocator;

class EmptyCrmSetupScenario extends BaseScenario
{
	public function getCode(): string
	{
		return 'empty_crm_setup';
	}

	public function getMetadata(): DefinitionMetadataDto
	{
		return new DefinitionMetadataDto(
			'Setup Empty CRM',
			'This scenario is helping new users to initially set up their CRM',
		);
	}

	public function getSystemPrompt(): SystemPromptDto
	{
		return new SystemPromptDto(
			name: 'EmptyCrmSetupScenario',
			promptCode: 'marta_ai_crm_planner',
		);
	}

	public function getUsesTools(): UsesToolsDto
	{
		return new UsesToolsDto();
	}

	public function canList(int $userId): bool
	{
		return true;
	}

	public function canRun(int $userId): bool
	{
		return true;
	}

	public function onScenarioCompleted(ChatContextDto $chatContextDto, ScenarioCompletedStatus $status): void
	{
		match ($status)
		{
			ScenarioCompletedStatus::Success => $this->remindOfADeal($chatContextDto),
			default => null,
		};
	}

	private function remindOfADeal(ChatContextDto $chatContextDto): void
	{
		$this->setupTrigger($chatContextDto->userId);
	}

	private function setupTrigger(int $userId): void
	{
		$triggerManagerService = ServiceLocator::getInstance()->get(TriggerManagerService::class);
		if (!$triggerManagerService)
		{
			return;
		}

		$triggerManagerService->addAccessForTrigger(FirstDealAction::class, $userId);
	}
}
