<?php

namespace Bitrix\Disk\View;

use Bitrix\Disk\Integration\TransformerManager;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Uf\BlogPostConnector;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\File;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UI\Viewer\PreviewManager;

/**
 * @deprecated
 */
class Base
{
	const TRANSFORM_STATUS_SUCCESS = 'success';
	const TRANSFORM_STATUS_WAS_TRANSFORMED = 'was transformed';
	const TRANSFORM_STATUS_NO_MODULE = 'no module';
	const TRANSFORM_STATUS_NOT_ALLOWED = 'not allowed';

	/** @var string */
	protected $name;
	/** @var int */
	protected $fileId;
	/** @var null|int */
	protected $previewId;
	/** @var null|int */
	protected $id;
	/** @var string */
	protected $fileExtension;

	/** @var array */
	protected $previewData;
	/** @var array */
	protected $data;

	/** @var int */
	protected $jsViewerWidth;
	/** @var int */
	protected $jsViewerHeight;
	/** @var bool */
	protected $isTransformationEnabledInStorage;

	/**
	 * View constructor.
	 * @param string $name Name of the file.
	 * @param int $fileId ID from b_file of the file.
	 * @param int $viewId ID from b_file of the view.
	 * @param int $previewId ID from b_file of the preview.
	 * @param bool $isTransformationEnabledInStorage.
	 * @throws ArgumentNullException
	 */
	public function __construct($name, $fileId, $viewId = null, $previewId = null, $isTransformationEnabledInStorage = true)
	{
		if(empty($name))
		{
			throw new ArgumentNullException('name');
		}
		$this->name = $name;
		$this->fileId = $fileId;
		$this->id = $viewId;
		$this->previewId = $previewId;
		$this->fileExtension = getFileExtension($this->name);
		$this->jsViewerHeight = 700;
		$this->jsViewerWidth = 900;
		$this->isTransformationEnabledInStorage = $isTransformationEnabledInStorage;
	}

	/**
	 * Returns file (@see CFile::getById()).
	 * If there is no record - delete
	 * @return array|null
	 */
	public function getData()
	{
		$viewId = $this->getId();

		if(!$viewId)
		{
			return null;
		}

		if(isset($this->data) && $viewId == $this->data['ID'])
		{
			return $this->data;
		}
		$this->data = $this->getFileData($viewId);

		if(!$this->data)
		{
			$fileObject = File::getById($this->fileId);
			if($fileObject)
			{
				$fileObject->changeViewId(null);
			}
			return array();
		}

		if(!$this->data)
		{
			return array();
		}

		return $this->data;
	}

	/**
	 * Returns file (@see CFile::getById());
	 * @return array|null
	 */
	public function getPreviewData()
	{
		if(!$this->previewId)
		{
			return null;
		}

		if(isset($this->previewData) && $this->previewId == $this->previewData['ID'])
		{
			return $this->previewData;
		}
		$this->previewData = $this->getFileData($this->previewId);

		if(!$this->previewData)
		{
			$fileObject = File::getById($this->fileId);
			if($fileObject)
			{
				$fileObject->changePreviewId(null);
			}
			return array();
		}

		return $this->previewData;
	}

	/**
	 * Get file data from b_file on $fileId.
	 *
	 * @param int $fileId
	 * @return array|false
	 */
	protected function getFileData($fileId)
	{
		$fileId = (int)$fileId;
		if($fileId > 0)
		{
			return \CFile::GetFileArray($fileId);
		}

		return false;
	}

	/**
	 * Return ID from b_file for the viewable file.
	 *
	 * @return int|null
	 */
	public function getId()
	{
		if($this->id)
		{
			return $this->id;
		}

		if($this->isOriginViewable())
		{
			return $this->fileId;
		}

		return null;
	}

	/**
	 * Check whether this file is viewable as it is.
	 *
	 * @return bool
	 */
	protected function isOriginViewable()
	{
		if(count(static::getViewableExtensions()) > 0)
		{
			return in_array(mb_strtolower($this->fileExtension), static::getViewableExtensions());
		}

		return true;
	}

