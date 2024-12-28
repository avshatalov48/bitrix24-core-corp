<?php

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Application;

final class BitrixAudio extends CloudEngine implements IQueueOptional
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['audio'];
	protected const ENGINE_NAME = 'BitrixAudio';
	public const ENGINE_CODE = 'BitrixAudio';
	protected const URL_COMPLETIONS = 'https://b24ai.bitrix.info/v1/audio/transcriptions';

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return 'default-v1';
	}

	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return $region === 'ru' || $region === 'by';
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
		$prettyResult = $rawResult['text'] ?? null;

		return new Result($rawResult, $prettyResult, $cached);
	}

	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	public function hasQuality(Quality $quality): bool
	{
		return true;
	}

	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		$prefer = [
			Quality::QUALITIES['transcribe'],
		];

		if ($quality === null)
		{
			// no quality specified, so we are preferred by default
			return true;
		}

		return !empty(array_intersect($quality->getRequired(), $prefer));
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
