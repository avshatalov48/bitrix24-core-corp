<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\Document;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\GoogleViewerHandler;
use Bitrix\Disk\Driver;
use Bitrix\Main\Web\MimeType;

class DocumentViewPolicy
{
	/** @var array<string, bool> */
	private static array $allowedUseCloudsContentTypes = [];

	public static function isAllowedUseClouds(string $contentType): bool
	{
		if (!isset(self::$allowedUseCloudsContentTypes[$contentType]))
		{
			if (!Configuration::canCreateFileByCloud())
			{
				self::$allowedUseCloudsContentTypes[$contentType] = false;

				return false;
			}

			$handler = self::getDefaultHandlerForView();

			if ($handler instanceof GoogleViewerHandler && !Configuration::isEnabledAutoExternalLink())
			{
				self::$allowedUseCloudsContentTypes[$contentType] = false;

				return false;
			}

			self::$allowedUseCloudsContentTypes[$contentType] = in_array($contentType, self::getInputContentTypes(), true);
		}

		return self::$allowedUseCloudsContentTypes[$contentType];
	}

	private static function getInputContentTypes(): array
	{
		$types = [
			MimeType::getByFileExtension('pdf'),
			'application/rtf',
			'application/vnd.ms-powerpoint',
		];

		$handler = self::getDefaultHandlerForView();
		$editableExtensions = $handler::listEditableExtensions();

		foreach ($editableExtensions as $extension)
		{
			$type = MimeType::getByFileExtension($extension);
			if ($type === 'application/octet-stream')
			{
				continue;
			}
			$types[] = $type;
		}

		return $types;
	}

	public static function getDefaultHandlerForView(): DocumentHandler
	{
		return Driver::getInstance()
			->getDocumentHandlersManager()
			->getDefaultHandlerForView()
		;
	}
}