	/**
	 * Extension of the view.
	 *
	 * @return string
	 */
	public static function getViewExtension()
	{
		return '';
	}

	/**
	 * Mime-type of the view.
	 *
	 * @return mixed
	 */
	public static function getMimeType()
	{
		$mimeTypes = TypeFile::getMimeTypeExtensionList();
		return $mimeTypes[static::getViewExtension()];
	}

	/**
	 * Extension of the preview.
	 *
	 * @return string
	 */
	public static function getPreviewExtension()
	{
		return 'jpg';
	}

	/**
	 * Returns name of the preview.
	 * @return string
	 */
	public function getPreviewName()
	{
		return $this->name . '.' . $this->getPreviewExtension();
	}

	/**
	 * Mime-type of the preview.
	 *
	 * @return mixed
	 */
	public static function getPreviewMimeType()
	{
		return 'image/jpeg';
	}

	/**
	 * Return html code to view file.
	 *
	 * @param array $params
	 * @return string
	 */
	public function render($params = array())
	{
		return '';
	}

	/**
	 * True if view should be saved in version as well.
	 *
	 * @return bool
	 */
	public function isSaveForVersion()
	{
		return false;
	}

	/**
	 * Return name of the view.
	 *
	 * @return string
	 */
	public function getName()
	{
		$name = $this->name;
		if(!$this->isOriginViewable())
		{
			$name .= '.' . $this->getViewExtension();
		}
		return $name;
	}

	/**
	 * Return html-attribute for iframe viewer.
	 *
	 * @return string|null
	 */
	public function getJsViewerFallbackHtmlAttributeName()
	{
		return null;
	}

	/**
	 * Return type of viewer from core_viewer.js
	 *
	 * @return string|null
	 */
	public function getJsViewerType()
	{
		return null;
	}

	/**
	 * Returns additional json array parameters for core_viewer.js
	 *
	 * @return array
	 */
	public function getJsViewerAdditionalJsonParams()
	{
		return array();
	}

	/**
	 * Is transformation allowed for this View.
	 *
	 * @return bool
	 */
	public static function isTransformationAllowedInOptions()
	{
		return false;
	}

	/**
	 * Returns maximum allowed transformation file size.
	 *
	 * @return int
	 */
	public function getMaxSizeTransformation()
	{
		return 0;
	}

	/**
	 * Returns true if transformation is allowed for this file.
	 *
	 * @param int $size Size of the file.
	 * @return bool
	 */
	public function isTransformationAllowed($size = 0)
	{
		$transformerManager = new \Bitrix\Main\UI\Viewer\Transformation\TransformerManager();
		if($transformerManager->isAvailable())
		{
			$fileData = $this->getFileData($this->fileId);
			if(!$fileData)
			{
				return false;
			}
			$transformation = $transformerManager->buildTransformationByFile($fileData);

			if(!$transformation)
			{
				return false;
			}

			$inputMaxSize = $transformation->getInputMaxSize();

			return !($inputMaxSize > 0 && $fileData['FILE_SIZE'] > $inputMaxSize);
		}

		return false;
	}

	/**
	 * Returns width of popup window for core_viewer.js
	 *
	 * @return int
	 */
	public function getJsViewerWidth()
	{
		return $this->jsViewerWidth;
	}

	/**
	 * Returns height of popup window for core_viewer.js
	 *
	 * @return int
	 */
	public function getJsViewerHeight()
	{
		return $this->jsViewerHeight;
	}

	/**
	 * Get the size of the file.
	 *
	 * @return int
	 */
	protected function getSize()
	{
		$file = \CFile::GetByID($this->fileId)->Fetch();
		if($file)
		{
			return $file['FILE_SIZE'];
		}

		return 0;
	}

	/**
	 * Returns true if view can be rendered in some way.
	 *
	 * @return bool
	 */
	public function isHtmlAvailable()
	{
		return false;
	}

