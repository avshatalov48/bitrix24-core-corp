<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics;

use Bitrix\Disk\Analytics\Context\ContextForCreateFile;
use Bitrix\Disk\Analytics\Context\ContextForUnattachedObject;
use Bitrix\Disk\Analytics\Context\ContextForUploadFileToAttach;
use Bitrix\Disk\Analytics\Context\ContextForUploadFileToIm;
use Bitrix\Disk\Analytics\Context\ElementStrategyInterface;
use Bitrix\Disk\Analytics\Context\SectionStrategyInterface;
use Bitrix\Disk\Analytics\Enum\DocumentHandlerType;
use Bitrix\Disk\Analytics\Enum\Event;
use Bitrix\Disk\Analytics\Enum\ImSection;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Folder;
use Bitrix\Disk\File;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\NotImplementedException;

class DiskAnalytics
{
	public const TOOL = 'files';
	public const CATEGORY = 'file_operations';

	public static function sendUploadFileDirectlyToDiskByIdEvent(int $fileId): void
	{
		$file = File::loadById($fileId);
		$context = new ContextForUnattachedObject($file);

		self::sendFileEvent($file, $context, Event::FileUploaded);
	}

	public static function sendUploadFileToAttachEvent(File $file, AttachedObject $attachedObject): void
	{
		$context = new ContextForUploadFileToAttach($attachedObject);

		self::sendFileEvent($file, $context, Event::FileUploaded);
	}

	public static function sendUploadFileToImEvent(File $file, ImSection $imSection): void
	{
		$context = new ContextForUploadFileToIm($imSection);

		self::sendFileEvent($file, $context, Event::FileUploaded);
	}

	public static function sendCreationFileThroughExternalServicesEvent(File $file, DocumentHandler $documentHandler): void
	{
		try
		{
			$service = $documentHandler::getCode();
		}
		catch (NotImplementedException)
		{
			$service = 'unknown_service';
		}

		$context = new ContextForCreateFile($file, $service);

		self::sendFileEvent($file, $context, Event::FileCreated);
	}

	public static function sendCreationFileEvent(File $file, DocumentHandlerType $documentHandlerType): void
	{
		$context = new ContextForCreateFile($file, $documentHandlerType->value);

		self::sendFileEvent($file, $context, Event::FileCreated);
	}

	public static function sendAddFolderEvent(Folder $folder): void
	{
		$context = new ContextForUnattachedObject($folder);

		$analyticsEvent = new AnalyticsEvent(Event::FolderAdded->value, self::TOOL, self::CATEGORY);
		$analyticsEvent
			->setSection($context->getSection())
			->setSubSection($context->getSubSection())
			->send()
		;
	}

	private static function sendFileEvent(File $file, SectionStrategyInterface|ElementStrategyInterface $context, Event $eventType): void
	{
		$fileEvent = new FileEvent($file, $context);
		$fileEvent
			->buildAnalyticsEvent($eventType)
			->send()
		;
	}
}