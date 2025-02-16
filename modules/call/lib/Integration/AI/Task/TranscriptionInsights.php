<?php

namespace Bitrix\Call\Integration\AI\Task;

use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\CallAIError;
use Bitrix\Call\Integration\AI\MentionService;
use Bitrix\Call\Integration\AI\CallAISettings;

class TranscriptionInsights extends AITask
{
	public const PROMPT_ID = 'meeting_insights';

	protected static string
		$promptFields =<<<JSON
			{
				"insights": [
					{
						"insight_type": "string or null",
						"detailed_insight": "string or null"
					}
				]
			}
		JSON
	;

	/**
	 * Provides payload for AI task.
	 * @param Outcome $payload
	 * @return self
	 */
	public function setPayload($payload): AITask
	{
		if ($payload instanceof Outcome)
		{
			$this->task
				->setType($this->getAISenseType())
				->setCallId($payload->getCallId())
				->setOutcome($payload)
				->setOutcomeId($payload->getId())
			;
		}

		return $this;
	}

	/**
	 * @return Result<\Bitrix\AI\Payload\IPayload>
	 */
	public function getAIPayload(): Result
	{
		$result = new Result;

		$outcome = $this->task->getOutcome() ?? $this->task->fillOutcome();
		if (!$outcome)
		{
			return $result->addError(new CallAIError(CallAIError::AI_EMPTY_PAYLOAD_ERROR));// Empty outcome content
		}

		$call = \Bitrix\Im\Call\Registry::getCallWithId($outcome->getCallId());
		if (!$call)
		{
			return $result->addError(new CallAIError(CallAIError::AI_EMPTY_PAYLOAD_ERROR));// Empty outcome content
		}

		/** @var \Bitrix\Call\Integration\AI\Outcome\Transcription $transcription */
		$transcription = $outcome->getSenseContent();
		if ($transcription->isEmpty)
		{
			return $result->addError(new CallAIError(CallAIError::AI_EMPTY_PAYLOAD_ERROR));// Empty outcome content
		}

		$mentionService = MentionService::getInstance();

		$callId = $outcome->getCallId();
		$content = '';
		foreach ($transcription->transcriptions as $row)
		{
			$userName = addslashes($mentionService->getAIMention($row->userId, $callId));
			$text = addslashes($row->text);
			// "00:00-00:45", "user", "phrase",
			$content .= sprintf('"%s-%s", "%s", "%s"', $row->start, $row->end, $userName, $text). "\n";
		}

		$payload = new \Bitrix\AI\Payload\Prompt(static::PROMPT_ID);
		$payload->setMarkers(['transcripts' => $content, 'json_call' => static::getAIPromptFields()]);

		return $result->setData(['payload' => $payload]);
	}

	public function getAIEngineCategory(): string
	{
		return \Bitrix\AI\Engine\Enum\Category::TEXT->value;
	}

	public function getAIEngineCode(): string
	{
		$engineItem = (new \Bitrix\AI\Tuning\Manager)->getItem(CallAISettings::TRANSCRIPTION_OVERVIEW_ENGINE);

		if (isset($engineItem))
		{
			$code = $engineItem->getValue();
		}
		elseif (\Bitrix\Call\Integration\AI\CallAISettings::isB24Mode())
		{
			$code = 'ChatGPT'; /** @see \Bitrix\Bitrix24\Integration\AI\Engine\ChatGPT::ENGINE_CODE */
		}
		else
		{
			$code = 'ItSolution'; /** @see \Bitrix\AI\Engine\Cloud\ItSolution::ENGINE_CODE */
		}

		return $code;
	}

	public function getAISenseType(): string
	{
		return SenseType::INSIGHTS->value;
	}

	public static function getAIPromptFields(): array
	{
		static $fields;
		if (empty($fields))
		{
			$fields = Json::decode(static::$promptFields);
		}
		return $fields;
	}

	public function filterResult(array $jsonData): array
	{
		$mentionService = MentionService::getInstance();
		$mentionService->loadMentionsForCall($this->getCallId());

		$fields = static::getAIPromptFields();
		$fieldsConvert = [];
		($findFieldToConvert = function(array $fields) use (&$findFieldToConvert, &$fieldsConvert)
		{
			foreach ($fields as $code => $field)
			{
				if (is_array($field))
				{
					$findFieldToConvert($field);
				}
				elseif (is_string($field) && $field == 'string or null')
				{
					$fieldsConvert[$code] = true;
				}
			}
		})($fields);

		($convert = function(array &$jsonData) use (&$convert, $fieldsConvert, $mentionService)
		{
			foreach ($jsonData as $code => &$field)
			{
				if (is_array($field))
				{
					$convert($field);
				}
				elseif (is_string($field) && isset($fieldsConvert[$code]))
				{
					$field = $mentionService->replaceAiMentions($field);
				}
			}
		})($jsonData);

		return $jsonData;
	}
}