	/**
	 * Returns html of the dummy view.
	 *
	 * @return string
	 */
	public function renderTransformationInProcessMessage()
	{
		return '';
	}

	/**
	 * Returns true if this file should be transformed on open.
	 *
	 * @param int $size
	 * @return bool
	 */
	public function isTransformationAllowedOnOpen($size = 0)
	{
		if($this->id)
		{
			return false;
		}
		return $this->isTransformationAllowed($size) && Configuration::allowTransformFilesOnOpen();
	}

	/**
	 * Send command to transform file if necessary. Returns array for json response.
	 *
	 * @param File $file
	 * @return array
	 */
	public function transformOnOpen(File $file)
	{
		if (!$this->isTransformationAllowedOnOpen($file->getSize()))
		{
			return [
				'status' => self::TRANSFORM_STATUS_NOT_ALLOWED,
			];
		}

		if (!Loader::includeModule('transformer'))
		{
			return [
				'status' => self::TRANSFORM_STATUS_NO_MODULE,
			];
		}

		$data = null;
		if (!TransformerManager::checkTransformationAttempts($file))
		{
			$previewManager = new PreviewManager();
			$data = $previewManager->generatePreview($file->getFileId())->getData();

			$status = self::TRANSFORM_STATUS_SUCCESS;
		}
		else
		{
			$status = self::TRANSFORM_STATUS_WAS_TRANSFORMED;
		}

		BlogPostConnector::clearCacheByObjectId($file->getId());

		return [
			'status' => $status,
			'data' => $data,
		];
	}

	/**
	 * Returns true if edit button should be hidden in js viewer.
	 *
	 * @return bool
	 */
	public function isJsViewerHideEditButton()
	{
		return false;
	}

	/**
	 * Get type attribute for bb-code in html-editor
	 *
	 * @return string
	 */
	public function getEditorTypeFile()
	{
		return '';
	}

	/**
	 * Returns array of extensions that can be viewed.
	 *
	 * @return array
	 */
	public static function getViewableExtensions()
	{
		return array();
	}

	/**
	 * Returns array of alternative extensions, that has the same mime type as main extension
	 *
	 * @return array
	 */
	public static function getAlternativeExtensions()
	{
		return array();
	}

	/**
	 * Returns actual mime-type of view.
	 *
	 * @return string
	 */
	protected function getExtension()
	{
		if($this->id)
		{
			return static::getViewExtension();
		}

		return mb_strtolower($this->fileExtension);
	}

	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Returns true if file should be transformed into view regardless of origin extension.
	 *
	 * @return bool
	 */
	public static function isAlwaysTransformToViewFormat()
	{
		return false;
	}

	/**
	 * Returns approximate time of transformation of the file.
	 *
	 * @return int
	 */
	public function getTransformTime()
	{
		return 0;
	}

	/**
	 * Returns true if we should limit rights on attached object with this file while transform in progress.
	 *
	 * @return bool
	 */
	public function isNeededLimitRightsOnTransformTime()
	{
		return false;
	}

	/**
	 * Returns true if we should display message about transformation status.
	 *
	 * @return bool
	 */
	public function isShowTransformationInfo()
	{
		return false;
	}

	/**
	 * Returns true if there was an error in last transformation for this file.
	 *
	 * @return array|bool
	 */
	public function isLastTransformationFailed()
	{
		if(Loader::includeModule('transformer'))
		{
			$info = \Bitrix\Transformer\FileTransformer::getTransformationInfoByFile((int)$this->fileId);
			if(!$info || $info['status'] > \Bitrix\Transformer\Command::STATUS_SUCCESS)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true upgrade message should be shown.
	 *
	 * @return bool
	 */
	public function isShowTransformationUpgradeMessage()
	{
		if(!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return false;
		}

		if($this->getId())
		{
			return false;
		}

		if($this->isShowTransformationInfo() && !$this->isTransformationAllowed())
		{
			return true;
		}

		return false;
	}
}