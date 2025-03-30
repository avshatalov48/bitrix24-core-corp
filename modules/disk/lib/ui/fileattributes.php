<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Document\GoogleViewerHandler;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\UI\Viewer\Renderer;
use Bitrix\Main\Web\MimeType;
use \Bitrix\Disk;

final class FileAttributes extends ItemAttributes
{
	public const ATTRIBUTE_OBJECT_ID = 'data-object-id';
	public const ATTRIBUTE_VERSION_ID = 'data-version-id';
	public const ATTRIBUTE_ATTACHED_OBJECT_ID = 'data-attached-object-id';
	public const ATTRIBUTE_SEPARATE_ITEM = 'data-viewer-separate-item';

	public const JS_TYPE = 'cloud-document';

	public const JS_TYPE_CLASS_CLOUD_DOCUMENT = 'BX.Disk.Viewer.DocumentItem';
	public const JS_TYPE_CLASS_ONLYOFFICE = 'BX.Disk.Viewer.OnlyOfficeItem';
	public const JS_TYPE_CLASS_BOARD = 'BX.Disk.Viewer.BoardItem';

	public static function tryBuildByFileId($fileId, $sourceUri)
	{
		try
		{
			return FileAttributes::buildByFileId($fileId, $sourceUri);
		}
		catch (ArgumentException $exception)
		{
			return FileAttributes::buildAsUnknownType($sourceUri);
		}
	}

	public function setVersionId($versionId)
	{
		$this->setAttribute(self::ATTRIBUTE_VERSION_ID, $versionId);

		return $this;
	}

	public function setObjectId($objectId)
	{
		$this->setAttribute(self::ATTRIBUTE_OBJECT_ID, $objectId);

		return $this;
	}

	public function setAttachedObjectId($attachedObjectId)
	{
		$this->setAttribute(self::ATTRIBUTE_ATTACHED_OBJECT_ID, $attachedObjectId);

		return $this;
	}

	public function setAsSeparateItem()
	{
		$this->setAttribute(self::ATTRIBUTE_SEPARATE_ITEM, true);

		return $this;
	}

	protected function setDefaultAttributes()
	{
		parent::setDefaultAttributes();

		if ($this->getViewerType() === Disk\UI\Viewer\Renderer\Board::getJsType())
		{
			$this
				->setAttribute('data-viewer-type-class', 'BX.Disk.Viewer.BoardItem')
				->setTypeClass(self::JS_TYPE_CLASS_BOARD)
				->setAsSeparateItem()
				->setExtension('disk.viewer.board-item')
			;

			Extension::load('disk.viewer.board-item');
		}

		if (self::isSetViewDocumentInClouds() && self::isAllowedUseClouds($this->fileData['CONTENT_TYPE']))
		{
			$documentHandler = self::getDefaultHandlerForView();
			if ($documentHandler instanceof OnlyOfficeHandler)
			{
				$this
					->setTypeClass(self::JS_TYPE_CLASS_ONLYOFFICE)
					->setAsSeparateItem()
					->setExtension('disk.viewer.onlyoffice-item')
				;

				Extension::load('disk.viewer.onlyoffice-item');
			}
			else
			{
				$this->setTypeClass(self::JS_TYPE_CLASS_CLOUD_DOCUMENT);
				$this->setExtension('disk.viewer.document-item');

				Extension::load('disk.viewer.document-item');
			}
		}
	}

	public function setGroupBy($id)
	{
		if (in_array($this->getTypeClass(), [self::JS_TYPE_CLASS_ONLYOFFICE, self::JS_TYPE_CLASS_BOARD]))
		{
			//temp fix: we have to disable view in group because onlyoffice uses SidePanel
			$this->unsetGroupBy();

			return $this;
		}

		return parent::setGroupBy($id);
	}

	protected static function getViewerTypeByFile(array $fileArray)
	{
		$type = parent::getViewerTypeByFile($fileArray);
		$type = self::refineType($type, $fileArray);

		if (!self::isSetViewDocumentInClouds())
		{
			return $type;
		}

		if ($type === Renderer\Pdf::getJsType() || self::isAllowedUseClouds($fileArray['CONTENT_TYPE']))
		{
			return self::JS_TYPE;
		}

		return $type;
	}

	/**
	 * @internal Should be deleted after main module will be updated.
	 * @return bool
	 */
	protected static function isFakeFileData(array $fileData): bool
	{
		return
			($fileData['ID'] === -1) && ($fileData['CONTENT_TYPE'] === 'application/octet-stream')
		;
	}

	protected static function refineType($type, $fileArray)
	{
		if (static::isFakeFileData($fileArray))
		{
			return $type;
		}

		if (
			$type === Renderer\Stub::getJsType() &&
			!empty($fileArray['ORIGINAL_NAME']) &&
			TypeFile::isImage($fileArray['ORIGINAL_NAME'])
		)
		{
			$type = Renderer\Image::getJsType();
		}

		if ($type === Renderer\Image::getJsType())
		{
			$treatImageAsFile = DiskUploaderController::shouldTreatImageAsFile($fileArray);
			if ($treatImageAsFile)
			{
				$type = Renderer\Stub::getJsType();
			}
		}

		return $type;
	}

	protected static function isSetViewDocumentInClouds()
	{
		$documentHandler = self::getDefaultHandlerForView();

		return !($documentHandler instanceof BitrixHandler);
	}

	protected static function isAllowedUseClouds($contentType)
	{
		if (!Configuration::canCreateFileByCloud())
		{
			return false;
		}

		$documentHandler = self::getDefaultHandlerForView();
		if ($documentHandler instanceof GoogleViewerHandler && !Configuration::isEnabledAutoExternalLink())
		{
			return false;
		}

		return in_array($contentType, self::getInputContentTypes(), true);
	}

	protected static function getInputContentTypes(): array
	{
		$types = [
			MimeType::getByFileExtension('pdf'),
			'application/rtf',
			'application/vnd.ms-powerpoint',
		];

		$documentHandler = self::getDefaultHandlerForView();
		$editableExtensions = $documentHandler::listEditableExtensions();
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

	protected static function getDefaultHandlerForView(): DocumentHandler
	{
		return Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
	}

	public function __toString()
	{
		$extension = $this->getExtension();
		if ($extension)
		{
			Extension::load($extension);
		}

		return parent::__toString();
	}
}
