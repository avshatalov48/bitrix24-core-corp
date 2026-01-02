<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Application;

final class Whisper extends CloudEngine implements IQueueOptional
{
	use Engine\Trait\AudioCommonTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['audio'];
	protected const ENGINE_NAME = 'Whisper';
	public const ENGINE_CODE = 'Audio';
	protected const DEFAULT_MODEL = 'large-v3';
	protected const URL_COMPLETIONS = 'https://api.openai.com/v1/audio/transcriptions';

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	protected function getDefaultModel(): string
	{
		return self::DEFAULT_MODEL;
	}

	/**
	 * Check if engine is available for current region.
	 * @return bool
	 */
	public function isAvailable(): bool
	{
		$region = Application::getInstance()->getLicense()->getRegion();

		return !in_array($region, ['ru', 'by', 'cn']);
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$prettyResult = $rawResult['text'] ?? null;

		return new Result($rawResult, $prettyResult, $cached);
	}
}
