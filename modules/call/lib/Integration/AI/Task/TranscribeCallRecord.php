<?php

namespace Bitrix\Call\Integration\AI\Task;

use Bitrix\Main\Result;
use Bitrix\Call\Track;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\MentionService;
use Bitrix\Call\Integration\AI\CallAISettings;

class TranscribeCallRecord extends AITask
{
	public const SETTINGS_ENGINE_CODE = 'transcribe_track';

	protected static string
		$promptFields =<<<JSON
			{
				"transcriptions": [
					{
						"start_time_formatted": "string",
						"end_time_formatted": "string",
						"user_id": "int",
						"text": "string"
					}
				]
			}
			JSON
	;

	/**
	 * Provides payload for AI task.
	 * @param Track $payload
	 * @return self
	 */
	public function setPayload($payload): self
	{
		if ($payload instanceof Track)
		{
			$this->task
				->setType($this->getAISenseType())
				->setCallId($payload->getCallId())
				->setTrack($payload)
				->setTrackId($payload->getId())
			;
		}

		return $this;
	}

	/**
	 * @return Result<\Bitrix\AI\Payload\IPayload: payload>
	 */
	public function getAIPayload(): Result
	{
		$result = new Result;

		$track = $this->task->fillTrack();
		if (!$track)
		{
			return $result->addError(new Track\TrackError(Track\TrackError::TRACK_NOT_FOUND_ERROR));
		}

		if (
			$track->hasDownloadUrl()
			&& !empty($track->getDownloadUrl())
		)
		{
			$url = $track->getDownloadUrl();
		}
		else
		{
			$url = $track->getUrl();
		}

		$payload = new \Bitrix\AI\Payload\Audio($url);
		$payload->setMarkers(['type' => $track->getFileMimeType()]);

		return $result->setData(['payload' => $payload]);
	}

	public function getAIEngineCategory(): string
	{
		return \Bitrix\AI\Engine\Enum\Category::CALL->value;
	}

	public function getAISenseType(): string
	{
		return SenseType::TRANSCRIBE->value;
	}

	public function getCost(): int
	{
		return 7;
	}

	public function getAIEngineCode(): string
	{
		$engineItem = (new \Bitrix\AI\Tuning\Manager)->getItem(CallAISettings::TRANSCRIBE_CALL_RECORD_ENGINE);

		return isset($engineItem) ? $engineItem->getValue() : 'AudioCall'; /** @see \Bitrix\Bitrix24\Integration\AI\Engine\AudioCall::ENGINE_CODE */
	}

	public function drop(): Result
	{
		$track = $this->task->fillTrack();
		if ($track)
		{
			$track->drop();
		}

		return parent::drop();
	}

	public function filterResult(array $jsonData): array
	{
		$mentionService = MentionService::getInstance();
		$mentionService->loadMentionsForCall($this->getCallId());

		foreach ($jsonData['transcriptions'] as &$row)
		{
			$row['text'] = $mentionService->replaceAiMentions($row['text']);
		}

		return $jsonData;
	}
}