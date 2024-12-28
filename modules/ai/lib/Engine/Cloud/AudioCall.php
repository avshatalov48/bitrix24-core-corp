<?php

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;

final class AudioCall extends CloudEngine implements IQueueOptional
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['call'];
	protected const ENGINE_NAME = 'AudioCall';
	public const ENGINE_CODE = 'AudioCall';
	protected const URL_COMPLETIONS = 'https://b24ai.bitrix.info/v1/call/transcriptions';

	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return 'default';
	}

	public function isAvailable(): bool
	{
		return Option::get('ai', 'audio_call_enabled', 'N') === 'Y';
	}

	public function getPostParams(): array
	{
		return [];
	}

	public function getParameters(): array
	{
		$payloadData = $this->getPayload()?->getData();

		return [
			'audioUrl' => $payloadData['file'] ?? null,
			'audioContentType' => $payloadData['fields']['type'] ?? null,
			'prompt' => $payloadData['fields']['prompt'] ?? null,
		];
	}

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$jsonData = null;
		if (\is_array($rawResult))
		{
			$jsonData = $rawResult;
		}

		return new Result($rawResult, Json::encode($rawResult), $cached, $jsonData);
	}

	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	public function hasQuality(Quality $quality): bool
	{
		return true;
	}

	protected function makeRequestParams(array $postParams = []): array
	{
		if (empty($postParams))
		{
			$postParams = $this->getPostParams();
			$postParams = array_merge($this->getParameters(), $postParams);
		}

		return [
			'audioUrl' => $postParams['audioUrl'] ?? '',
			'audioContentType' => $postParams['audioContentType'] ?? '',
			'prompt' => $postParams['prompt'] ?? '',
		];
	}
}
