<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\SupportPolicy;

use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Service\Document\DocumentViewPolicy;
use Bitrix\Disk\Internal\Service\UnifiedLink\Configuration;

class DocumentSupportPolicy extends BaseSupportPolicy
{
	protected function passesSpecializedChecks(File $file): bool
	{
		$contentType = $file->getFile()['CONTENT_TYPE'] ?? '';

		if ($contentType === 'text/plain' || $file->getExtension() === 'txt')
		{
			return false;
		}

		$documentHandler = DocumentViewPolicy::getDefaultHandlerForView();

		return DocumentViewPolicy::isAllowedUseClouds($contentType)
			&& $this->supportsDocumentHandler($documentHandler);
	}

	private function supportsDocumentHandler(DocumentHandler $documentHandler): bool
	{
		return Configuration::isDocumentHandlerAllowed($documentHandler);
	}
}
