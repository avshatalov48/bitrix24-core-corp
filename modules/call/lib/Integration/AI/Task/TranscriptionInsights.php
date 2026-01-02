<?php

namespace Bitrix\Call\Integration\AI\Task;

use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Call\Library;
use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\CallAIError;
use Bitrix\Call\Integration\AI\MentionService;
use Bitrix\Call\Integration\AI\CallAISettings;
use Bitrix\Call\Integration\AI\Outcome\Transcription;

class TranscriptionInsights extends AITask
{
	public const
		PROMPT_ID = 'meeting_insights_reasoning_eu',
		PROMPT_ID_CIS = 'meeting_insights_reasoning_cis'
	;

	protected static string
		$promptFields =<<<JSON
			{
				"insights": [
					{
						"speaker": "string or null",
						"detailed_insight": "string or null"
					}
				],
				"meeting_strengths": [
					{
						"strength_title": "string",
						"strength_explanation": "string"
					}
				],
				"meeting_weaknesses": [
					{
						"weakness_title": "string",
						"weakness_explanation": "string"
					}
				],
				"speech_style_influence": "string or null",
				"engagement_level": "string or null",
				"areas_of_responsibility": "string or null",
				"final_recommendations": "string"
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

		$speakersList = $this->buildSpeakersList($transcription, $callId);

		$payload = new \Bitrix\AI\Payload\Prompt($this->getPromptCode());
		$markers = [
			'transcripts' => $content,
			'json_call' => static::getAIPromptFields()
		];

		if (!empty($speakersList))
		{
			$markers['speakers_list'] = $speakersList;
		}

		$payload->setMarkers($markers);

		return $result->setData(['payload' => $payload]);
	}

	/**
	 * Builds speakers list for participants who spoke more than 30 seconds
	 * @param \Bitrix\Call\Integration\AI\Outcome\Transcription $transcription
	 * @param int $callId
	 * @return string
	 */
	protected function buildSpeakersList(Transcription $transcription, int $callId): string
	{
		/** @var MentionService $mentionService */
		$mentionService = MentionService::getInstance();
		$speakerTimes = [];

		foreach ($transcription->transcriptions as $row)
		{
			$startSeconds = $this->timeToSeconds($row->start);
			$endSeconds = $this->timeToSeconds($row->end);
			$duration = $endSeconds - $startSeconds;

			if (!isset($speakerTimes[$row->userId]))
			{
				$speakerTimes[$row->userId] = [
					'totalTime' => 0,
					'userName' => $mentionService->getAIMention($row->userId, $callId)
				];
			}

			$speakerTimes[$row->userId]['totalTime'] += $duration;
		}

		$activeSpeakers = [];
		foreach ($speakerTimes as $data)
		{
			if ($data['totalTime'] > 30)
			{
				$activeSpeakers[] = $data['userName'];
			}
		}

		return implode(",\n", $activeSpeakers);
	}

	/**
	 * Converts time format MM:SS to seconds
	 * @param string $time
	 * @return int
	 */
	protected function timeToSeconds(string $time): int
	{
		$parts = explode(':', $time);
		if (count($parts) === 2)
		{
			return (int)$parts[0] * 60 + (int)$parts[1];
		}
		elseif (count($parts) === 3)
		{
			return (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
		}

		return 0;
	}

	protected function getPromptCode(): string
	{
		return \Bitrix\Main\Application::getInstance()->getLicense()->isCis()
			? self::PROMPT_ID_CIS
			: self::PROMPT_ID
		;
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
		($findFieldToConvert = static function (array $fields) use (&$findFieldToConvert, &$fieldsConvert)
		{
			foreach ($fields as $code => $field)
			{
				if (is_array($field))
				{
					$findFieldToConvert($field);
				}
				elseif (is_string($field) && str_contains($field, 'string'))
				{
					$fieldsConvert[$code] = true;
				}
			}
		})($fields);

		($convert = static function (array &$jsonData) use (&$convert, $fieldsConvert, $mentionService)
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

	/**
	 * Allows to output thw chat error message then task failed.
	 * @return bool
	 */
	public function allowNotifyTaskFailed(): bool
	{
		return true;
	}
}
