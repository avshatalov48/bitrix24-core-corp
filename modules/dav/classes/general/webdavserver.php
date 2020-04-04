<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Storage;

IncludeModuleLangFile(__FILE__);

class CDavWebDavServer
	extends CDavWebDav
{
	static $FORBIDDEN_SYMBOLS = array("/", "\\", ":", "*", "?", "\"", "'", "<", ">", "|", "#", "{", "}", "%", "&", "~", "+");
	static $ALLOWED_SYMBOLS = array("#", "+");

	public function __construct($request)
	{
		parent::__construct($request);

		if(defined('BX_HTTP_AUTH_REALM'))
			$realm = BX_HTTP_AUTH_REALM;
		else
			$realm = "Bitrix Site Manager";

		$this->SetDavPoweredBy($realm);
	}

	public function CheckAuth($authType, $phpAuthUser, $phpAuthPw)
	{
		global $APPLICATION, $USER;

		if (strlen($phpAuthUser) > 0 && strlen($phpAuthPw) > 0)
		{
			$arAuthResult = $USER->Login($phpAuthUser, $phpAuthPw, "N");
			$APPLICATION->arAuthResult = $arAuthResult;
		}

		return $USER->IsAuthorized();
	}

	protected function parsePath($requestUri)
	{
		static $storages;

		if (empty($storages))
		{
			$cache = new \CPHPCache();

			if ($cache->initCache(30*24*3600, 'webdav_disk_common_storage', '/webdav/storage'))
			{
				$storages = $cache->getVars();
			}
			else
			{
				$storages = \Bitrix\Disk\Storage::getModelList(array(
					'filter' => array(
						'=ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className(),
					)
				));

				foreach ($storages as $key => $storage)
				{
					$storages[$key] = array(
						'id'   => $storage->getEntityId(),
						'path' => $storage->getProxyType()->getStorageBaseUrl()
					);
				}

				$cache->startDataCache();

				if (defined('BX_COMP_MANAGED_CACHE'))
				{
					$taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();

					$taggedCache->startTagCache('/webdav/storage');
					$taggedCache->registerTag('disk_common_storage');
					$taggedCache->endTagCache();
				}

				$cache->endDataCache($storages);
			}
		}

		$patterns = array(
			array('user', '/(?:company|contacts)/personal/user/(\d+)/files/lib(.*)$'),
			array('user', '/(?:company|contacts)/personal/user/(\d+)/disk/path(.*)$'),
			array('group', '/workgroups/group/(\\d+)/files(.*)$'),
			array('group', '/workgroups/group/(\\d+)/disk/path(.*)$'),
		);

		foreach ($storages as $storage)
		{
			$storagePath = trim($storage['path'], '/');

			$patterns[] = array('docs', sprintf('^/%s/path(.*)$', $storagePath), $storage['id']);
			$patterns[] = array('docs', sprintf('^/%s(.*)$', $storagePath), $storage['id']);
		}

		// @TODO: aggregator
		$patterns[] = array('docs', '^/docs/path(.*)$', 'shared_files_s1');
		$patterns[] = array('docs', '^/docs(.*)$', 'shared_files_s1');

		$type = null;
		$id   = null;
		$path = null;
		foreach ($patterns as $pattern)
		{
			$matches = array();
			if (preg_match('#'.$pattern[1].'#i', $requestUri, $matches))
			{
				$type = $pattern[0];
				list($id, $path) = ($type == 'docs')
					? array($pattern[2], $matches[1])
					: array($matches[1], $matches[2]);

				break;
			}
		}

		/** @var Storage $storage */

		$storage = null;
		if ($type == 'user')
			$storage = Driver::getInstance()->getStorageByUserId((int)$id);
		elseif ($type == 'group')
			$storage = Driver::getInstance()->getStorageByGroupId((int)$id);
		elseif ($type == 'docs')
			$storage = Driver::getInstance()->getStorageByCommonId($id);
		else
			return array(null, null);

		$path = static::UrlDecode($path);

		return array($storage, $path);
	}

	private static function UrlDecode($t)
	{
		$t = rawurldecode($t);
		$t = str_replace("%20", " ", $t);
		if (preg_match("/^.{1}/su", $t) == 1 && SITE_CHARSET != "UTF-8")
		{
			$t = CharsetConverter::ConvertCharset($t, "UTF-8", SITE_CHARSET);
			if (preg_match("/^.{1}/su", $t) == 1 ) // IE
				$t = CharsetConverter::ConvertCharset($t, "UTF-8", SITE_CHARSET);
		}
		return $t;
	}

	public function UrlEncode($t)
	{
		$arPath = explode("/", $t);
		foreach ($arPath as $i => $sElm)
		{
			$arPath[$i] = rawurlencode($sElm);
		}
		return implode("/", $arPath);


		$params = (is_array($params) ? $params : array($params));
		$params["utf8"] = ($params["utf8"] == "N" ? "N" : "Y");
		$params["convert"] = (in_array($params["convert"], array("allowed", "full")) ? $params["convert"] : "allowed");

		if ($params["convert"] == "allowed")
		{
			foreach (static::$ALLOWED_SYMBOLS as $symbol)
			{
				$t = str_replace($symbol, urlencode($symbol), $t);
			}
		}
		else
		{
			if ($params["utf8"] == "Y" && SITE_CHARSET != "UTF-8")
			{
				$t = CharsetConverter::ConvertCharset($t, SITE_CHARSET, "UTF-8");
			}
			if ($params["urlencode"] != "N")
			{
				$t = str_replace(" ", "%20", $t);
				$t = urlencode($t);
				$t = str_replace(array("%2520", "%2F"), array("%20", "/"), $t);
			}
		}
		return $t;
	}

	/**
	 * @param array $arResources Returns resources by path
	 * @param string $method
	 * @return bool Returns false for CheckLock, string like '404 Not Found' for error, true for OK
	 */
	protected function PROPFIND(&$arResources, $method = 'PROPFIND')
	{
		$arResources = array();

		/** @var CDavRequest $request */
		$request = $this->request;
		$requestDocument = $request->GetXmlDocument();

		/** @var Storage $storage */
		list($storage, $path) = $this->parsePath($request->getPath());

		if (!$storage)
			return '404 Not Found';

//		try
//		{
			$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);
//		}
//		catch (Exception $e)
//		{}

		if(!$objectId)
		{
			return '404 Not Found';
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);

		if(!$object)
		{
			return '404 Not Found';
		}
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			return '403 Forbidden';
		}
		// И ФОРМИРУЕМ CDavResource
		// ЗАПИХИВАЯ В НЕГО ВСЕ ЕГО СВОЙСТВА
		// $resource->AddProperty('имя', 'значение' /*, 'xmlns', 'сырые данные'*/);
		// $resource->AddProperty('resourcetype', array('collection', ''));

		$arResources[] = $this->getResourceByObject($request->getPath(), $object);

		if ($request->GetDepth() && $object instanceof Folder)
		{
			// ВЫГРЕБАЕМ И ДОПИСЫВАЕМ В $arResources ДЕТЕЙ ПУТИ $path
			foreach($object->getChildren($securityContext) as $child)
			{
				/** @var File|Folder $child */
				$arResources[] = $this->getResourceByObject(rtrim($request->getPath(), '/') . '/' . $child->getName(), $child);
			}
			unset($child);
		}

		return true;
	}

	protected function PROPPATCH(&$arResources)
	{
		$arResources = array();

		/** @var CDavRequest $request */
		$request = $this->request;

		$path = $request->GetPath();
		$requestDocument = $request->GetXmlDocument();

//		CDav::Report(
//					"PROPPATCH",
//					print_r($requestDocument, 1),
//					"UNDEFINED",
//					true
//				);

		// ТУТА ПАТЧИМ ДОКУМЕНТ ПО ПУТЮ $path
		list($storage, $path) = $this->parsePath($request->getPath());

		if (!$storage)
			return '404 Not Found';

		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);

		if(!$objectId)
		{
			return '404 Not Found';
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if(!$object)
		{
			return '404 Not Found';
		}
		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			return '403 Forbidden';
		}
		if(!$object->canUpdate($securityContext))
		{
			return '403 Forbidden';
		}

		//todo Как я должен получить свойства, которые мне присылают?
