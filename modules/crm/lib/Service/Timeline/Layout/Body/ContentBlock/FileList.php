<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model\File;

class FileList extends ContentBlock
{
	/**
	 * @var string|null
	 */
	protected ?string $title = null;

	/**
	 * @var File[]|null
	 */
	protected ?array $files = null;

	/**
	 * @var array|null
	 */
	protected ?array $updateParams = null;

	public function getRendererName(): string
	{
		return 'FileList';
	}

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	public function getNumberOfFiles(): int
	{
		return $this->files ? count($this->files) : 0;
	}

	public function getUpdateParams(): ?array
	{
		return $this->updateParams;
	}

	public function setUpdateParams(?array $params): self
	{
		$this->updateParams = $params;

		return $this;
	}

	/**
	 * @return File[]|null
	 */
	public function getFiles(): ?array
	{
		return $this->files;
	}

	/**
	 * @param File[]|null $files
	 *
	 * @return $this
	 */
	public function setFiles(?array $files): self
	{
		$this->files = $files;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'title' => $this->getTitle(),
			'numberOfFiles' => $this->getNumberOfFiles(),
			'files' => $this->getFiles(),
			'updateParams' => $this->getUpdateParams(),
		];
	}
}
