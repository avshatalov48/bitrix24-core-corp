<?php

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Engine\IContext;
use Bitrix\AI\Engine\IQueueOptional;
use Bitrix\AI\Facade\File;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use CFile;

class YandexART extends ImageCloudEngine implements IContext, IQueueOptional
{
	protected const ENGINE_NAME = 'YandexART';
	public const ENGINE_CODE = 'YandexART';

	protected const URL_COMPLETIONS = 'https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync';
	protected const URL_COMPLETIONS_QUEUE_PATH = '/api/v1/image/generation';

	protected const MODEL = 'art://<folder>/yandex-art/latest';

	protected const DEFAULT_FORMAT = 'square';
	protected const DEFAULT_SEED = 2;// grain is any number from 0 to 2ˆ64

	protected const HTTP_STATUS_OK = 200;

	protected function getDefaultModel(): string
	{
		return 'yandex-art';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return self::ENGINE_NAME;
	}

	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		if (Option::get('ai', 'ai_engine_yandexart_enable') !== 'Y')
		{
			return false;
		}

		$region = Application::getInstance()->getLicense()->getRegion();

		return $region === 'ru' || $region === 'by';
	}

	/**
	 * @inheritDoc
	 */
	protected function getSystemParameters(): array
	{
		$format = $this->getImageWidthAndHeightByFormat(self::DEFAULT_FORMAT);

		return [
			'modelUri' => self::MODEL,
			'generationOptions' => [
				'seed' => self::DEFAULT_SEED,
				'aspectRatio' => [
					'widthRatio' => $format['widthRatio'],
					'heightRatio' => $format['heightRatio'],
				],
			],
			'messages' => [],
		];
	}

	/**
	 * Get the supported image formats.
	 *
	 * @return array The supported image formats with their respective dimensions.
	 */
	public function getImageFormats(): array
	{
		return [
			'square' => [
				'code' => 'square',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_YA_FORMAT_SQUARE'),
				'widthRatio' => 1,
				'heightRatio' => 1,
			],
			'portrait' => [
				'code' => 'portrait',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_YA_FORMAT_PORTRAIT'),
				'widthRatio' => 9,
				'heightRatio' => 16,
			],
			'landscape' => [
				'code' => 'landscape',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_YA_FORMAT_LANDSCAPE'),
				'widthRatio' => 16,
				'heightRatio' => 9,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getPostParams(): array
	{
		$payloadData = $this->getPayload()->getData();
		$format = $this->getImageWidthAndHeightByFormat($payloadData['format']);
		$stylePrompt = $payloadData['style'] ?? '';

		return [
			'generationOptions' => [
				'seed' => self::DEFAULT_SEED,
				'aspectRatio' => [
					'widthRatio' => $format['widthRatio'],
					'heightRatio' => $format['heightRatio'],
				],
			],
			'messages' => [
				[
					'weight' => 1,
					'text' => ($stylePrompt !== '') ? $stylePrompt . ',' . $payloadData['prompt'] : $payloadData['prompt'],
				],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$image = null;
		$imageBase64 = $rawResult['response']['image'] ?? null;
		if ($imageBase64)
		{
			$imageSrc = $this->getImageSrcFromBase64String($imageBase64);
			$image = $imageSrc ? [$imageSrc] : null;
		}

		return new Result(
			$image,
			is_array($image) ? json_encode($image) : $image,
			$cached
		);
	}

	protected function makeRequestParams(array $postParams = []): array
	{
		if (empty($postParams))
		{
			$postParams = $this->preparePostParams();
			$postParams = array_merge($this->getParameters(), $postParams);
		}

		return $postParams;
	}

}