//		if(isset($requestDocument['name']))
//		{
//			$object->rename($requestDocument['name']);
//		}

		//todo что значит 403? Которые не получилось обновить? А что с успешными?

		// И ФОРМИРУЕМ CDavResource
		$resource = new CDavResource($request->getPath());
		// ЗАПИХИВАЯ В НЕГО ВСЕ ЕГО СВОЙСТВА, КОТОРЫЕ '403 Forbidden'
		// $resource->AddProperty('имя', '', 'xmlns', '', '403 Forbidden');

		$arResources[] = $resource;

		return '';
	}

	protected function GET(&$arResult)
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$response = $this->response;

		$path = $request->GetPath();

		list($storage, $path) = $this->parsePath($request->getPath());
		
		if (!$storage)
			return '404 Not Found';

		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);

		if(!$objectId)
		{
			return '404 Not Found';
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if(!$object)
		{
			return '404 Not Found';
		}
		if(!$object->canRead($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return '403 Forbidden';
		}
		if($object instanceof Folder)
		{
			return '501 Not Implemented';
		}

		$fileArray = $object->getFile();

		$arResult["id"] = $objectId;
		$arResult["mimetype"] = $fileArray["CONTENT_TYPE"];
		$arResult["mtime"] = MakeTimeStamp($fileArray["TIMESTAMP_X"]);
		$arResult["size"] = $fileArray["FILE_SIZE"];
		$arResult["name"] = $object->getName();

		$arTmpFile = CFile::MakeFileArray($object->getFileId());
		if (!(is_array($arTmpFile) && is_set($arTmpFile, 'tmp_name')))
		{
			return false;
		}

		$io = CBXVirtualIo::GetInstance();
//		if(!empty($options['getContent']))
//		{
//			if(file_exists($io->GetPhysicalName($arTmpFile['tmp_name'])))
//			{
//				$options['content'] = $io->GetFile($io->GetPhysicalName($arTmpFile['tmp_name']))->GetContents();
//			}
//			elseif(file_exists($arTmpFile['tmp_name']))
//			{
//				$options['content'] = file_get_contents($arTmpFile['tmp_name']);
//			}
//			else
//			{
//				$options['content'] = null;
//			}
//		}
//		else
//		{
			if (file_exists($io->GetPhysicalName($arTmpFile['tmp_name'])))
			{
				$arResult['stream'] = fopen($io->GetPhysicalName($arTmpFile['tmp_name']), 'rb');
			}
			elseif(file_exists($arTmpFile['tmp_name']))
			{
				$arResult['stream'] = fopen($arTmpFile['tmp_name'], 'rb');
			}
			else
			{
				return false;
			}
//		}

		if (empty($arResult["mimetype"]) || $arResult["mimetype"] == "unknown" || $arResult["mimetype"] == "application/octet-stream")
		{
//			$arResult["mimetype"] = $this->get_mime_type($object->getName());
		}
		return true;
	}

	private function RenderPropertyValue($value)
	{
		if (is_array($value))
		{
			$request = $this->request;
			$response = $this->response;

			if (isset($value[0]['ns']))
				$value = CDavResource::EncodeHierarchicalProp($value, null, $xmlnsDefs = null, $xmlnsHash = null, $response, $request);

			$value = htmlspecialcharsbx(CDav::ToString($value));
		}
		elseif (preg_match('/\<(D:)?href\>[^<]+\<\/(D:)?href\>/i', $value))
		{
			$value = preg_replace('/\<(D:)?href\>([^<]+)\<\/(D:)?href\>/i','&lt;\\1href&gt;<a href="\\2">\\2</a>&lt;/\\3href&gt;<br />', $value);
		}
		else
		{
			$value = htmlspecialcharsbx($value);
		}
		return $value;
	}

	private function ConvertPropertiesToArray(array $props)
	{
		$request = $this->request;
		$response = $this->response;

		$arr = array();
		foreach ($props as $prop)
		{
			switch ($prop['xmlns'])
			{
				case 'DAV:';
					$xmlns = 'DAV';
					break;
				default:
					$xmlns = $prop['xmlns'];
			}

			$xmlnsDefs = '';
			$xmlnsHash = array($prop['xmlns'] => $xmlns, 'DAV:' => 'D');
			$arr[$xmlns.':'.$prop['tagname']] = is_array($prop['content']) ? CDavResource::EncodeHierarchicalProp($prop['content'], $prop['xmlns'], $xmlnsDefs, $xmlnsHash, $response, $request) : $prop['content'];
		}

		return $arr;
	}

	protected function POST()
	{
		return '501 Not Implemented';
	}

	protected function getNewLockToken()
	{
		$uuid = '';
		if (function_exists('uuid_create'))
		{
			$uuid = uuid_create();
		}
		else
		{
			$uuid = md5(microtime().getmypid());

			$uuid{12} = '4';
			$n = 8 + (ord($uuid{16}) & 3);
			$hex = '0123456789abcdef';
			$uuid{16} = substr($hex, $n, 1);

			$uuid = substr($uuid,  0, 8).'-'.
				substr($uuid,  8, 4).'-'.
				substr($uuid, 12, 4).'-'.
				substr($uuid, 16, 4).'-'.
				substr($uuid, 20);
		}

		return 'opaquelocktoken:'.$uuid;
	}

	/**
	 * @param array $arResult From PUT(&$arResult)
	 * @return string String like '204 No Content', '403 Forbidden', '404 Not Found' or file pointer if we have to load file
	 */
	protected function PUT(&$arResult)
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		list($storage, $path) = $this->parsePath($request->getPath());

		if (!$storage)
			return '404 Not Found';

		$withoutFilename = explode('/', $path);
		$filename = array_pop($withoutFilename);
		$folderId = Driver::getInstance()->getUrlManager()->resolveFolderIdFromPath($storage, implode('/', $withoutFilename));

		if(!$folderId)
		{
			return '404 Not Found'; //"409 Conflict"?
		}
		/** @var Folder $folder */
		$folder = Folder::loadById($folderId);
		if(!$folder)
		{
			return '404 Not Found'; //"409 Conflict"?
		}
		/** @var File $file */

		$file = File::load(array('NAME' => $filename, 'PARENT_ID' => $folder->getRealObjectId()));

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if(!$file)
		{
			if(!$folder->canAdd($securityContext))
			{
				return '403 Forbidden';
			}

			$tmpFile = CTempFile::GetFileName($filename);

			CheckDirPath($tmpFile);
			$fp = fopen($tmpFile, "w");

			$arResult['new'] = true;
			$arResult['filename'] = $filename;
			$arResult['tmpFile'] = $tmpFile;
			$arResult['targetFolder'] = $folder;

			return $fp;
		}

		$arResult['new'] = false;
		if(!$file->canUpdate($securityContext))
		{
			return '403 Forbidden';
		}

		$tmpFile = CTempFile::GetFileName($filename);
		CheckDirPath($tmpFile);
		$fp = fopen($tmpFile, "w");

		$arResult['tmpFile'] = $tmpFile;
		$arResult['targetFolder'] = $folder;
		$arResult['file'] = $file;

		return $fp;
	}

	/**
	 * @param array $arResult From PUT(&$arResult)
	 * @return bool
	 */
	protected function PutCommit($arResult)
	{
		$folder = $arResult['targetFolder'];
		$fileArray = CFile::MakeFileArray($arResult['tmpFile']);

		if(!$fileArray)
		{
			return false;
		}
		if($arResult['new'])
		{
			/** @var Folder $folder */
			$file = $folder->uploadFile($fileArray, array('NAME' => $arResult['filename'], 'CREATED_BY' => $this->getUser()->getId()));

			if(!$file)
			{
				return false;
			}
			return true;
		}
		/** @var File $file */
		$file = $arResult['file'];

		return $file->uploadVersion($fileArray, $this->getUser()->getId()) !== null;
	}

	protected function DELETE()
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		list($storage, $path) = $this->parsePath($request->getPath());

		if (!$storage)
			return '404 Not Found';

		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);

		if(!$objectId)
		{
			return '404 Not Found'; //todo 400 Bad Request?
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if(!$object)
		{
			return '404 Not Found';//todo 400 Bad Request?
		}
		if(!$object->canMarkDeleted($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return '403 Forbidden';//todo 400 Bad Request?
		}
		if($object->markDeleted($this->getUser()->getId()))
		{
			return '204 No Content';
		}

		return '400 Bad Request'; // '400 Something went wrong', '501 Not Implemented'
	}

	protected function MKCOL()
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		/** @var Storage $storage */
		list($storage, $path) = $this->parsePath($request->getPath());
		//todo?

		if (!$storage)
			return '404 Not Found';

		$withoutFolderName = explode('/', rtrim($path, '/'));
		$folderName = array_pop($withoutFolderName);
		$folderId = Driver::getInstance()->getUrlManager()->resolveFolderIdFromPath($storage, implode('/', $withoutFolderName));

		if(!$folderId)
		{
			return '409 Conflict';
		}
		/** @var Folder $folder */

		$folder = Folder::loadById($folderId);
		if(!$folder)
		{
			return '409 Conflict';
		}
		if(!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
		{
			return '403 Forbidden';
		}

		try
		{
			$subFolder = $folder->addSubFolder(array('NAME' => $folderName, 'CREATED_BY' => $this->getUser()->getId()));
		}
		catch (Exception $e)
		{
		}

		if(!$subFolder)
		{
			return '409 Conflict';
		}

		$this->response->AddHeader('Content-length: 0');
		$this->response->AddHeader('Location: ' . ($request->GetParameter("HTTPS") === "on" ? "https" : "http").'://'.$request->GetParameter('HTTP_HOST').$request->getPath());

		return "201 Created";
	}

	protected function MOVE($dest, $httpDestination, $overwrite)
	{
		return $this->COPY($dest, $httpDestination, $overwrite, true);
	}

	protected function COPY($dest, $httpDestination, $overwrite, $delete = false)
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		$v = $request->GetParameter('CONTENT_LENGTH');
		if (!empty($v))
		{
			return "415 Unsupported media type";
		}

		//if (isset($httpDestination))
		//{
		//	return "502 bad gateway";
		//}

		$requestDocument = $request->GetXmlDocument();

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		/** @var Storage $storage */
		list($storage, $path) = $this->parsePath($request->getPath());
		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);
		if(!$objectId)
		{
			return '404 Not Found';
		}

		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if(!$object)
		{
			return '404 Not Found';
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if(!$object->canRead($securityContext))
		{
			return '403 Forbidden';
		}

		list($destStorage, $poludest) = $this->parsePath($dest);
		if(!$destStorage)
		{
			return '404 Not Found';
		}

		$srcPath = GetDirPath(rtrim($path, '/'));
		$destPath = GetDirPath($poludest);
		if ($srcPath == $destPath)
		{
			if (!$object->canRename($securityContext))
			{
				return '403 Forbidden';
			}

			if (!$object->rename(GetFileName($poludest)))
			{
				return '400 Bad Request';
			}

			return "201 Created";
		}

//		$ret = $this->createFolderPath($destStorage, $poludest);
//		if ($ret !== true)
//			return $ret;

		$poludestExploded = explode('/', $poludest);
		$poludestFolderName = array_pop($poludestExploded);

		$targetObjectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($destStorage, implode('/', $poludestExploded));
		if(!$targetObjectId)
		{
			return '404 Not Found';
		}

		/** @var File|Folder $folder */
		$folder = Folder::loadById($targetObjectId);
		if(!$targetObjectId)
		{
			return '404 Not Found';
		}

		if($delete)
		{
			if(!$object->canMove($securityContext, $folder))
			{
				return '403 Forbidden';
			}
		}
		else
		{
			if(!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
			{
				return '403 Forbidden';
			}
		}

		$opponent = false;
		if($overwrite)
		{
			$opponent = BaseObject::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'NAME' => GetFileName($poludest),
					'PARENT_ID' => $folder->getRealObjectId(),
				),
				'limit' => 1
			))->fetch();

			if($opponent)
			{
				/** @var File|Folder $opponentObject */
				$opponentObject = BaseObject::loadById($opponent['ID']);
				if(!$opponentObject->canMarkDeleted($opponentObject->getStorage()->getCurrentUserSecurityContext()))
				{
					return '403 Forbidden';
				}
				if(!$opponentObject->markDeleted($this->getUser()->getId()))
				{
					return '400 Bad Request';
				}
			}
		}

		if($delete)
		{
			if (!$object->moveTo($folder, $this->getUser()->getId(), true))
			{
				return '400 Bad Request';
			}
		}
		else
		{
			if (!$object->copyTo($folder, $this->getUser()->getId(), true))
			{
				return '400 Bad Request';
			}
		}

		if (GetFileName($poludest) != $object->getName())
		{
			$object->rename(GetFileName($poludest));
		}

		return $opponent ? "201 Created" : "204 No Content";
	}

	/**
	 * @param Storage $storage
	 * @param string $path
	 */
	private function createFolderPath($storage, $path)
	{
		$path = trim($path, '/');
		if ($path == '')
			return true;

		$foldersPath = explode('/', $path);

		$urlManager = Driver::getInstance()->getUrlManager();
		/** @var Folder $folder */
		$folderId = $urlManager->resolveFolderIdFromPath($storage, '/');
		$folder = Folder::loadById($folderId);

		$s = '';
		while (!empty($foldersPath))
		{
			$subFolderName = array_shift($foldersPath);
			$s .= '/'.$subFolderName;
			$folderId = $urlManager->resolveFolderIdFromPath($storage, $s);
			if ($folderId)
			{
				$folder = Folder::loadById($folderId);
				if (!$folder)
				{
					return '409 Conflict';
				}
			}
			else
			{
				if (!$folder->canAdd($storage->getCurrentUserSecurityContext()))
				{
					return '403 Forbidden';
				}
				$folder = $folder->addSubFolder(array('NAME' => $subFolderName, 'CREATED_BY' => $this->getUser()->getId()));
			}
		}

		return true;
	}

	protected function LOCK($locktoken, &$httpTimeout, &$owner, &$scope, &$type, $update)
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$arRequestPath = self::ParsePath($request->GetPath());

		if (!$arRequestPath[0])
		{
			return '409 Conflict';
		}

		$path = CDavVirtualFileSystem::GetLockPath("WS".($arRequestPath[0]->getId()), $arRequestPath[1]);

