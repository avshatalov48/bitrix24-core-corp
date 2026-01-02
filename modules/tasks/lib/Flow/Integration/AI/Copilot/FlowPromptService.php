<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Copilot;

use Bitrix\AI\Prompt\Item;
use Bitrix\AI\Prompt\Manager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Flow\Integration\AI\Configuration;

class FlowPromptService
{
	public function getPrompt(): ?Item
	{
		if (!Loader::includeModule('ai'))
		{
			return null;
		}

		$promptCode = $this->getPromptCode();

		return Manager::getByCode($promptCode);
	}

	private function getPromptCode(): string
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return match ($region)
		{
			'ru', 'by', 'kz', 'uz' => Configuration::RECOMMENDATIONS_PROMPT_CODE,
			default => Configuration::RECOMMENDATIONS_AI_ACT_PROMPT_CODE,
		};
	}
}
