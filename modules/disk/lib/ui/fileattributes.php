<?php

namespace Bitrix\Disk\Ui;

use Bitrix\Disk\Document\BitrixHandler;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UI\Viewer\ItemAttributes;
use Bitrix\Main\UI\Viewer\Renderer;
use \Bitrix\Disk;
use \Bitrix\Disk\Internal\Service\Document;

final class FileAttributes extends ItemAttributes
{
	public const ATTRIBUTE_OBJECT_ID = 'data-object-id';
	public const ATTRIBUTE_VERSION_ID = 'data-version-id';
	public const ATTRIBUTE_ATTACHED_OBJECT_ID = 'data-attached-object-id';
	public const ATTRIBUTE_SEPARATE_ITEM = 'data-viewer-separate-item';
	public const ATTRIBUTE_UNIFIED_LINK = 'data-unified-link';

	public const JS_TYPE = 'cloud-document';

	public const JS_TYPE_CLASS_CLOUD_DOCUMENT = 'BX.Disk.Viewer.DocumentItem';
	public const JS_TYPE_CLASS_ONLYOFFICE = 'BX.Disk.Viewer.OnlyOfficeItem';
	public const JS_TYPE_CLASS_UNIFIED_LINK = 'BX.Disk.Viewer.UnifiedLinkItem';
	public const JS_TYPE_CLASS_BOARD = 'BX.Disk.Viewer.BoardItem';

	public const KEY_FILE_OBJECT = 'FILE_OBJECT';

	private bool $needSetUnifiedLink = false;
	private array $unifiedLinkOptions = [];
	private bool $useUnifiedEditLink = false;

	public static function tryBuildByFileId($fileId, $sourceUri, ?File $file = null): self
	{
		try
		{
			return self::buildByFileId($fileId, $sourceUri, $file);
		}
		catch (ArgumentException)
		{
			return self::buildAsUnknownType($sourceUri);
		}
	}

	public static function buildByFileId($fileId, $sourceUri, ?File $file = null): self
	{
		$fileData = \CFile::getByID($fileId)->fetch();

		if ($fileData === false)
		{
			throw new ArgumentException('Invalid fileId', 'fileId');
		}

		$fileData[self::KEY_FILE_OBJECT] = $file;

		return self::buildByFileData($fileData, $sourceUri);
	}

	private function supportsUnifiedLink(): bool
	{
		$file = $this->getFileObject();
		if ($file === null)
		{
			return false;
		}

		return $file->supportsUnifiedLink();
	}

	private function setUnifiedLink(): void
	{
		$file = $this->getFileObject();
		if ($this->needSetUnifiedLink
			&& isset($file)
			&& $this->getTypeClass() === self::JS_TYPE_CLASS_UNIFIED_LINK
		)
		{
			$unifiedLinkOptions = $this->unifiedLinkOptions;

			$versionId = (int)$this->getAttribute(self::ATTRIBUTE_VERSION_ID);
			if ($versionId > 0)
			{
				$unifiedLinkOptions['versionId'] = $versionId;
			}

			$attachedObjectId = (int)$this->getAttribute(self::ATTRIBUTE_ATTACHED_OBJECT_ID);
			if ($attachedObjectId > 0)
			{
				$unifiedLinkOptions['attachedId'] = $attachedObjectId;
			}

			$urlManager = Driver::getInstance()->getUrlManager();
			$unifiedLink = $this->useUnifiedEditLink
				? $urlManager->getUnifiedEditLink($file, $unifiedLinkOptions)
				: $urlManager->getUnifiedLink($file, $unifiedLinkOptions);

			$this->setAttribute(self::ATTRIBUTE_UNIFIED_LINK, $unifiedLink);
		}
	}

	/**
	 * @param array{
	 *      absolute?: bool,
	 *      attachedId?: ?int,
	 *      versionId?: ?int,
	 *      noRedirect?: bool,
	 *      additionalQueryParams?: array
	 * } $options
	 * @return $this
	 */
	public function setUnifiedLinkOptions(array $options): self
	{
		$this->unifiedLinkOptions = $options;

		return $this;
	}

