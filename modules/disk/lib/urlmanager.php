<?php

namespace Bitrix\Disk;

use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Diag;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\View\Video;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;

class UrlManager implements IErrorable
{
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var  BaseComponent */
	protected $component;

	/**
	 * Constructor UrlManager
	 */
	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * Resolves path in complex component and find target file or folder.
	 * @param \CComponentEngine $engine Component engine.
	 * @param array             $pageCandidates Page candidates.
	 * @param array             &$variables Output variables.
	 * @return int|string
	 */
	public function resolvePathComponentEngine(\CComponentEngine $engine, $pageCandidates, &$variables)
	{
		$component = $engine->getComponent();
		if(!$component)
		{
			$pageId = key($pageCandidates);
			$variables = current($pageCandidates);

			return $pageId;
		}
		/** @var Storage $storage */
		$storage = $component->arParams['STORAGE'];

		foreach ($pageCandidates as $pageId => $variablesTmp)
		{
			if(isset($variablesTmp["PATH"]) && is_string($variablesTmp["PATH"]) && $variablesTmp["PATH"] <> '')
			{
				$variables = array_merge($variablesTmp, $this->resolvePathToFolder($storage, $variablesTmp["PATH"]));
				if(empty($variables['FOLDER_ID']))
				{
					return '';
				}
				return $pageId;
			}
			elseif(isset($variablesTmp["FILE_PATH"]) && is_string($variablesTmp["FILE_PATH"]) && $variablesTmp["FILE_PATH"] <> '')
			{
				$variables = array_merge($variablesTmp, $this->resolvePathToFile($storage, $variablesTmp["FILE_PATH"]));
				if(empty($variables['FILE_ID']))
				{
					return '';
				}
				return $pageId;
			}
			elseif(isset($variablesTmp["TRASH_PATH"]) && is_string($variablesTmp["TRASH_PATH"]) && $variablesTmp["TRASH_PATH"] <> '')
			{
				$variables = array_merge($variablesTmp, $this->resolvePathToTrashFolder($storage, $variablesTmp["TRASH_PATH"]));
				if(empty($variables['FOLDER_ID']))
				{
					return '';
				}
				return $pageId;
			}
			elseif(isset($variablesTmp["TRASH_FILE_PATH"]) && is_string($variablesTmp["TRASH_FILE_PATH"]) && $variablesTmp["TRASH_FILE_PATH"] <> '')
			{
				$variables = array_merge($variablesTmp, $this->resolvePathToTrashFile($storage, $variablesTmp["TRASH_FILE_PATH"]));
				if(empty($variables['FILE_ID']))
				{
					return '';
				}
				return $pageId;
			}
		}

		$pageId = key($pageCandidates);
		$variables = current($pageCandidates);

		return $pageId;
	}

	/**
	 * Resolves path in complex component (socialnetwork) and find target file or folder.
	 * @param \CComponentEngine $engine Component engine.
	 * @param array             $pageCandidates Page candidates.
	 * @param array             &$variables Output variables.
	 * @return int|string
	 */
	public function resolveSocNetPathComponentEngine(\CComponentEngine $engine, $pageCandidates, &$variables)
	{
		$component = $engine->getComponent();
		if(!$component)
		{
			$pageId = key($pageCandidates);
			$variables = current($pageCandidates);

			return $pageId;
		}

		$storage = null;
		foreach ($pageCandidates as $pageId => $variablesTmp)
		{
			if(isset($variablesTmp["PATH"]) && is_string($variablesTmp["PATH"]) && $variablesTmp["PATH"] <> '')
			{
				$storage = $this->getStorageByVariables($variablesTmp);
				if(!$storage)
				{
					return '';
				}
				$variables = array_merge($variablesTmp, $this->resolvePathToFolder($storage, $variablesTmp["PATH"]));
				if(empty($variables['FOLDER_ID']))
				{
					return '';
				}
				return $pageId;
			}
			elseif(isset($variablesTmp["FILE_PATH"]) && is_string($variablesTmp["FILE_PATH"]) && $variablesTmp["FILE_PATH"] <> '')
			{
				$storage = $this->getStorageByVariables($variablesTmp);
				if(!$storage)
				{
					return '';
				}
				$variables = array_merge($variablesTmp, $this->resolvePathToFile($storage, $variablesTmp["FILE_PATH"]));
				if(empty($variables['FILE_ID']))
				{
					return '';
				}
				return $pageId;
			}
			elseif(isset($variablesTmp["TRASH_PATH"]) && is_string($variablesTmp["TRASH_PATH"]) && $variablesTmp["TRASH_PATH"] <> '')
			{
				$storage = $this->getStorageByVariables($variablesTmp);
				if(!$storage)
				{
					return '';
				}
				$variables = array_merge($variablesTmp, $this->resolvePathToTrashFolder($storage, $variablesTmp["TRASH_PATH"]));
				if(empty($variables['FOLDER_ID']))
				{
					return '';
				}
				return $pageId;
			}
			elseif(isset($variablesTmp["TRASH_FILE_PATH"]) && is_string($variablesTmp["TRASH_FILE_PATH"]) && $variablesTmp["TRASH_FILE_PATH"] <> '')
			{
				$storage = $this->getStorageByVariables($variablesTmp);
				if(!$storage)
				{
					return '';
				}
				$variables = array_merge($variablesTmp, $this->resolvePathToTrashFile($storage, $variablesTmp["TRASH_FILE_PATH"]));
				if(empty($variables['FILE_ID']))
				{
					return '';
				}
				return $pageId;
			}
		}

		$pageId = key($pageCandidates);
		$variables = current($pageCandidates);

		return $pageId;
	}

