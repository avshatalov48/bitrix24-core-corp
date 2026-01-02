<?php
namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

trait YandexARTCommonTrait
{
	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		if (Bitrix24::shouldUseB24())
		{
			$region = Bitrix24::getPortalZone();
		}
		else
		{
			$region = Application::getInstance()->getLicense()->getRegion();
		}

		return ($region === 'ru' || $region === 'by');
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
}