	public function setUseUnifiedEditLink(bool $use = true): self
	{
		$this->useUnifiedEditLink = $use;

		return $this;
	}

	private function getFileObject(): ?File
	{
		if (isset($this->fileData[self::KEY_FILE_OBJECT]) && $this->fileData[self::KEY_FILE_OBJECT] instanceof File)
		{
			return $this->fileData[self::KEY_FILE_OBJECT];
		}

		return null;
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

	protected function setDefaultAttributes(): void
	{
		parent::setDefaultAttributes();

		if ($this->getViewerType() === Disk\UI\Viewer\Renderer\Board::getJsType())
		{
			if ($this->supportsUnifiedLink())
			{
				$this->setUnifiedLinkViewer();
			}
			else
			{
				$this
					->setAttribute('data-viewer-type-class', 'BX.Disk.Viewer.BoardItem')
					->setTypeClass(self::JS_TYPE_CLASS_BOARD)
					->setAsSeparateItem()
					->setExtension('disk.viewer.board-item')
				;

				Extension::load('disk.viewer.board-item');
			}
		}

		if (self::isSetViewDocumentInClouds() && Document\DocumentViewPolicy::isAllowedUseClouds($this->fileData['CONTENT_TYPE']))
		{
			$documentHandler = Document\DocumentViewPolicy::getDefaultHandlerForView();
			if ($documentHandler instanceof OnlyOfficeHandler)
			{
				if ($this->supportsUnifiedLink())
				{
					$this->setUnifiedLinkViewer();
				}
				else
				{
					$this
						->setTypeClass(self::JS_TYPE_CLASS_ONLYOFFICE)
						->setAsSeparateItem()
						->setExtension('disk.viewer.onlyoffice-item')
					;

					Extension::load('disk.viewer.onlyoffice-item');
				}
			}
			else
			{
				$this->setTypeClass(self::JS_TYPE_CLASS_CLOUD_DOCUMENT);
				$this->setExtension('disk.viewer.document-item');

				Extension::load('disk.viewer.document-item');
			}
		}
	}

	private function setUnifiedLinkViewer(): void
	{
		$this
			->setTypeClass(self::JS_TYPE_CLASS_UNIFIED_LINK)
			->setAsSeparateItem()
			->setExtension('disk.viewer.unified-link-item')
		;

		$this->needSetUnifiedLink = true;

		Extension::load('disk.viewer.unified-link-item');
	}

	public function setGroupBy($id)
	{
		if (in_array(
			$this->getTypeClass(),
			[self::JS_TYPE_CLASS_ONLYOFFICE, self::JS_TYPE_CLASS_BOARD, self::JS_TYPE_CLASS_UNIFIED_LINK],
			true)
		)
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

		if ($type === Renderer\Pdf::getJsType() || Document\DocumentViewPolicy::isAllowedUseClouds($fileArray['CONTENT_TYPE']))
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

		if (self::isBoardType($fileArray))
		{
			$type = Disk\UI\Viewer\Renderer\Board::getJsType();
		}

		return $type;
	}

	protected static function isBoardType(array $fileData): bool
	{
		return !empty($fileData['CONTENT_TYPE'])
			&& $fileData['CONTENT_TYPE'] === 'application/octet-stream'
			&& GetFileExtension($fileData['ORIGINAL_NAME'] ?? '') === 'board'
		;
	}

	protected static function isSetViewDocumentInClouds()
	{
		$documentHandler = Document\DocumentViewPolicy::getDefaultHandlerForView();

		return !($documentHandler instanceof BitrixHandler);
	}

	public function __toString()
	{
		$extension = $this->getExtension();
		if ($extension)
		{
			Extension::load($extension);
		}

		$this->setUnifiedLink();

		return parent::__toString();
	}

	public function toDataSet()
	{
		$this->setUnifiedLink();

		return parent::toDataSet();
	}

	public function toVueBind(): array
	{
		$this->setUnifiedLink();

		return parent::toVueBind();
	}
}
