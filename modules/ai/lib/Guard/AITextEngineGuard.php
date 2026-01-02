<?php declare(strict_types=1);

namespace Bitrix\AI\Guard;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;


class AITextEngineGuard implements Guard
{
	protected bool $hasTextEngine;

	public function hasAccess(?int $userId = null): bool
	{
		return $this->hasEngine();
	}

	protected function hasEngine(): bool
	{
		if (!isset($this->hasTextEngine))
		{
			$this->hasTextEngine = !is_null(
				Engine::getByCategory(Engine::CATEGORIES['text'], Context::getFake())
			);
		}

		return $this->hasTextEngine;
	}
}
