<?php

namespace Bitrix\AI;

use Bitrix\Main\SystemException;

class Quality
{
	public const QUALITIES = [
		// audio to text
		'transcribe' => 'transcribe',
		// summarize a lot of text to compact
		'summarize' => 'summarize',
		// find crm (or other) fields in text and return its in json format
		'fields_highlight' => 'fields_highlight',
		// translate many languages
		'translate' => 'translate',
		// support response result in json format
		'json_response_mode' => 'json_response_mode',
		// support chatting
		'chat_talk' => 'chat_talk',
		// parse json with fields and give advice on it
		'give_advice' => 'give_advice',
		// support meeting processing in video and audio calls
		'meeting_processing' => 'meeting_processing',
	];

	private array|string $qualities;

	public function __construct(array|string $qualities)
	{
		$qualities = (array)$qualities;

		foreach ($qualities as $quality)
		{
			if (!is_string($quality) || !array_key_exists($quality, self::QUALITIES))
			{
				throw new SystemException('Incorrect or unknown quality');
			}
		}

		$this->qualities = $qualities;
	}

	public function getRequired(): array
	{
		return (array)$this->qualities;
	}
}