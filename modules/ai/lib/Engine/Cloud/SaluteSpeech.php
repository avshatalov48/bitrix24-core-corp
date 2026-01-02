<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Engine\Trait\SaluteSpeechTrait;
use Bitrix\AI\Engine;
use Bitrix\AI\Quality;

class SaluteSpeech extends CloudEngine implements IQueueOptional
{
	use SaluteSpeechTrait;

	protected const CATEGORY_CODE = Engine::CATEGORIES['audio'];
	protected const ENGINE_NAME = 'SaluteSpeech';
	protected const ENGINE_CODE = 'SaluteSpeech';

	protected const URL_COMPLETIONS_QUEUE_PATH = '/api/v1/sber/audio/transcriptions/salute-speech';

	public function getName(): string
	{
		return $this->getModel();
	}

	protected function getDefaultModel(): string
	{
		return static::ENGINE_NAME;
	}

	protected function makeRequestParams(array $postParams = []): array
	{
		return $this->getQueryParams();
	}
}
