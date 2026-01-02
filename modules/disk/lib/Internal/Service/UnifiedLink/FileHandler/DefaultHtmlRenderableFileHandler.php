<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler;

use Bitrix\Disk\File;

class DefaultHtmlRenderableFileHandler implements HtmlRenderableFileHandler
{
	public function __construct(
		private readonly File $file
	)
	{
	}

	public function view(): FileHandlerOperationResult
	{
		return FileHandlerOperationResult::createSuccess("view file {$this->file->getId()}");
	}

	public function edit(): FileHandlerOperationResult
	{
		return FileHandlerOperationResult::createSuccess("edit file {$this->file->getId()}");
	}
}