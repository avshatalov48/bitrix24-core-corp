<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Quality;
use Bitrix\AI\QueueJob;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

trait SaluteSpeechTrait
{
	public function getUrlCompletionsQueuePath(): string
	{
		if (empty(self::URL_COMPLETIONS_QUEUE_PATH))
		{
			return '';
		}

		return self::URL_COMPLETIONS_QUEUE_PATH;
	}

	public function getHttpStatusOk(): int
	{
		if (empty(self::HTTP_STATUS_OK))
		{
			return 200;
		}

		return self::HTTP_STATUS_OK;
	}

	public function isAvailable(): bool
	{
		return in_array(
			Application::getInstance()->getLicense()->getRegion(),
			['ru', 'by'],
			true
		);
	}

	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$prettyResult = $rawResult['text'] ?? null;

		return new Result($rawResult, $prettyResult, $cached);
	}

	public function hasQuality(Quality $quality): bool
	{
		$supportedQualities = [
			Quality::QUALITIES['transcribe'],
			Quality::QUALITIES['transcribe_chat_voice_messages'],
		];

		return !empty(array_intersect($supportedQualities, $quality->getRequired()));
	}

	protected function getQueryParams(): array
	{
		$payloadData = $this->getPayload()?->getData();

		return [
			'callbackUrl' => $this->getQueueJob()->getCallbackUrl(),
			'errorCallbackUrl' => $this->queueJob->getErrorCallbackUrl(),
			'audioUrl' => $payloadData['file'] ?? null,
			'audioContentType' => $payloadData['fields']['type'] ?? null,
			'fileExtension' => $payloadData['fileExtension'] ?? '',
			'authorization' => method_exists($this, 'getAuthorizationHeader')
				? $this->getAuthorizationHeader()
				: '',
		];
	}

	public function getQueueJob(): QueueJob
	{
		if (!$this->queueJob)
		{
			$this->queueJob = QueueJob::createWithinFromEngine($this);
		}

		return $this->queueJob;
	}
}
