<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Configuration;
use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Document\GoogleViewerHandler;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\UI\Viewer\Renderer;
use Bitrix\Main\Web\MimeType;

final class FileAttributes extends ItemAttributes
{
	public const ATTRIBUTE_OBJECT_ID = 'data-object-id';
	public const ATTRIBUTE_VERSION_ID = 'data-version-id';
	public const ATTRIBUTE_ATTACHED_OBJECT_ID = 'data-attached-object-id';
	public const ATTRIBUTE_SEPARATE_ITEM = 'data-viewer-separate-item';

	public const JS_TYPE = 'cloud-document';

	public const JS_TYPE_CLASS_CLOUD_DOCUMENT = 'BX.Disk.Viewer.DocumentItem';
	public const JS_TYPE_CLASS_ONLYOFFICE = 'BX.Disk.Viewer.OnlyOfficeItem';

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

		if (self::isSetViewDocumentInClouds() && self::isAllowedUseClouds($this->fileData['CONTENT_TYPE']))
		{
			$documentHandler = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
			if ($documentHandler instanceof OnlyOfficeHandler)
			{
				$this->setTypeClass(self::JS_TYPE_CLASS_ONLYOFFICE);
				$this->setAsSeparateItem();
			}
			else
			{
				$this->setTypeClass(self::JS_TYPE_CLASS_CLOUD_DOCUMENT);
			}

			$this->setExtension('disk.viewer.document-item');

			Extension::load('disk.viewer.document-item');
		}
	}

	public function setGroupBy($id)
	{
		if ($this->getTypeClass() === self::JS_TYPE_CLASS_ONLYOFFICE)
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

		if ($type === Renderer\Pdf::getJsType())
		{
			if (self::isAllowedUseClouds($fileArray['CONTENT_TYPE']))
			{
				return self::JS_TYPE;
			}

			return Renderer\Stub::getJsType();
		}

		return $type;
	}

	protected static function refineType($type, $fileArray)
	{
		if (
			$type === Renderer\Stub::getJsType() &&
			!empty($fileArray['ORIGINAL_NAME']) &&
			TypeFile::isImage($fileArray['ORIGINAL_NAME'])
		)
		{
			return Renderer\Image::getJsType();
		}

		return $type;
	}

	protected static function isSetViewDocumentInClouds()
	{
		$documentHandler = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();

		return !($documentHandler instanceof BitrixHandler);
	}

	protected static function isAllowedUseClouds($contentType)
	{
		if (!Configuration::canCreateFileByCloud())
		{
			return false;
		}

		$documentHandler = Driver::getInstance()->getDocumentHandlersManager()->getDefaultHandlerForView();
		if ($documentHandler instanceof GoogleViewerHandler && !Configuration::isEnabledAutoExternalLink())
		{
			return false;
		}

		return in_array($contentType, self::getInputContentTypes(), true);
	}

	protected static function getInputContentTypes()
	{
		return [
			MimeType::getByFileExtension('pdf'),
			MimeType::getByFileExtension('doc'),
			MimeType::getByFileExtension('docx'),
			MimeType::getByFileExtension('xls'),
			MimeType::getByFileExtension('xlsx'),
			MimeType::getByFileExtension('ppt'),
			MimeType::getByFileExtension('pptx'),
			'application/vnd.ms-powerpoint',
		];
	}
}
