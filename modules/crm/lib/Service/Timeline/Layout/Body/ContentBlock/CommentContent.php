<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Action;

final class CommentContent extends EditableDescription
{
	/**
	 * @var int|null
	 */
	protected ?int $filesCount = null;

	/**
	 * @var bool|null
	 */
	protected ?bool $hasInlineFiles = null;

	/**
	 * @var Action|null
	 */
	private ?Action $loadAction = null;

	public function getRendererName(): string
	{
		return 'CommentContent';
	}

	public function getFilesCount(): ?int
	{
		return $this->filesCount;
	}

	public function setFilesCount(?int $filesCount): self
	{
		$this->filesCount = $filesCount;

		return $this;
	}

	public function getHasInlineFiles(): ?bool
	{
		return $this->hasInlineFiles;
	}

	public function setHasInlineFiles(?bool $hasInlineFiles): self
	{
		$this->hasInlineFiles = $hasInlineFiles;

		return $this;
	}

	public function getLoadAction(): ?Action
	{
		return $this->loadAction;
	}

	public function setLoadAction(?Action $action): self
	{
		$this->loadAction = $action;

		return $this;
	}

	protected function getProperties(): array
	{
		return array_merge(
			parent::getProperties(),
			[
				'filesCount' => $this->getFilesCount(),
				'hasInlineFiles' => $this->getHasInlineFiles(),
				'loadAction' => $this->getLoadAction(),
			]
		);
	}
}
