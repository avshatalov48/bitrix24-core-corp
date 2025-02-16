<?php

namespace Bitrix\Call\Integration\AI\Task;

use Bitrix\Im\V2\Chat;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Call\Integration\AI\Outcome;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\CallAIError;
use Bitrix\Call\Integration\AI\MentionService;
use Bitrix\Call\Integration\AI\CallAISettings;

class TranscriptionOverview extends AITask
{
	public const PROMPT_ID = 'meeting_overview';

	protected static string
		$promptFields =<<<JSON
			{
				"topic": "string or null",
				"agenda": {
					"is_mentioned": "bool",
					"explanation": "string or null",
					"quote": "string or null"
				},
				"meeting_details": {
					"type": "string or null",
					"is_exception_meeting": "bool",
					"explanation": "string or null"
				},
				"agreements": [
					{
						"agreement": "string or null",
						"quote": "string or null"
					}
				],
				"tasks": [
					{
						"task": "string or null",
						"quote": "string or null"
					}
				],
				"meetings": [
					{
						"meeting": "string or null",
						"quote": "string or null"
					}
				],
				"efficiency": 
				{
					"agenda_clearly_stated": {
						"value": "bool",
						"explanation": "string or null"
					},
					"agenda_items_covered": {
						"value": "bool",
						"explanation": "string or null"
					},
					"conclusions_and_actions_outlined": {
						"value": "bool",
						"explanation": "string or null"
					},
					"time_exceed_penalty": {
						"value": "bool",
						"explanation": "string or null"
					}
				}
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
		return SenseType::OVERVIEW->value;
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

	/**
	 * @param \Bitrix\AI\Result $aiResult
	 * @return Outcome
	 */
	public function buildOutcome(\Bitrix\AI\Result $aiResult): Outcome
	{
		$outcome = parent::buildOutcome($aiResult);
		[$calendar, $overhead, $duration] = $this->checkMeetingTimeOverhead($this->getCallId());
		if ($calendar)
		{
			$value = new \stdClass;
			$value->duration = $duration;
			$value->overhead = $overhead;
			$outcome->setProperty('calendar', $value);
		}

		return $outcome;
	}

	public static function checkMeetingTimeOverhead(int $callId): array
	{
		$call = \Bitrix\Im\Call\Registry::getCallWithId($callId);

		$calendar = false;
		$overhead = false;
		$duration = -1;
		if (
			($chatId = $call?->getChatId())
			&& ($chat = Chat::getInstance($chatId))
			&& $chat->getEntityType() == Chat\Type::Calendar->value
			&& Loader::includeModule('calendar')
			&& ($entryId = $chat->getEntityId())
			&& ($event = \Bitrix\Calendar\Internals\EventTable::getById($entryId)?->fetchObject())
		)
		{
			$calendar = true;
			$eventStart = $event->getDateFrom()->getTimestamp();
			$eventEnd = $event->getDateTo()->getTimestamp();
			$callStart = $call->getStartDate()->getTimestamp();
			$callEnd = $call->getEndDate() ?? new DateTime();
			if ($eventStart <= $callStart && $callStart <= $eventEnd)
			{
				$callDuration = $callEnd->getTimestamp() - $callStart;
				$duration = $eventEnd - $eventStart;
				$overhead = ($callDuration - $duration) > 60;
			}
		}

		return [$calendar, $overhead, $duration];
	}
}