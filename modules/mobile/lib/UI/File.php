<?php

namespace Bitrix\Mobile\UI;

use Bitrix\Main\Web\MimeType;

final class File implements \JsonSerializable
{
	private $id;
	private $name;
	private $type;
	private $url;
	private $height;
	private $width;

	/** @var ?self */
	private $preview;

	private function __construct(
		?int $id = null,
		?string $name = null,
		?string $type = null,
		?string $url = null,
		?int $width = null,
		?int $height = null
	)
	{
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->url = $url;
		$this->width = $width;
		$this->height = $height;
	}

	public static function load(int $fileId): ?self
	{
		$fileInfo = \CFile::GetFileArray($fileId);
		if (!$fileInfo)
		{
			return null;
		}

		$fileName = $fileInfo['ORIGINAL_NAME'] ?: $fileInfo['FILE_NAME'];

		return new self(
			$fileId,
			$fileName,
			MimeType::getByFilename($fileName),
			$fileInfo['SRC'],
			(int)$fileInfo['WIDTH'],
			(int)$fileInfo['HEIGHT'],
		);
	}

	public static function loadWithPreview(int $fileId, array $previewOptions = []): ?self
	{
		$file = self::load($fileId);
		if (!$file)
		{
			return null;
		}

		$width = (int)($previewOptions['width'] ?? 0) ?: 120;
		$height = (int)($previewOptions['height'] ?? 0) ?: 120;
		$file->preview = $file->getPreview($width, $height);

		return $file;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function getType(): ?string
	{
		return $this->type;
	}

	public function getUrl(): ?string
	{
		return $this->url;
	}

	public function getHeight(): ?int
	{
		return $this->height;
	}

	public function getWidth(): ?int
	{
		return $this->width;
	}

	public function getPreview(int $width, int $height): ?self
	{
		if (!\CFile::IsImage($this->getUrl()))
		{
			return null;
		}

		if (
			$this->preview
			&& $this->preview->getWidth() === $width
			&& $this->preview->getHeight() === $height
		)
		{
			return $this->preview;
		}

		$resizedImage = \CFile::ResizeImageGet(
			$this->getId(),
			[
				'width' => $width,
				'height' => $height,
			],
			BX_RESIZE_IMAGE_EXACT,
			true,
			false,
			true
		);
		if ($resizedImage)
		{
			$resizedInfo = \CFile::MakeFileArray($resizedImage['src']);
			$this->preview = new self(
				null,
				$resizedInfo['name'],
				$resizedInfo['type'],
				$resizedImage['src'],
				$resizedImage['width'],
				$resizedImage['height'],
			);
		}
		else
		{
			$this->preview = null;
		}

		return $this->preview;
	}

	public function getInfo(): array
	{
		$info = [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'type' => $this->getType(),
			'url' => $this->getUrl(),
			'height' => $this->getHeight(),
			'width' => $this->getWidth(),
		];

		if ($this->preview)
		{
			$info['previewUrl'] = $this->preview->getUrl();
			$info['previewHeight'] = $this->preview->getHeight();
			$info['previewWidth'] = $this->preview->getWidth();
		}

		return $info;
	}

	public function jsonSerialize()
	{
		return $this->getInfo();
	}
}