//		if (!$arRequestPath["id"] || $request->GetDepth() || !$handler->CheckPrivilegesByPath("DAV:write", $request->GetPrincipal(), $arRequestPath["site"], $arRequestPath["account"], $arRequestPath["path"]))
//			return '409 Conflict';

		$httpTimeout = time() + 300;

		if (!$update)
		{
			$ret = CDavVirtualFileSystem::Lock($path, $locktoken, $httpTimeout, $owner, $scope, $type);
			return $ret ? '200 OK' : '409 Conflict';
		}

		$ret = CDavVirtualFileSystem::UpdateLock($path, $locktoken, $httpTimeout, $owner, $scope, $type);
		return $ret;
	}

	protected function UNLOCK($httpLocktoken)
	{
		/** @var CDavRequest $request */
		$request = $this->request;

		$arRequestPath = self::ParsePath($request->GetPath());
		if (!$arRequestPath[0])
			return '409 Conflict';

		$path = CDavVirtualFileSystem::GetLockPath("WS".($arRequestPath[0]->getId()), $arRequestPath[1]);

		return (CDavVirtualFileSystem::Unlock($path, $httpLocktoken) ? '204 No Content' : '409 Conflict');
	}

	protected function CheckLock($path)
	{
		/** @var Storage $storage */
		list($storage, $path) = $this->parsePath($path);
		if (!$storage)
			return false;

		$path = CDavVirtualFileSystem::GetLockPath("WS".($storage->getId()), $path);

		return CDavVirtualFileSystem::CheckLock($path);
	}

	/**
	 * @param $path
	 * @param File|Folder|Object $object
	 * @return CDavResource
	 */
	protected function getResourceByObject($path, BaseObject $object)
	{
		$isFolder = $object instanceof Folder;
		$resource = new CDavResource($path.($isFolder && substr($path, -1, 1) != "/" ? "/" : ""));
		$resource->AddProperty('name', $object->getName());
		if ($object instanceof File)
			$resource->AddProperty('getcontentlength', $object->getSize());
		$resource->AddProperty('creationdate', $object->getCreateTime()->getTimestamp());
		$resource->AddProperty('getlastmodified', $object->getUpdateTime()->getTimestamp());
		$resource->AddProperty('iscollection', $isFolder? '1' : '0');

		if($isFolder)
		{
			$resource->AddProperty('resourcetype', array('collection', ''));
			$resource->AddProperty('getcontenttype', 'httpd/unix-directory');
		}
		else
		{
			$resource->AddProperty('getcontenttype', '');
			$resource->AddProperty('isreadonly', '');
			$resource->AddProperty('ishidden', '');
			$resource->AddProperty('resourcetype', '');
		}

		$resource->AddProperty("supportedlock",
			"<D:lockentry><D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry><D:lockentry><D:lockscope><D:shared/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry>"
		);

		return $resource;
	}

	/**
	 * @return array|bool|\CAllUser|\CUser
	 */
	protected function getUser()
	{
		global $USER;
		return $USER;
	}

} 