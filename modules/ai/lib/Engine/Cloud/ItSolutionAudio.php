<?php

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Application;

final class ItSolutionAudio extends CloudEngine implements IQueueOptional
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['audio'];
	protected const ENGINE_NAME = 'IT-Solution Audio';
	public const ENGINE_CODE = 'ItSolutionAudio';
	protected const URL_COMPLETIONS = 'https://it-solution.ru/llm/v1/transcriptions';

	/**
	 * @inheritDoc
	 */
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
		$region = Application::getInstance()->getLicense()->getRegion();

		return $region === 'ru' || $region === 'by';
	}

	/**
	 * @inheritDoc
	 */
	public function getPostParams(): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getParameters(): array
	{
		$payloadData = $this->getPayload()?->getData();

		return [
			'audioUrl' => $payloadData['file'] ?? null,
			'audioContentType' => $payloadData['fields']['type'] ?? null,
			'prompt' => $payloadData['fields']['prompt'] ?? null,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$prettyResult = $rawResult['text'] ?? null;

		return new Result($rawResult, $prettyResult, $cached);
	}

	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	/**
	 * @inheritDoc
	 */
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
