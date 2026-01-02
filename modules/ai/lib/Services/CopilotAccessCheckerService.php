<?php declare(strict_types=1);

namespace Bitrix\AI\Services;

use Bitrix\AI\Guard\IntranetGuard;
use Bitrix\AI\Guard\AITextEngineGuard;
use Bitrix\AI\Guard\CollaberGuard;

/**
 * For integration this services use
 * 		\Bitrix\AI\Container::init()->getItem(\Bitrix\AI\Services\CopilotAccessCheckerService::class)
 */
class CopilotAccessCheckerService
{
	public function __construct(
		protected IntranetGuard $intranetGuard,
		protected AITextEngineGuard $aiTextEngineGuard,
		protected CollaberGuard $collaberGuard,
	)
	{
	}

	public function canShowInFrontend(?int $userId = null): bool
	{
		if (!$this->canAccessEngines($userId))
		{
			return false;
		}

		return $this->aiTextEngineGuard->hasAccess($userId);
	}

	public function canShowLibrariesInFrontend(?int $userId = null): bool
	{
		if (!$this->intranetGuard->hasAccess($userId))
		{
			return false;
		}

		return $this->aiTextEngineGuard->hasAccess($userId);
	}

	public function canAccessEngines(?int $userId = null): bool
	{
		if ($userId === 0)
		{
			return true;
		}

		return $this->intranetGuard->hasAccess($userId) || $this->collaberGuard->hasAccess($userId);
	}
}
