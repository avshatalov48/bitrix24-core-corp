<?php

namespace Bitrix\Crm\Integration\AI\Operation\Autostart;

use Bitrix\Crm\Copilot\CallAssessment\Enum\AutoCheckType;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use Bitrix\Crm\Integration\AI\Operation\ScoreCall;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use CCrmActivityDirection;

final class ScoreCallSettings implements AutoStartInterface
{
	private const AUTOSTART_OPERATION_TYPES = [
		TranscribeCallRecording::TYPE_ID,
		ScoreCall::TYPE_ID,
	];

	public function __construct(private readonly int $autoCheckType)
	{
	}

	public function shouldAutostart(int $operationType, int $callDirection): bool
	{
		if (
			!(
				AIManager::isAiCallAutomaticProcessingAllowed()
				&& AIManager::isBaasServiceHasPackage()
				&& AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment)
			)
		)
		{
			return false;
		}

		if ($this->autoCheckType === AutoCheckType::DISABLED->value)
		{
			return false;
		}

		if (!in_array($operationType, self::AUTOSTART_OPERATION_TYPES, true))
		{
			return false;
		}

		return (
				$callDirection === CCrmActivityDirection::Incoming
				&& (in_array($this->autoCheckType, [AutoCheckType::INCOMING->value, AutoCheckType::FIRST_INCOMING->value], true))
			) // all incoming and first incoming
			|| (
				$callDirection === CCrmActivityDirection::Outgoing
				&& $this->autoCheckType === AutoCheckType::OUTGOING->value
			) // all outgoing
			|| (
				in_array($callDirection, [CCrmActivityDirection::Incoming, CCrmActivityDirection::Outgoing], true)
				&& $this->autoCheckType === AutoCheckType::ALL->value
			) // all calls
		;
	}

	public function isAutostartTranscriptionOnlyOnFirstCallWithRecording(): bool
	{
		return $this->autoCheckType === AutoCheckType::FIRST_INCOMING->value;
	}
}
