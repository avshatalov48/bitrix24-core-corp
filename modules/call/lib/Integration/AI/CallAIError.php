<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CallAIError extends \Bitrix\Call\Error
{
	public const
		AI_UNAVAILABLE_ERROR = 'AI_UNAVAILABLE_ERROR',
		AI_SETTINGS_ERROR = 'AI_SETTINGS_ERROR',
		AI_AGREEMENT_ERROR = 'AI_AGREEMENT_ERROR',
		AI_UNSUPPORTED_TRACK_ERROR = 'AI_UNSUPPORTED_TRACK_ERROR',
		AI_EMPTY_PAYLOAD_ERROR = 'AI_EMPTY_PAYLOAD_ERROR',
		AI_NOT_ENOUGH_BAAS_ERROR = 'AI_NOT_ENOUGH_BAAS_ERROR',
		AI_RECORD_TOO_SHORT = 'AI_RECORD_TOO_SHORT',
		AI_TRACKPACK_NOT_RECEIVED = 'AI_TRACKPACK_NOT_FOUND',
		AI_TRANSCRIBE_TASK_ERROR = 'AI_TRANSCRIBE_TASK_ERROR',
		AI_OVERVIEW_TASK_ERROR = 'AI_OVERVIEW_TASK_ERROR'
	;

	/**
	 * Checks if error fired by ai module.
	 * @see \Bitrix\AI\Engine\Engine::onResponseError
	 */
	public function isAiGeneratedError(): bool
	{
		// Errors comes from AI module are started with prefix AI_ENGINE_ERROR_
		return
			str_starts_with($this->getCode(), 'AI_ENGINE_ERROR')
			|| str_starts_with($this->getCode(), 'LIMIT_IS_EXCEEDED');
	}
}
