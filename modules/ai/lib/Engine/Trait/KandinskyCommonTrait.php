<?php
namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;

trait KandinskyCommonTrait
{
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
	public function isAvailable(): bool
	{
		if (Option::get('ai', 'ai_engine_kandinsky_enable') !== 'Y')
		{
			return false;
		}

		if (Bitrix24::shouldUseB24())
		{
			$zone = Bitrix24::getPortalZone();

			return $zone === 'ru' || $zone === 'by';
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
		$sizes = $this->adjustSizeForAspectRatio($format['widthRatio'], $format['heightRatio']);

		return [
			'model_id' => self::MODEL_ID,
			'messages' => [],
			'params' => [
				'type' => 'GENERATE',
				'numImages' => 1,
				'width' => $sizes['width'],
				'height' => $sizes['height'],
				'generateParams' => [
					'query' => ''
				]
			]
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
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_KA_FORMAT_SQUARE'),
				'widthRatio' => 1,
				'heightRatio' => 1,
			],
			'portrait' => [
				'code' => 'portrait',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_KA_FORMAT_PORTRAIT'),
				'widthRatio' => 9,
				'heightRatio' => 16,
			],
			'landscape' => [
				'code' => 'landscape',
				'name' => Loc::getMessage('AI_IMAGE_ENGINE_KA_FORMAT_LANDSCAPE'),
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
		$sizes = $this->adjustSizeForAspectRatio($format['widthRatio'], $format['heightRatio']);
		$query = empty($payloadData['style']) ? $payloadData['prompt'] : "{$payloadData['style']},{$payloadData['prompt']}";

		return [
			'model_id' => self::MODEL_ID,
			'messages' => [],
			'params' => Json::encode([
				 'type' => 'GENERATE',
				 'numImages' => 1,
				 'width' => $sizes['width'],
				 'height' => $sizes['height'],
				 'generateParams' => [
					 'query' => $query
				 ]
			 ])
		];
	}

	/**
	 * Return array contain width and height.
	 *
	 * @return array{width: float|int, height: float|int}
	 */
	private function adjustSizeForAspectRatio(int $aspectRatioWidth, int $aspectRatioHeight): array
	{
		$size = ['width' => self::MAX_WIDTH, 'height' => self::MAX_HEIGHT];
		if ($aspectRatioWidth > $aspectRatioHeight)
		{
			$size['height'] = floor(($size['height'] / $aspectRatioWidth) * $aspectRatioHeight);
		}
		else
		{
			$size['width'] = floor(($size['width'] / $aspectRatioHeight) * $aspectRatioWidth);
		}

		return $size;
	}

	protected function preparePostParams(array $additionalParams = []): array
	{
		$postParams = $this->getPostParams();

		return Encoding::convertEncoding($postParams, SITE_CHARSET, 'UTF-8');
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$image = null;
		$imageBase64 = $rawResult['images'][0] ?? null;
		if ($imageBase64)
		{
			$imageSrc = $this->getImageSrcFromBase64String($rawResult['images'][0]);
			$image = $imageSrc ? [$imageSrc] : null;
		}

		return new Result(
			$image,
			is_array($image) ? Json::encode($image) : $image,
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