<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\AI\Call;

use Bitrix\Crm\Badge\Type\AiCallFieldsFillingResult;
use Bitrix\Crm\Integration\AI\ErrorCode;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;

final class AutomationLaunchError extends Base
{
	public function getType(): string
	{
		return 'AutomationLaunchError';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_LOG_MESSAGE_AI_AUTOMATION_LAUNCH_ERROR');
	}

	public function getTags(): ?array
	{
		$settings = $this->getModel()->getSettings();
		$firstErrorCode = $settings['ERROR_CODES'][0] ?? null;
		if ($firstErrorCode === null)
		{
			return null;
		}

		$tag = $this->getTagByErrorCode($firstErrorCode);
		if ($tag === null)
		{
			return null;
		}

		return [
			'automationLaunchError' => $tag,
		];
	}

	private function getTagByErrorCode(string $errorCode): ?Tag
	{
		return match($errorCode)
		{
			ErrorCode::AI_ENGINE_LIMIT_EXCEEDED => new Tag(
				AiCallFieldsFillingResult::getLimitExceededTextValue(),
				Tag::TYPE_FAILURE,
			),
			default => null,
		};
	}
}
