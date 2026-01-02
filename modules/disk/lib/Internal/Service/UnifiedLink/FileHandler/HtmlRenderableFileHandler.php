<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler;

interface HtmlRenderableFileHandler
{
	public function view(): FileHandlerOperationResult;

	public function edit(): FileHandlerOperationResult;
}