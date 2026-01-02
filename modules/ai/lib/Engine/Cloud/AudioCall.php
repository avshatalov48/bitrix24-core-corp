<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\AiUrlManager;
use Bitrix\AI\Result;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Web\Json;

final class AudioCall extends CloudEngine implements IQueueOptional
{
	use Engine\Trait\AudioCommonTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['call'];
	protected const ENGINE_NAME = 'AudioCall';
	public const ENGINE_CODE = 'AudioCall';

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

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$jsonData = is_array($rawResult) ? $rawResult : null;

		return new Result($rawResult, Json::encode($rawResult), $cached, $jsonData);
	}

	protected function getCompletionsUrl(): string
	{
		return $this->getAiUrlManager()->getAudioCallCompletionsUrl();
	}

	protected function getAiUrlManager(): AiUrlManager
	{
		return ServiceLocator::getInstance()->get(AiUrlManager::class);
	}
}
