<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Agents;

use Bitrix\AiAssistant\Definition\Agent\BaseAgent;
use Bitrix\AiAssistant\Definition\Dto\DefinitionMetadataDto;
use Bitrix\AiAssistant\Definition\Dto\SystemPromptDto;
use Bitrix\AiAssistant\Definition\Dto\UsesToolsDto;
use Bitrix\Crm\Integration\AiAssistant\Tools\CategoryRenameTool;
use Bitrix\Crm\Integration\AiAssistant\Tools\CategoryUpdateStagesList;
use Bitrix\Crm\Integration\AiAssistant\Tools\CreateUserFieldTool;


class CrmExecutorPlannerAgent extends BaseAgent
{
	public function getCode(): string
	{
		return 'crm_executor_planner';
	}

	public function getSystemPrompt(): SystemPromptDto
	{
		return new SystemPromptDto(
			name: 'CrmExecutorPlannerAgent',
			promptCode: 'marta_ai_crm_executor',
		);
	}

	public function getUsesTools(): UsesToolsDto
	{
		return new UsesToolsDto([
			CreateUserFieldTool::class,
			CategoryRenameTool::class,
			CategoryUpdateStagesList::class,
		]);
	}

	public function canList(int $userId): bool
	{
		return true;
	}

	public function canRun(int $userId): bool
	{
		return true;
	}

	public function getMetadata(): DefinitionMetadataDto
	{
		return new DefinitionMetadataDto(
			'CRM Agent (Executor Planner)',
			'CRM Agent (Executor Planner)',
		);
	}
}
