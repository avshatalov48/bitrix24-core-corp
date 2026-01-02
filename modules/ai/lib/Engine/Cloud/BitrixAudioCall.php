<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Result;
use Bitrix\Main\Web\Json;

class BitrixAudioCall extends BitrixAudio
{
	protected const CATEGORY_CODE = Engine::CATEGORIES['call'];
	protected const ENGINE_NAME = 'BitrixAudioCall';
	public const ENGINE_CODE = 'BitrixAudioCall';

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$jsonData = is_array($rawResult) ? $rawResult : null;

		return new Result($rawResult, Json::encode($rawResult), $cached, $jsonData);
	}

	protected function getCompletionsUrl(): string
	{
		return $this->getAiUrlManager()->getAudioCallCompletionsUrl();
	}
}