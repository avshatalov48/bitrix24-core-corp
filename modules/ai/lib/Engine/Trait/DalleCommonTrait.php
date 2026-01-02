<?php
namespace Bitrix\AI\Engine\Trait;

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\File;
use Bitrix\AI\Quality;
use Bitrix\AI\Result;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\AI\Services\ImageService;

trait DalleCommonTrait
{
	/**
	 * @inheritDoc
	 */
	protected function getCompletionsUrl(): string
	{
		return self::URL_COMPLETIONS;
	}

	protected function getSize(int $width, int $height): string
	{
		return $width . 'x' . $height;
	}

	/**
	 * @param string|null $format
	 * @return int[]
	 */
	protected function getImageWidthAndHeightByFormat(?string $format = null): array
	{
		$widthAndHeightByFormat = $this->getImageFormats();

		return $widthAndHeightByFormat[$format] ?? $widthAndHeightByFormat['square'];
	}

	/**
	 * @inheritDoc
	 */
	protected function getSystemParameters(): array
	{
		return [
			'model' => $this->getModel(),
			'n' => self::IMAGES_NUM,
			'quality' => 'standard',// can be "standard" or "hd"
			'size' => self::WIDTH . 'x' . self::HEIGHT,
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
			'prompt' => ($stylePrompt !== '') ? $stylePrompt . ',' . $payloadData['prompt'] : $payloadData['prompt'],
			'size' => $format['width'] . 'x' . $format['height'],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getResultFromRaw(mixed $rawResult, bool $cached = false): Result
	{
		$image = null;
		$imageUri = $rawResult['data'][0]['url'] ?? null;

		if ($imageUri !== null)
		{
			$fileId = File::saveImageByURL($imageUri, 'ai');
			if ($fileId && ($fileArray = \CFile::GetFileArray($fileId)) && !empty($fileArray['EXTERNAL_ID']))
			{
				$image = [
					ServiceLocator::getInstance()->get(ImageService::class)
						->getUrlOnImgFile($fileId, $fileArray['EXTERNAL_ID'])
				];
			}
		}

		return new Result(
			$image,
			is_array($image) ? json_encode($image) : $image,
			$cached
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function makeRequestParams(array $postParams = []): array
	{
		if (empty($postParams))
		{
			$postParams = $this->getPostParams();
			$postParams = array_merge($this->getParameters(), $postParams);
		}

		return [
			'model' => $postParams['model'] ?? $this->getModel(),
			'n' => $postParams['n'] ?? self::IMAGES_NUM,
			'quality' => $postParams['quality'] ?? self::DEFAULT_QUALITY,
			'size' => $postParams['size'] ?? $this->getSize(self::WIDTH, self::HEIGHT),
			'prompt' => $postParams['prompt'] ?? $this->getPostParams()['prompt'],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function isPreferredForQuality(?Quality $quality = null): bool
	{
		$region = (Bitrix24::shouldUseB24())
			? Bitrix24::getPortalZone()
			: Application::getInstance()->getLicense()->getRegion()
		;

		return !in_array($region, ['ru', 'by'], true);
	}
}