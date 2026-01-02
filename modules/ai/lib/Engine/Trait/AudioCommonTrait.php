<?php
namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Quality;

trait AudioCommonTrait
{
	public function getParameters(): array
	{
		$payloadData = $this->getPayload()?->getData();

		return [
			'audioUrl' => $payloadData['file'] ?? null,
			'audioContentType' => $payloadData['fields']['type'] ?? null,
			'prompt' => $payloadData['fields']['prompt'] ?? null,
		];
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

	/**
	 * @inheritDoc
	 */
	public function getPostParams(): array
	{
		return [];
	}

	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	public function hasQuality(Quality $quality): bool
	{
		return true;
	}
}