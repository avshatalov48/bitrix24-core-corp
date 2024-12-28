<?php

namespace Bitrix\Crm\Integration\AI\Enum;

use Bitrix\Crm\Integration\AI\EventHandler;

enum EngineSetting: string
{
	/** @see EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_TEXT_CODE */
	case FillItemFromCallText = 'crm_copilot_fill_item_from_call_engine_text';

	/** @see EventHandler::SETTINGS_FILL_ITEM_FROM_CALL_ENGINE_AUDIO_CODE */
	case FillItemFromCallAudio = 'crm_copilot_fill_item_from_call_engine_audio';

	/** @see EventHandler::SETTINGS_CALL_ASSESSMENT_ENGINE_CODE */
	case CallAssessment = 'crm_copilot_call_assessment_engine_code';

	public function getCode(): string
	{
		return $this->value;
	}

	public function getCategory(): string
	{
		return match ($this) {
			self::FillItemFromCallText, self::CallAssessment => 'text',
			self::FillItemFromCallAudio => 'audio',
		};
	}
}