	private function getStorageByVariables($variablesTmp)
	{
		$storage = null;
		/** @var Storage $storage */
		if(!empty($variablesTmp['user_id']))
		{
			$storage = Driver::getInstance()->getStorageByUserId((int)$variablesTmp['user_id']);
		}
		elseif(!empty($variablesTmp['group_id']))
		{
			$storage = Driver::getInstance()->getStorageByGroupId((int)$variablesTmp['group_id']);
		}

		return $storage;
	}

	/**
	 * Encodes uri: explodes uri by / and encodes in UTF-8 and rawurlencodes.
	 * @param string $uri Uri.
	 * @return string
	 */
	public function encodeUrn($uri)
	{
		global $APPLICATION;

		$result = '';
		$parts = preg_split("#(://|:\\d+/|/|\\?|=|&)#", $uri, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach($parts as $i => $part)
		{
			$result .= ($i % 2)
				? $part
				: rawurlencode($APPLICATION->convertCharset($part, LANG_CHARSET, 'UTF-8'));
		}

		return $result;
	}

	/**
	 * Gets url for external link by parameters.
	 *
	 * Tries to find rewrite condition for disk.external.link.
	 *
	 * @param array $paramsUri Parameters for uri.
	 * @param bool  $absolute Prepend host url.
	 * @return string
	 */
	public function getUrlExternalLink(array $paramsUri, $absolute = false)
	{
		static $rewriteCondition = null;
		if($rewriteCondition === null)
		{

			$extLinksAccessPoints = \CUrlRewriter::getList(array('ID' => 'bitrix:disk.external.link'));
			if(empty($extLinksAccessPoints))
			{
				$rewriteCondition = "#^/docs/pub/(?<hash>[0-9a-f]{32})/(?<action>.*)\$#";
			}
			else
			{
				$rewrite = reset($extLinksAccessPoints);
				$rewriteCondition = $rewrite['CONDITION'];
			}
		}

		$url = $this->buildUrl($rewriteCondition, $paramsUri);
		if ($absolute && Loader::includeModule('bitrix24') && !\CBitrix24::isCustomDomain())
		{
			$host = parse_url($this->getHostUrl(), PHP_URL_HOST);

			return "https://bitrix24public.com/{$host}{$url}";
		}

		return ($absolute? $this->getHostUrl() : '') . $url;
	}

	/**
	 * Gets short url on external link by \CBXShortUri.
	 * @param array $paramsUri  Parameters for uri.
	 * @param bool  $absolute Prepend host url.
	 * @return string
	 */
	public function getShortUrlExternalLink(array $paramsUri, $absolute = false)
	{
		return ($absolute? $this->getHostUrl() : '') . \CBXShortUri::getShortUri($this->getUrlExternalLink($paramsUri, $absolute));
	}

	/**
	 * Gets host url with port and scheme.
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function getHostUrl()
	{
		$protocol = (\CMain::isHTTPS() ? 'https' : 'http');
		if (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME)
		{
			$host = SITE_SERVER_NAME;
		}
		else
		{
			$host =
				Option::get('main', 'server_name', Context::getCurrent()->getServer()->getHttpHost())?:
					Context::getCurrent()->getServer()->getHttpHost()
			;
		}

		$port = Context::getCurrent()->getServer()->getServerPort();
		if($port <> 80 && $port <> 443 && $port > 0 && mb_strpos($host, ':') === false)
		{
			$host .= ':'.$port;
		}
		elseif($protocol == 'http' && $port == 80)
		{
			$host = str_replace(':80', '', $host);
		}
		elseif($protocol == 'https' && $port == 443)
		{
			$host = str_replace(':443', '', $host);
		}

		return $protocol . '://' . $host;
	}

	/**
	 * Gets path to list where file or folder.
	 * @param BaseObject $object Target file or folder.
	 * @return string
	 */
	public function getPathInListing(BaseObject $object)
	{
		if($object->getStorage()->getRootObjectId() == $object->getId())
		{
			return $object
				->getStorage()
				->getProxyType()
				->getBaseUrlFolderList()
			;
		}

		$crumbs = implode('/', CrumbStorage::getInstance()->getByObject($object));
		if($crumbs)
		{
			$crumbs .= '/';
		}

		return $object
			->getStorage()
			->getProxyType()
			->getBaseUrlFolderList() . $crumbs
		;
	}

	/**
	 * Returns path to list where file or folder is in trashcan.
	 *
	 * @param BaseObject $object Folder or File.
	 * @return string
	 */
	public function getPathInTrashcanListing(BaseObject $object)
	{
		if (!$object->isDeleted())
		{
			return $this->getPathInListing($object);
		}

		$root = $this->getTrashcan($object->getStorage());
		$crumbs = $this->buildPathToDeletedObject($object)?: '';

		return $root . $crumbs;
	}

	/**
	 * Returns path to root of trashcan.
	 *
	 * @param Storage $storage Storage.
	 * @return string
	 */
	public function getTrashcan(Storage $storage)
	{
		return $storage->getProxyType()->getBaseUrlTashcanList();
	}

	/**
	 * Returns path to list folder.
	 * @param Folder $folder Target folder.
	 * @return string
	 */
	public function getPathFolderList(Folder $folder)
	{
		if($folder->getStorage()->getRootObjectId() == $folder->getId())
		{
			return $folder
				->getStorage()
				->getProxyType()
				->getBaseUrlFolderList()
			;
		}

		$crumbs = implode('/', CrumbStorage::getInstance()->getByObject($folder));
		if($crumbs)
		{
			$crumbs .= '/';
		}
		$crumbs .= $folder->getName();

		return $folder
			->getStorage()
			->getProxyType()
			->getBaseUrlFolderList() . $crumbs
		;
	}

	/**
	 * Gets path to detail page of file.
	 * @param File $file Target file.
	 * @return string
	 */
	public function getPathFileDetail(File $file)
	{
		$crumbs = CrumbStorage::getInstance()->getByObject($file, true);

		return $file
			->getStorage()
			->getProxyType()
			->getBaseUrlFileDetail() . implode('/', $crumbs);
	}

	/**
	 * Gets path to detail page of file in trashcan.
	 * @param File $file Target file.
	 * @return string
	 */
	public function getPathTrashcanFileDetail(File $file)
	{
		if (!$file->isDeleted())
		{
			return $this->getPathFileDetail($file);
		}

		$baseUrlTrashcanFileDetail = $file->getStorage()->getProxyType()->getBaseUrlTrashcanFileDetail();
		$crumbs = $this->buildPathToDeletedObject($file)?: '';

		return $baseUrlTrashcanFileDetail . $crumbs . $file->getOriginalName();
	}

	/**
	 * Gets url for start create file by cloud services.
	 * @param string $typeFile Type of file (docx, pptx, xlsx). See in \Bitrix\Disk\Document\BlankFileData (docx, pptx, xslx).
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlForStartCreateFile($typeFile, $service)
	{
		return static::getUrlDocumentController('', array(
			'document_action' => 'start',
			'primaryAction' => 'publishBlank',
			'type' => $typeFile,
			'service' => $service,
		));
	}

	/**
	 * Gets url for start edit file by cloud services.
	 * @param mixed $fileId File id.
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlForStartEditFile($fileId, $service)
	{
		return static::getUrlDocumentController('', array(
			'document_action' => 'start',
			'primaryAction' => 'publish',
			'objectId' => $fileId,
			'service' => $service,
		));
	}

	/**
	 * Gets url for start edit version of file by cloud services.
	 * @param int $fileId File id.
	 * @param int $versionId Version id.
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlForStartEditVersion($fileId, $versionId, $service)
	{
		return static::getUrlDocumentController('', array(
			'document_action' => 'start',
			'primaryAction' => 'publish',
			'objectId' => (int)$fileId,
			'versionId' => (int)$versionId,
			'service' => $service,
		));
	}

	/**
	 * Gets url for show file in cloud service.
	 *
	 * @param int $fileId File id.
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getUrlToShowFileByService($fileId, $service, array $params = array())
	{
		return static::getUrlDocumentController('', array_merge(array(
			'document_action' => 'show',
			'primaryAction' => 'show',
			'objectId' => (int)$fileId,
			'service' => $service,
		), $params));
	}

	/**
	 * Gets url for show version of file in cloud service.
	 * @param int $fileId Id of file.
	 * @param int $versionId Id of version of file.
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlToShowVersionByService($fileId, $versionId, $service)
	{
		return static::getUrlDocumentController('', array(
			'document_action' => 'show',
			'primaryAction' => 'show',
			'objectId' => (int)$fileId,
			'versionId' => (int)$versionId,
			'service' => $service,
		));
	}

	/**
	 * Gets url for show file in cloud service (userfield context).
	 * @param int $attachedId Id of attached object.
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlToShowAttachedFileByService($attachedId, $service)
	{
		return static::getUrlUfController('', array(
			'document_action' => 'show',
			'primaryAction' => 'show',
			'attachedId' => (int)$attachedId,
			'service' => $service,
		));
	}

	/**
	 * Gets url for start creating file in cloud service (userfield context).
	 * @param string $typeFile Type of file (docx, pptx, xlsx). See in \Bitrix\Disk\Document\BlankFileData (docx, pptx, xslx).
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlToStartCreateUfFileByService($typeFile, $service)
	{
		return static::getUrlUfController('', array(
			'document_action' => 'start',
			'primaryAction' => 'publishBlank',
			'type' => $typeFile,
			'service' => $service,
		));
	}

	/**
	 * Gets url for start editing file in cloud service (userfield context).
	 * @param int $attachedId Id of attach object.
	 * @param string $service Service which will use to create file. See in subclasses \Bitrix\Disk\Document\DocumentHandler::getCodeName, and event subscribers 'onDocumentHandlerBuildList'.
	 * @return string
	 */
	public static function getUrlToStartEditUfFileByService($attachedId, $service)
	{
		return static::getUrlUfController('', array(
			'document_action' => 'start',
			'primaryAction' => 'publish',
			'attachedId' => $attachedId,
			'service' => $service,
		));
	}

	/**
	 * Gets url to show attached object.
	 * @param int $attachedId Id of attached object.
	 * @param array $params Parameters to add in query.
	 * @return string
	 */
	public static function getUrlToActionShowUfFile($attachedId, array $params = array())
	{
		if(isset($params['width'], $params['height']))
		{
			$params['signature'] = ParameterSigner::getImageSignature($attachedId, $params['width'], $params['height']);
		}
		return static::getUrlUfController('show', array_merge($params, array('attachedId' => $attachedId)));
	}

	/**
	 * Gets url to upload file in userfield context.
	 * @return string
	 */
	public static function getUrlToUploadUfFile()
	{
		return static::getUrlUfController('uploadFile');
	}

	/**
	 * Gets url of userfield controller to run different actions.
	 * @param string $action Action.
	 * @param array  $params Parameters to add in query.
	 * @return string
	 */
	public static function getUrlUfController($action, array $params = array())
	{
		$params['action'] = $action;
		$params['ncc'] = 1;
		return '/bitrix/tools/disk/uf.php?' . http_build_query($params);
	}

	/**
	 * Gets url of focus controller to run different actions.
	 *
	 * @param string $action Action.
	 * @param array $params Parameters to add in query.
	 * @param bool $absolute Generate absolute url with host url.
	 *
	 * @return string
	 */
	public static function getUrlFocusController($action, array $params = array(), $absolute = false)
	{
		$prefix = '';
		if ($absolute)
		{
			$prefix = Driver::getInstance()->getUrlManager()->getHostUrl();
		}

		$params['action'] = $action;
		$params['ncc'] = 1;

		return $prefix . '/bitrix/tools/disk/focus.php?' . http_build_query($params);
	}

	/**
	 * Gets url of document controller to run action with documents.
	 * @param string $action Action.
	 * @param array  $params Parameters to add in query.
	 * @return string
	 */
	public static function getUrlDocumentController($action, array $params = array())
	{
		$params['action'] = $action;
		return '/bitrix/tools/disk/document.php?' . http_build_query($params);
	}

	/**
	 * Gets url to download file.
	 * @param File $file Target file.
	 * @param bool $absolute Generate absolute url with host url.
	 * @return string
	 */
	public function getUrlForDownloadFile(File $file, $absolute = false)
	{
		return $this->getUrlDownloadController('downloadFile', array('fileId' => $file->getId(), 'filename' => $file->getName()), $absolute);
	}

	/**
	 * Gets url to show file.
	 * @param File  $file Target file.
	 * @param array $params Parameters to add in query.
	 * @param bool  $absolute Generate absolute url with host url.
	 * @return string
	 */
	public function getUrlForShowFile(File $file, array $params = array(), $absolute = false)
	{
		if(isset($params['width']) && isset($params['height']))
		{
			$params['signature'] = ParameterSigner::getImageSignature($file->getId(), $params['width'], $params['height']);
		}
		$params['ts'] = $file->getUpdateTime()->getTimestamp();
		return $this->getUrlDownloadController('showFile', array_merge($params, array('fileId' => $file->getId(), 'filename' => $file->getName())), $absolute);
	}

	/**
	 * Get url to show preview of the file.
	 * @param File $file
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function getUrlForShowPreview(File $file, array $params = array(), $absolute = false)
	{
		if(isset($params['width']) && isset($params['height']))
		{
			$params['signature'] = ParameterSigner::getImageSignature($file->getId(), $params['width'], $params['height']);
		}
		$params['ts'] = $file->getUpdateTime()->getTimestamp();
		$filename = $file->getView()->getPreviewName();
		if(mb_strlen($filename) > 80)
		{
			$filename = mb_substr($filename, 0, 37).'...'.mb_substr($filename, -37);
		}
		return $this->getUrlDownloadController('showPreview', array_merge($params, array('fileId' => $file->getId(), 'filename' => $filename)), $absolute);
	}

	/**
	 * Get url to show viewable version of the file.
	 * @param File $file
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function getUrlForShowView(File $file, array $params = array(), $absolute = false)
	{
		$params['ts'] = $file->getUpdateTime()->getTimestamp();
		return $this->getUrlDownloadController('showView', array_merge($params, array('fileId' => $file->getId())), $absolute);
	}

	/**
	 * Get url to html-code to show viewable version of the file.
	 * @param File $file
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function getUrlForShowViewHtml(File $file, array $params = array(), $absolute = false)
	{
		$params['filename'] = $file->getView()->getName();
		if(mb_strlen($params['filename']) > 80)
		{
			$params['filename'] = mb_substr($params['filename'], 0, 37).'...'.mb_substr($params['filename'], -37);
		}
		$preview = $this->getUrlForShowPreview($file);
		if($file->getView() instanceof Video)
		{
			$pathToView = [
				$this->getUrlForShowView($file, $params),
				$this->getUrlForShowFile($file, $params),
			];
		}
		else
		{
			$pathToView = $this->getUrlForShowView($file, $params);
			$printUrl = $this->getUrlDownloadController('showViewHtml', array_merge($params, array('fileId' => $file->getId(), 'pathToView' => $pathToView, 'preview' => $preview, 'print' => 'Y', 'mode' => 'iframe')), $absolute);
		}
		return $this->getUrlDownloadController('showViewHtml', array_merge($params, array('fileId' => $file->getId(), 'pathToView' => $pathToView, 'preview' => $preview, 'printUrl' => $printUrl)), $absolute);
	}

	/**
	 * Get url to html-code to show viewable version of the attached file.
	 *
	 * @param int $attachedId
	 * @param array $params
	 * @param int $updateTime
	 * @return string
	 */
	public function getUrlForShowAttachedFileViewHtml($attachedId, array $params = array(), $updateTime = 0)
	{
		$pathToView = array(
			$this->getUrlUfController('showView', array('attachedId' => $attachedId, 'tc' => ($updateTime > 0? $updateTime: null))),
			$this->getUrlUfController('show', array('attachedId' => $attachedId, 'tc' => ($updateTime > 0? $updateTime: null))),
		);
		$preview = $this->getUrlUfController('showPreview', array('attachedId' => $attachedId, 'tc' => ($updateTime > 0? $updateTime: null)));
		$printUrl = $this->getUrlUfController('showViewHtml', array_merge($params, array('attachedId' => $attachedId, 'pathToView' => $pathToView, 'preview' => $preview, 'print' => 'Y', 'mode' => 'iframe', 'tc' => ($updateTime > 0? $updateTime: null))));
		return $this->getUrlUfController('showViewHtml', array_merge($params, array('attachedId' => $attachedId, 'pathToView' => $pathToView, 'preview' => $preview, 'printUrl' => $printUrl)));
	}

	/**
	 * Get url to html-code to show viewable version of the attached version.
	 *
	 * @param int $attachedId
	 * @param array $params
	 * @return string
	 */
	public function getUrlForShowAttachedVersionViewHtml($attachedId, array $params = array())
	{
		$pathToView = $this->getUrlUfController('showVersionView', array('attachedId' => $attachedId));
		return $this->getUrlUfController('showViewHtml', array_merge($params, array('attachedId' => $attachedId, 'pathToView' => $pathToView)));
	}

	/**
	 * Get url to show view of the version.
	 * @param Version $version
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function getUrlForShowVersionView(Version $version, array $params = array(), $absolute = false)
	{
		return $this->getUrlDownloadController('showVersionView', array_merge($params, array('fileId' => $version->getObjectId(), 'versionId' => $version->getId())), $absolute);
	}

	/**
	 * Get url to html-code to show view of the version.
	 * @param Version $version
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function getUrlForShowVersionViewHtml(Version $version, array $params = array(), $absolute = false)
	{
		$params['filename'] = $version->getView()->getName();
		$pathToView = $this->getUrlForShowVersionView($version, $params);
		return $this->getUrlDownloadController('showViewHtml', array_merge($params, array('fileId' => $version->getObjectId(), 'versionId' => $version->getId(), 'pathToView' => $pathToView)), $absolute);
	}

	/**
	 * Get url to send file on transformation.
	 * @param File $file
	 * @return string
	 */
	public function getUrlForTransformOnOpen(File $file)
	{
		return $this->getUrlDownloadController('transformOnOpen', array('fileId' => $file->getId()));
	}

	/**
	 * Get url to show transformation.dummy
	 * @param File $file
	 * @param array $params
	 * @return string
	 */
	public function getUrlForShowTransformInfo(File $file, $params = array())
	{
		return $this->getUrlDownloadController('showTransformationInfo', array('fileId' => $file->getId()) + $params);
	}

	/**
	 * Gets url to download version of file.
	 * @param Version $version Version of file.
	 * @param bool $absolute Generate absolute url with host url.
	 * @return string
	 */
	public function getUrlForDownloadVersion(Version $version, $absolute = false)
	{
		return $this->getUrlDownloadController('downloadVersion', array(
			'fileId' => $version->getObjectId(),
			'versionId' => $version->getId(),
		), $absolute);
	}

	/**
	 * Gets url of download controller.
	 * @param string $action Action.
	 * @param array  $params Parameters to add in query.
	 * @param bool   $absolute Generate absolute url with host url.
	 * @return string
	 */
	public function getUrlDownloadController($action, array $params = array(), $absolute = false)
	{
		static $rewriteCondition = null;
		if($rewriteCondition === null)
		{

			$accessPoints = \CUrlRewriter::getList(array('ID' => 'bitrix:disk.services'));
			if(empty($accessPoints))
			{
				$rewriteCondition = "#^/disk/(?<action>[0-9a-zA-Z]+)/(?<fileId>[0-9]+)/\?#";
			}
			else
			{
				$rewrite = reset($accessPoints);
				$rewriteCondition = $rewrite['CONDITION'];
			}
		}

		return ($absolute? $this->getHostUrl() : '') .
			$this->buildUrl($rewriteCondition, array('action' => $action, 'ncc' => 1) + $params);
	}

	public function getUrlToDownloadByExternalLink($externalLinkHash, array $params = array(), $absolute = false)
	{
		$params['externalLink'] = $externalLinkHash;
		$params['fileId'] = 0;

		return $this->getUrlDownloadController('downloadByExternalLink', $params, $absolute);
	}

	public function getUrlToGetLastVersionUriByFile($fileId)
	{
		return static::getUrlDocumentController('', array(
			'document_action' => 'getLastVersionUri',
			'service' => 'bitrix',
			'objectId' => $fileId,
		));
	}

	public function getUrlToGetLastVersionUriByAttachedFile($attachedObjectId)
	{
		return $this->getUrlUfController('getLastVersionUri', array(
			'document_action' => 'getLastVersionUri',
			'service' => 'bitrix',
			'attachedId' => $attachedObjectId,
		));
	}


	/**
	 * Generates url for view object in Google.Viewer.
	 * @param string $downloadLink Link to download object.
	 * @return string
	 */
	public function generateUrlForGoogleViewer($downloadLink)
	{
		return 'https://drive.google.com/viewerng/viewer?embedded=true&url=' . urlencode($downloadLink);
	}

	/**
	 * Resolves from path folder id. Search start from root folder of storage.
	 * @param Storage $storage Storage where we run search.
	 * @param string  $path Path to folder.
	 * @return mixed
	 */
	public function resolveFolderIdFromPath(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, $storage->getRootObjectId());
		return $data['OBJECT_ID'];
	}

