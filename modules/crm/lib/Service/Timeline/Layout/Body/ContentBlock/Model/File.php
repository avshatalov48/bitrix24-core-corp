<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model;

use Bitrix\Crm\Service\Timeline\Config;
use Bitrix\Crm\Service\Timeline\Layout\Base;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\Web\Json;

class File extends Base
{
	/** File identifier from disk module */
	private int $id;

	/** File identifier from b_file table */
	private int $sourceFileId;

	private string $name;
	private int $size;
	private string $viewUrl;
	private ?string $previewUrl;
	private array $attributes;
	private string $extension;

	public function __construct(int $id, int $sourceFileId, string $name, int $size, string $viewUrl, ?string $previewUrl = null)
	{
		$this->id = $id;
		$this->sourceFileId = $sourceFileId;
		$this->name = $name;
		$this->size = $size;
		$this->viewUrl = $viewUrl;
		$this->previewUrl = $previewUrl;
		$this->attributes = $this->fetchFileAttributes($sourceFileId, $name, $viewUrl);
		$this->extension = GetFileExtension(mb_strtolower($name));
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getSourceFileId(): int
	{
		return $this->sourceFileId;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function getViewUrl(): string
	{
		return $this->viewUrl;
	}

	public function getPreviewUrl(): ?string
	{
		return $this->previewUrl;
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function isAudio(): bool
	{
		return in_array($this->extension, Config::ALLOWED_AUDIO_EXTENSIONS, true);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'sourceFileId' => $this->getSourceFileId(),
			'name' => $this->getName(),
			'size' => $this->getSize(),
			'viewUrl' => $this->getViewUrl(),
			'previewUrl' => $this->getPreviewUrl(),
			'attributes' => $this->getAttributes(),
			'extension' => $this->getExtension(),
			'hasAudioPlayer' => $this->isAudio(),
		];
	}

	private function fetchFileAttributes(int $fileId, string $fileName, string $viewUrl): array
	{
		if ($fileId <= 0)
		{
			return [];
		}

		$itemAttributes = ItemAttributes::tryBuildByFileId($fileId, $viewUrl)
			->setTitle($fileName)
			->addAction(['type' => 'download'])
		;

		$result = [];
		foreach ($itemAttributes->getAttributes() as $key => $value)
		{
			$result[$key] = $value ?? '';
		}

		if ($itemAttributes->getActions())
		{
			$result['data-actions'] = Json::encode($itemAttributes->getActions());
		}

		unset($itemAttributes);

		return $result;
	}
}
