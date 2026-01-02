<?php

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\AiUrlManager;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;

class BitrixAudio extends CloudEngine implements IQueueOptional
{
	use Engine\Trait\AudioCommonTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['audio'];
	protected const ENGINE_NAME = 'BitrixAudio';
	public const ENGINE_CODE = 'BitrixAudio';

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return static::ENGINE_NAME;
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

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$prettyResult = $rawResult['text'] ?? null;

		return new Result($rawResult, $prettyResult, $cached);
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

	public function hasQuality(Quality $quality): bool
	{
		$supportedQualities = [
			Quality::QUALITIES['transcribe'],
			Quality::QUALITIES['transcribe_chat_voice_messages'],
		];

		return !empty(array_intersect($supportedQualities, $quality->getRequired()));
	}

	protected function getCompletionsUrl(): string
	{
		return $this->getAiUrlManager()->getAudioCompletionsUrl();
	}

	protected function getAiUrlManager(): AiUrlManager
	{
		return ServiceLocator::getInstance()->get(AiUrlManager::class);
	}
}