	/**
	 * Resolves from path file id. Search start from root folder of storage.
	 * @param Storage $storage Storage where we run search.
	 * @param string  $path Path to file.
	 * @return mixed
	 */
	public function resolveFileIdFromPath(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, $storage->getRootObjectId(), ObjectTable::TYPE_FILE);
		return $data['OBJECT_ID'];
	}

	/**
	 * Resolves from path file or folder id. Search start from root folder of storage.
	 * @param Storage $storage Storage where we run search.
	 * @param string  $path Path to file or folder.
	 * @return mixed
	 */
	public function resolveObjectIdFromPath(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, $storage->getRootObjectId(), null);
		return $data['OBJECT_ID'];
	}

	/**
	 * Resolves from path file or folder id. Search start from root folder.
	 *
	 * @param BaseObject $rootObject Root object.
	 * @param string     $path Path to file or folder.
	 * @return int
	 * @internal
	 */
	public function resolveObjectIdFromPathUnderRootObject(BaseObject $rootObject, $path)
	{
		$data = $this->resolvePathUnderRootObject($rootObject, $path);

		return $data['OBJECT_ID'];
	}

	/**
	 * Resolves data from path. Search start from root folder.
	 *
	 * @param BaseObject $rootObject Root object.
	 * @param string     $path Path to file or folder.
	 * @return array
	 * @internal
	 */
	public function resolvePathUnderRootObject(BaseObject $rootObject, $path)
	{
		$data = $this->resolvePath($rootObject->getStorage(), $path, $rootObject->getId(), null);
		if($data['RELATIVE_PATH'] === '/' && $data['OBJECT_ID'] == $rootObject->getStorage()->getId())
		{
			return array(
				'STORAGE' => $rootObject->getStorage(),
				'OBJECT_ID' => $rootObject->getId(),
				'RELATIVE_PATH' => '/',
				'RELATIVE_ITEMS' => array(),
			);
		}

		return $data;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * Sets component, which may use in url manager.
	 * @param BaseComponent $component Component.
	 * @return $this
	 * @internal
	 */
	public function setComponent(BaseComponent $component)
	{
		$this->component = $component;

		return $this;
	}

	/**
	 * Generate uri from url rewrite condition.
	 * Example: $rewriteCondition "#^/pub/(?<hash>[0-9a-f]{32})/(?<action>.*)\$#"
	 * $paramsUri = array('hash' => '9339b4c32205f397e1d0506c7da2a7dd', 'action' => 'download', 'time' => time())
	 *
	 * And buildUrl return: /pub/9339b4c32205f397e1d0506c7da2a7dd/default?time=1397813404
	 * @param string $rewriteCondition  Rewrite condition.
	 * @param array $paramsUri Parameters to add in query.
	 * @return string
	 */
	protected function buildUrl($rewriteCondition, array $paramsUri)
	{
		$replaceNamedPattern = array();
		if(preg_match_all('#(\(\?\<([a-zA-Z]+)\>(?:.*)\))#U', $rewriteCondition, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $namedPattern)
			{
				//name of subpattern
				if(isset($paramsUri[$namedPattern[2]]))
				{
					$replaceNamedPattern[$namedPattern[1]] = $paramsUri[$namedPattern[2]];
					unset($paramsUri[$namedPattern[2]]);
				}
			}
			unset($namedPattern);

			$replaceNamedPattern['(.*)'] = '';

			$uri = strtr($rewriteCondition, $replaceNamedPattern);

			$patternDelimiter = $uri[0];
			$uri = trim(mb_substr($uri, 1, mb_strrpos($uri, $patternDelimiter) - 1), '^$');

			return strtr($uri, array('\?' => '?')) . '&' . http_build_query($paramsUri);
		}

		return '/';
	}

	private function resolvePath(Storage $storage, $path, $lookUpFromFolderId, $lastPart = FolderTable::TYPE_FOLDER)
	{
		Diag::getInstance()->collectDebugInfo('urlmanager');

		if (!is_string($path))
		{
			return null;
		}

		$path = trim($path, '/');
		$relativeItems = array();
		if ($path === '' && $lastPart == FileTable::TYPE)
		{
			return null;
		}
		elseif ($path === '' && $lastPart === null)
		{
			return array(
				'STORAGE' => $storage,
				'OBJECT_ID' => $lookUpFromFolderId?: $storage->getRootObjectId(),
				'RELATIVE_PATH' => '/',
				'RELATIVE_ITEMS' => $relativeItems,
			);
		}

		if ( ($path === '' || $path === 'index.php') && $lastPart == FolderTable::TYPE)
		{
			//by default we show root folder.
			return array(
				'STORAGE' => $storage,
				'OBJECT_ID' => $storage->getRootObjectId(),
				'RELATIVE_PATH' => '/',
				'RELATIVE_ITEMS' => $relativeItems,
			);
		}

		$filter = array(
			'TYPE' => FolderTable::TYPE_FOLDER,
			'STORAGE_ID' => $storage->getId(),
		);

		if($lookUpFromFolderId !== null)
		{
			$filter['PARENT_ID'] = $lookUpFromFolderId;
		}

		$partsOfPath = explode('/', $path);
		if(end($partsOfPath) == 'index.php' && $lastPart !== FileTable::TYPE)
		{
			array_pop($partsOfPath);
		}
		foreach ($partsOfPath as $i => $pieceOfPath)
		{
			if($i === (count($partsOfPath) - 1))
			{
				if($lastPart !== null)
				{
					$filter['TYPE'] = $lastPart;
				}
				else
				{
					unset($filter['TYPE']);
				}
			}

			$filter['=NAME'] = $pieceOfPath;
			$folder = ObjectTable::getList(array(
				'filter' => $filter,
				'select' => array('ID', 'NAME', 'REAL_OBJECT_ID', 'STORAGE_ID', 'PARENT_ID'),
			))->fetch();

			if(!$folder)
			{
				return null;
			}
			if($folder['REAL_OBJECT_ID'])
			{
				$filter['PARENT_ID'] = $folder['REAL_OBJECT_ID'];
				unset($filter['STORAGE_ID']);
			}
			else
			{
				$filter['PARENT_ID'] = $folder['ID'];
				$filter['STORAGE_ID'] = $folder['STORAGE_ID'];
			}
			$lookUpFromFolderId = $folder['ID'];

			$relativeItems[] = array(
				'ID' => $folder['ID'],
				'NAME' => $pieceOfPath,
			);
		}
		unset($pieceOfPath);

		Diag::getInstance()->logDebugInfo('urlmanager');

		return array(
			'STORAGE' => $storage,
			'OBJECT_ID' => $lookUpFromFolderId,
			'RELATIVE_PATH' => implode('/', $partsOfPath),
			'RELATIVE_ITEMS' => $relativeItems,
		);
	}

	private function resolvePathToFolder(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, $storage->getRootObjectId());
		$data['FOLDER_ID'] = $data['OBJECT_ID'];
		unset($data['OBJECT_ID']);

		return $data;
	}

	private function resolvePathToFile(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, $storage->getRootObjectId(), FileTable::TYPE_FILE);
		$data['FILE_ID'] = $data['OBJECT_ID'];
		unset($data['OBJECT_ID']);

		return $data;
	}

	private function resolvePathToTrashFolder(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, null);
		$data['FOLDER_ID'] = $data['OBJECT_ID'];
		unset($data['OBJECT_ID']);

		return $data;
	}

	private function resolvePathToTrashFile(Storage $storage, $path)
	{
		$data = $this->resolvePath($storage, $path, null, FileTable::TYPE_FILE);
		$data['FILE_ID'] = $data['OBJECT_ID'];
		unset($data['OBJECT_ID']);

		return $data;
	}

	private function buildPathToDeletedObject(BaseObject $object)
	{
		if (!$object->isDeleted())
		{
			throw new InvalidOperationException('Object is not deleted');
		}

		if ($object->getDeletedType() == ObjectTable::DELETED_TYPE_ROOT)
		{
			return '';
		}

		$parentRows = ObjectTable::getParents($object->getId(), array(
			'select' => array(
				'ID', 'NAME', 'DELETED_TYPE',
			),
			'filter' => array(
				'!==DELETED_TYPE' => ObjectTable::DELETED_TYPE_NONE,
			),
		));

		$pathItems = array();
		foreach ($parentRows as $parentRow)
		{
			$pathItems[] = $parentRow['NAME'];
		}

		return implode('/', $pathItems) . '/';
	}
}