<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model;

use Bitrix\Crm\Service\Timeline\Layout\Base;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\Web\Json;

class File extends Base
{
	private int $id;
	private string $name;
	private int $size;
	private string $viewUrl;
	private array $attributes;

	public function __construct(int $id, string $name, int $size, string $viewUrl)
	{
		$this->id = $id;
		$this->name = $name;
		$this->size = $size;
		$this->viewUrl = $viewUrl;
		$this->attributes = $this->fetchFileAttributes($id, $name, $viewUrl);
	}

	public function getId(): int
	{
		return $this->id;
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

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'size' => $this->getSize(),
			'viewUrl' => $this->getViewUrl(),
			'attributes' => $this->getAttributes(),
		];
	}

	private function fetchFileAttributes(int $fileId, string $fileName, string $viewUrl): array
	{
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
