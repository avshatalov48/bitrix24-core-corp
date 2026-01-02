<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\Agents;

use Bitrix\AiAssistant\Definition\Agent\BaseAgent;
use Bitrix\AiAssistant\Definition\Dto\DefinitionMetadataDto;
use Bitrix\AiAssistant\Definition\Dto\SystemPromptDto;
use Bitrix\AiAssistant\Definition\Dto\UsesToolsDto;


class CrmExecutorAgent extends BaseAgent
{
	public function getCode(): string
	{
		return 'crm_executor';
	}

	public function getSystemPrompt(): SystemPromptDto
	{
		return new SystemPromptDto(
			name: 'CrmExecutorAgent',
			promptCode: 'marta_ai_crm_executor_reporter',
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

	public function getMetadata(): DefinitionMetadataDto
	{
		return new DefinitionMetadataDto(
			'CRM Agent (Executor Output)',
			'CRM Agent (Executor Output)',
		);
	}
}
