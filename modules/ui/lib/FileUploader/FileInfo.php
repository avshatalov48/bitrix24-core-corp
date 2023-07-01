<?php

namespace Bitrix\UI\FileUploader;

use Bitrix\Main\Type\Dictionary;

class FileInfo implements \JsonSerializable
{
	protected $id;
	protected string $contentType;
	protected string $name;
	protected int $size;
	protected int $fileId = 0;
	protected int $width = 0;
	protected int $height = 0;
	protected ?string $downloadUrl = null;
	protected ?string $previewUrl = null;
	protected int $previewWidth = 0;
	protected int $previewHeight = 0;
	protected ?Dictionary $customData = null;

	/**
	 * @param {string | int} $id
	 * @param string $name
	 * @param string $contentType
	 * @param int $size
	 */
	public function __construct($id, string $name, string $contentType, int $size)
	{
		$this->id = $id;
		$this->contentType = $contentType;
		$this->name = $name;
		$this->size = $size;
	}

	public static function createFromBFile(int $id): ?FileInfo
	{
		$file = \CFile::getFileArray($id);
		if (!is_array($file))
		{
			return null;
		}

		$fileName = !empty($file['ORIGINAL_NAME']) ? $file['ORIGINAL_NAME'] : $file['FILE_NAME'];
		$fileInfo = new static($id, $fileName, $file['CONTENT_TYPE'], (int)$file['FILE_SIZE']);
		$fileInfo->setWidth($file['WIDTH']);
		$fileInfo->setHeight($file['HEIGHT']);
		$fileInfo->setFileId($id);

		return $fileInfo;
	}

	public static function createFromTempFile(string $tempFileId): ?FileInfo
	{
		[$guid, $signature] = explode('.', $tempFileId);

		$tempFile = TempFileTable::getList([
			'filter' => [
				'=GUID' => $guid,
				'=UPLOADED' => true,
			],
		])->fetchObject();

		if (!$tempFile)
		{
			return null;
		}

		$fileInfo = new static($tempFileId, $tempFile->getFilename(), $tempFile->getMimetype(), $tempFile->getSize());
		$fileInfo->setWidth($tempFile->getWidth());
		$fileInfo->setHeight($tempFile->getHeight());
		$fileInfo->setFileId($tempFile->getFileId());

		return $fileInfo;
	}

	/**
	 * @return int|string
	 */
	public function getId()
	{
		return $this->id;
	}

	public function setId($id): void
	{
		if (is_int($id) || is_string($id))
		{
			$this->id = $id;
		}
	}

	public function getFileId(): int
	{
		return $this->fileId;
	}

	public function setFileId(int $fileId): void
	{
		$this->fileId = $fileId;
	}

	public function getContentType(): string
	{
		return $this->contentType;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function getWidth(): int
	{
		return $this->width;
	}

	public function setWidth(int $width): void
	{
		$this->width = $width;
	}

	public function getHeight(): int
	{
		return $this->height;
	}

	public function setHeight(int $height): void
	{
		$this->height = $height;
	}

	public function isImage(): bool
	{
		return \CFile::isImage($this->getName()) && $this->getWidth() > 0 && $this->getHeight() > 0;
	}

	public function getDownloadUrl(): ?string
	{
		return $this->downloadUrl;
	}

	public function setDownloadUrl(string $downloadUrl): void
	{
		$this->downloadUrl = $downloadUrl;
	}

	public function getPreviewUrl(): ?string
	{
		return $this->previewUrl;
	}

	public function setPreviewUrl(string $previewUrl, int $previewWidth, int $previewHeight): void
	{
		$this->previewUrl = $previewUrl;
		$this->previewWidth = $previewWidth;
		$this->previewHeight = $previewHeight;
	}

	public function getPreviewWidth(): int
	{
		return $this->previewWidth;
	}

	public function getPreviewHeight(): int
	{
		return $this->previewHeight;
	}

	public function setCustomData(array $customData): self
	{
		$this->getCustomData()->setValues($customData);

		return $this;
	}

	/**
	 * @return Dictionary
	 */
	public function getCustomData(): Dictionary
	{
		if ($this->customData === null)
		{
			$this->customData = new Dictionary();
		}

		return $this->customData;
	}

	public function jsonSerialize(): array
	{
		return [
			'serverFileId' => $this->getId(),
			'serverId' => $this->getId(), // compatibility
			'type' => $this->getContentType(),
			'name' => $this->getName(),
			'size' => $this->getSize(),
			'width' => $this->getWidth(),
			'height' => $this->getHeight(),
			'downloadUrl' => $this->getDownloadUrl(),
			'serverPreviewUrl' => $this->getPreviewUrl(),
			'serverPreviewWidth' => $this->getPreviewWidth(),
			'serverPreviewHeight' => $this->getPreviewHeight(),
			'customData' => $this->customData !== null ? $this->getCustomData()->getValues() : [],
		];
	}
}
