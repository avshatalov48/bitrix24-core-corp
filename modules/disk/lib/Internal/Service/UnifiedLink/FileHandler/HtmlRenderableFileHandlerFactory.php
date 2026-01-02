<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\FileHandler;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\DocumentSource;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Version;

class HtmlRenderableFileHandlerFactory
{
	public function createHandler(File $file, ?AttachedObject $attachedObject = null, ?Version $version = null): HtmlRenderableFileHandler
	{
		$typeFile = (int)$file->getTypeFile();
		$documentSource = $this->getDocumentSource($file, $attachedObject, $version);

		return match ($typeFile)
		{
			TypeFile::DOCUMENT, TypeFile::PDF => $this->getDocumentHandler($file, $documentSource),
			TypeFile::FLIPCHART => new BoardHtmlRenderableFileHandler($file, $documentSource),
			default => new DefaultHtmlRenderableFileHandler($file),
		};
	}

	private function getDocumentHandler(File $file, DocumentSource $documentSource): HtmlRenderableFileHandler
	{
		$driver = Driver::getInstance();
		$handlersManager = $driver->getDocumentHandlersManager();
		$documentHandler = $handlersManager->getHandlerByCode('onlyoffice');

		if (!$documentHandler)
		{
			return new DefaultHtmlRenderableFileHandler($file);
		}

		return new OnlyOfficeHtmlRenderableFileHandler($file, $documentSource);
	}

	private function getDocumentSource(File $file, ?AttachedObject $attachedObject, ?Version $version = null): DocumentSource
	{
		if ($version !== null)
		{
			return DocumentSource::fromVersion($version);
		}

		if ($attachedObject !== null)
		{
			return DocumentSource::fromAttachedObject($attachedObject);
		}

		return DocumentSource::fromFile($file);
	}
}
