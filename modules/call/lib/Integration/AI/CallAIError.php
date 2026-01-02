<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Call\Integration\AI\Task\AITask;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CallAIError extends \Bitrix\Call\Error
{
	public const
		AI_MODULE_ERROR = 'AI_MODULE_ERROR',
		AI_UNAVAILABLE_ERROR = 'AI_UNAVAILABLE_ERROR',
		AI_SETTINGS_ERROR = 'AI_SETTINGS_ERROR',
		AI_AGREEMENT_ERROR = 'AI_AGREEMENT_ERROR',
		AI_UNSUPPORTED_TRACK_ERROR = 'AI_UNSUPPORTED_TRACK_ERROR',
		AI_EMPTY_PAYLOAD_ERROR = 'AI_EMPTY_PAYLOAD_ERROR',
		AI_NOT_ENOUGH_BAAS_ERROR = 'AI_NOT_ENOUGH_BAAS_ERROR',
		AI_RECORD_TOO_SHORT = 'AI_RECORD_TOO_SHORT',
		AI_TASK_START_FAIL = 'AI_TASK_START_FAIL',
		AI_TASK_FAILED = 'AI_TASK_FAILED',
		AI_TRACKPACK_NOT_RECEIVED = 'AI_TRACKPACK_NOT_FOUND',
		AI_TRANSCRIBE_TASK_ERROR = 'AI_TRANSCRIBE_TASK_ERROR',
		AI_OVERVIEW_TASK_ERROR = 'AI_OVERVIEW_TASK_ERROR'
	;

	protected bool $recoverable = false;

	/**
	 * Checks if error fired by ai module.
	 * @see \Bitrix\AI\Engine\Engine::onResponseError
	 */
	public function isAiGeneratedError(): bool
	{
		return
			str_starts_with($this->getCode(), 'AI_ENGINE_ERROR')
			|| str_starts_with($this->getCode(), 'LIMIT_IS_EXCEEDED')
			|| str_starts_with($this->getCode(), 'CLOUD_REGISTRATION')
			|| $this->getCode() === 'RATE_LIMIT'
		;
	}

	/**
	 * @param string $errorCode
	 * @param \Bitrix\Main\Error $processingError
	 * @param AITask $processingTask
	 * @return static
	 */
	public static function constructTaskError(string $errorCode, $processingError, AITask $processingTask): self
	{
		$error = new self($errorCode);

		if ($processingError instanceof \Bitrix\Main\Error)
		{
			$error->code = $processingError->getCode();
			$error->message = static::htmlToBbCodeLink($processingError->getMessage());
		}

		if (
			$error->code !== 'HASH_EXPIRED'
			&& str_starts_with($error->code, 'AI_ENGINE_ERROR')
			&& ($errorRow = self::detectRowError($error->message, $processingTask))
		)
		{
			if (str_starts_with($errorRow, '[ERROR]'))
			{
				$error->description = $errorRow;
			}
			else
			{
				$error->message = $errorRow;
			}
		}

		if (
			$error->isAiGeneratedError()
			|| $error->code !== 'HASH_EXPIRED'
		)
		{
			$error->allowRecover();
		}

		return $error;
	}

	public function recoverable(): bool
	{
		return $this->recoverable;
	}

	public function allowRecover(): self
	{
		$this->recoverable = true;
		return $this;
	}

	public function disallowRecover(): self
	{
		$this->recoverable = false;
		return $this;
	}

	private static function detectRowError(string $againstError, AITask $task): string
	{
		if (\Bitrix\Main\Loader::includeModule('ai'))
		{
			$payloadResult = $task->getAIPayload();
			if ($payloadResult->isSuccess())
			{
				/**
				 * @var \Bitrix\AI\Payload\IPayload $payload
				 */
				$payload = $payloadResult->getData()['payload'];
				$context = $task->getAIEngineContext();
				$engine = $task->getAIEngine($context);

				$history = \Bitrix\AI\Model\HistoryTable::getList([
					'filter' => [
						'=CONTEXT_MODULE' => 'call',
						'=CONTEXT_ID' => $task->getContextId(),
						'=ENGINE_CODE' => $task->getAIEngineCode(),
						'=ENGINE_CLASS' => $engine::class,
						'=PAYLOAD_CLASS' => $payload::class,
						'>DATE_CREATE' => (new \Bitrix\Main\Type\DateTime())->add('-5sec'),
					],
					'order' => ['ID' => 'DESC'],
					'limit' => 1,
				]);
				if (
					($row = $history->fetch())
					&& !empty($row['RESULT_TEXT'])
					&& $row['RESULT_TEXT'] != $againstError
					&& !str_starts_with(ltrim($row['RESULT_TEXT']), '{')
				)
				{
					return static::htmlToBbCodeLink($row['RESULT_TEXT']);
				}
			}
		}

		return '';
	}
}
