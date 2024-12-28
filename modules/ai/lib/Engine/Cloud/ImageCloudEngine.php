<?php declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud;

use Bitrix\AI\Engine;
use Bitrix\AI\Facade\File;
use Bitrix\Main\Localization\Loc;

abstract class ImageCloudEngine extends CloudEngine
{
	/**
	 * The category code for the image engine.
	 */
	protected const CATEGORY_CODE = Engine::CATEGORIES['image'];

	/**
	 * The default width for images.
	 */
	protected const WIDTH = 1024;

	/**
	 * The default height for images.
	 */
	protected const HEIGHT = 1024;

	protected const DEFAULT_FORMAT = 'square';

	/**
	 * The default number of images.
	 */
	protected const IMAGES_NUM = 1;

	/**
	 * @param string|null $format
	 * @return int[]
	 */
	protected function getImageWidthAndHeightByFormat(?string $format = null): array
	{
		$widthAndHeightByFormat = $this->getImageFormats();

		return $widthAndHeightByFormat[$format] ?? $widthAndHeightByFormat[self::DEFAULT_FORMAT];
	}

	/**
	 * Save base64 coded image to file and return src.
	 *
	 * @param string $imageBase64
	 *
	 * @return string|null
	 */
	protected function getImageSrcFromBase64String(string $imageBase64): ?string
	{
		$fileId = File::saveImageByBase64Content($imageBase64, 'ai');
		if ($fileId && ($fileArray = \CFile::GetFileArray($fileId)) && !empty($fileArray['SRC']))
		{
			return $fileArray['SRC'];
		}

		return null;
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
				'name' => Loc::getMessage('AI_IMAGE_CLOUD_ENGINE_FORMAT_SQUARE') ?? 'square (1:1)',
				'width' => 1024,
				'height' => 1024,
			],
			'portrait' => [
				'code' => 'portrait',
				'name' => Loc::getMessage('AI_IMAGE_CLOUD_ENGINE_FORMAT_PORTRAIT') ?? 'portrait (9:16)',
				'width' => 1024,
				'height' => 1792,
			],
			'landscape' => [
				'code' => 'landscape',
				'name' => Loc::getMessage('AI_IMAGE_CLOUD_ENGINE_FORMAT_LANDSCAPE') ?? 'landscape (16:9)',
				'width' => 1792,
				'height' => 1024,
			],
		];
	}

}