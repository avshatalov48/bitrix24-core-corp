<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Storage;
use Bitrix\Disk\Security\DiskSecurityContext;
use Bitrix\Disk\Security\FakeSecurityContext;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\User;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class CDavWebDavServer
	extends CDavWebDav
{
	static $FORBIDDEN_SYMBOLS = array("/", "\\", ":", "*", "?", "\"", "'", "<", ">", "|", "#", "{", "}", "%", "&", "~", "+");
	static $ALLOWED_SYMBOLS = array("#", "+");
	private $titleGroupStoragesQuote = '';
	private $titleUserStoragesQuote = '';

	public function __construct($request)
	{
		parent::__construct($request);

		if(defined('BX_HTTP_AUTH_REALM'))
			$realm = BX_HTTP_AUTH_REALM;
		else
			$realm = "Bitrix Site Manager";

		$this->SetDavPoweredBy($realm);

		$this->titleGroupStoragesQuote = preg_quote(Loc::getMessage('DAV_GROUP_STORAGES'), '#');
		$this->titleUserStoragesQuote = preg_quote(Loc::getMessage('DAV_USER_STORAGES'), '#');
	}

	public function CheckAuth($authType, $phpAuthUser, $phpAuthPw)
	{
		global $APPLICATION, $USER;

		if ($phpAuthUser <> '' && $phpAuthPw <> '')
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
			$patterns[] = array('docs', sprintf('^/%s/(.*)$', $storagePath), $storage['id']);
		}

		// @TODO: aggregator
		$patterns[] = array('all', '^(/docs/path/[^/]+)(/.*)', 'all');
		$patterns[] = array('all', '^(/docs/'.$this->titleGroupStoragesQuote.'/[^/]+)(/.*)', 'all');
		$patterns[] = array('all', '^(/docs/'.$this->titleUserStoragesQuote.'/[^/]+)(/.*)', 'all');
		$patterns[] = array('all', '^(/docs/[^/]+)(/.*)', 'all');

		$type = null;
		$id   = null;
		$path = null;
		foreach ($patterns as $pattern)
		{
			$matches = array();
			if (preg_match('#'.$pattern[1].'#i', $requestUri, $matches))
			{
				$type = $pattern[0];
				if ($type === 'all')
				{
					$storage = null;
					$storageId = $this->getStorageId($matches[1]);
					$storage = Storage::loadById($storageId);

					if (!$storage)
					{
						return [null, null];
					}

					$id = $storage->getEntityId();
					$path = $matches[2];
					$type = $this->getEntityType($storage);

					break;
				}

				list($id, $path) = ($type === 'docs')
					? array($pattern[2], $matches[1])
					: array($matches[1], $matches[2]);
				break;
			}
		}
		/** @var Storage $storage */

		$storage = null;
		if ($type === 'user')
		{
			$storage = Driver::getInstance()->getStorageByUserId((int)$id);
		}
		elseif ($type === 'group')
		{
			$storage = Driver::getInstance()->getStorageByGroupId((int)$id);
		}
		elseif ($type === 'docs' || $type === 'common')
		{
			$storage = Driver::getInstance()->getStorageByCommonId($id);
		}
		else
		{
			return [null, null];
		}

		$path = $path ?: '/';
		$path = static::UrlDecode($path);

		return array($storage, $path);
	}

	private static function UrlDecode($t)
	{
		$t = rawurldecode($t);
		$t = str_replace("%20", " ", $t);
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
		$requestPath = $request->getPath();

		if ($this->fillSystemStorage($arResources, $requestPath))
		{
			return true;
		}

		if ($matches = $this->isStorage($requestPath))
		{
			$storage = Storage::loadById(array_pop($matches));

			if (!$storage)
			{
				return '404 Not Found';
			}

			$objectId = $storage->getRootObjectId();
		}
		else
		{
			list($storage, $path) = $this->parsePath($requestPath);

			if (!$storage)
			{
				return '404 Not Found';
			}

			$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);
		}

		if (!$objectId)
		{
			return '404 Not Found';
		}

		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);

		if (!$object)
		{
			return '404 Not Found';
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();

		if (!$object->canRead($securityContext))
		{
			return '403 Forbidden';
		}
		// И ФОРМИРУЕМ CDavResource
		// ЗАПИХИВАЯ В НЕГО ВСЕ ЕГО СВОЙСТВА
		// $resource->AddProperty('имя', 'значение' /*, 'xmlns', 'сырые данные'*/);
		// $resource->AddProperty('resourcetype', array('collection', ''));

		$arResources[] = $this->getResourceByObject($requestPath, $object);

		if ($request->GetDepth() && $object instanceof Folder)
		{
			// ВЫГРЕБАЕМ И ДОПИСЫВАЕМ В $arResources ДЕТЕЙ ПУТИ $path
			foreach ($object->getChildren($securityContext) as $child)
			{
				/** @var File|Folder $child */
				$arResources[] = $this->getResourceByObject(rtrim($requestPath, '/') . '/' . $child->getName(), $child);
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
		$requestPath = $request->getPath();
		if ($this->isSystemStorageElement($requestPath))
		{
			return '403 Forbidden';
		}

		$path = $request->GetPath();
		$requestDocument = $request->GetXmlDocument();

//		CDav::Report(
//					"PROPPATCH",
//					print_r($requestDocument, 1),
//					"UNDEFINED",
//					true
//				);

		// ТУТА ПАТЧИМ ДОКУМЕНТ ПО ПУТЮ $path
		list($storage, $path) = $this->parsePath($requestPath);

		if (!$storage)
			return '404 Not Found';

		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);

		if (!$objectId)
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
		if (!$object->canRead($securityContext))
		{
			return '403 Forbidden';
		}
		if (!$object->canUpdate($securityContext))
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
		$resource = new CDavResource($requestPath);
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
		$requestPath = $request->getPath();

		$path = $request->GetPath();

		list($storage, $path) = $this->parsePath($requestPath);

		if (!$storage)
			return '404 Not Found';

		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);

		if (!$objectId)
		{
			return '404 Not Found';
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if (!$object)
		{
			return '404 Not Found';
		}
		if (!$object->canRead($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return '403 Forbidden';
		}
		if ($object instanceof Folder)
		{
			return '501 Not Implemented';
		}

		$fileArray = $object->getFile();
		if (!$fileArray)
		{
			return '404 Not Found';
		}

		$response = \Bitrix\Main\Engine\Response\BFile::createByFileId($object->getFileId(), $object->getName());
		\Bitrix\Main\Application::getInstance()->end(0, $response);
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
			$value = preg_replace('/\<(D:)?href\>([^<]+)\<\/(D:)?href\>/i', '&lt;\\1href&gt;<a href="\\2">\\2</a>&lt;/\\3href&gt;<br />', $value);
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
			$arr[$xmlns . ':' . $prop['tagname']] = is_array($prop['content']) ? CDavResource::EncodeHierarchicalProp($prop['content'], $prop['xmlns'], $xmlnsDefs, $xmlnsHash, $response, $request) : $prop['content'];
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

			$uuid[12] = '4';
			$n = 8 + (ord($uuid[16]) & 3);
			$hex = '0123456789abcdef';
			$uuid[16] = mb_substr($hex, $n, 1);

			$uuid = mb_substr($uuid, 0, 8).'-'.
				mb_substr($uuid, 8, 4).'-'.
				mb_substr($uuid, 12, 4).'-'.
				mb_substr($uuid, 16, 4).'-'.
				mb_substr($uuid, 20);
		}

		return 'opaquelocktoken:' . $uuid;
	}

	/**
	 * @param array $arResult From PUT(&$arResult)
	 *
	 * @return string String like '204 No Content', '403 Forbidden', '404 Not Found' or file pointer if we have to load file
	 */
	protected function PUT(&$arResult)
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$requestPath = $request->getPath();

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		list($storage, $path) = $this->parsePath($requestPath);

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

		$file = File::load([
			'=NAME' => $filename,
			'PARENT_ID' => $folder->getRealObjectId(),
		]);

		$securityContext = $folder->getStorage()->getCurrentUserSecurityContext();
		if (!$file)
		{
			if (!$folder->canAdd($securityContext))
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
		if (!$file->canUpdate($securityContext))
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
	 *
	 * @return bool
	 */
	protected function PutCommit($arResult)
	{
		$folder = $arResult['targetFolder'];
		$fileArray = CFile::MakeFileArray($arResult['tmpFile']);

		if (!$fileArray)
		{
			return false;
		}
		if ($arResult['new'])
		{
			/** @var Folder $folder */
			$file = $folder->uploadFile($fileArray, array('NAME' => $arResult['filename'], 'CREATED_BY' => $this->getUser()->getId()));

			if (!$file)
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
		$requestPath = $request->getPath();

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		list($storage, $path) = $this->parsePath($requestPath);

		if (!$storage)
			return '404 Not Found';

		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);

		if (!$objectId)
		{
			return '404 Not Found'; //todo 400 Bad Request?
		}
		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if (!$object)
		{
			return '404 Not Found';//todo 400 Bad Request?
		}
		if (!$object->canMarkDeleted($object->getStorage()->getCurrentUserSecurityContext()))
		{
			return '403 Forbidden';//todo 400 Bad Request?
		}
		if ($object->markDeleted($this->getUser()->getId()))
		{
			return '204 No Content';
		}

		return '400 Bad Request'; // '400 Something went wrong', '501 Not Implemented'
	}

	protected function MKCOL()
	{
		/** @var CDavRequest $request */
		$request = $this->request;
		$requestPath = $request->getPath();
		if ($this->isSystemStorageElement($requestPath))
		{
			return '403 Forbidden';
		}

		//todo откуда мы узнаем хранилище относительно которого вести поиск?
		/** @var Storage $storage */
		list($storage, $path) = $this->parsePath($requestPath);
		//todo?

		if (!$storage)
			return '404 Not Found';

		$withoutFolderName = explode('/', rtrim($path, '/'));
		$folderName = array_pop($withoutFolderName);
		$folderId = Driver::getInstance()->getUrlManager()->resolveFolderIdFromPath($storage, implode('/', $withoutFolderName));

		if (!$folderId)
		{
			return '409 Conflict';
		}
		/** @var Folder $folder */

		$folder = Folder::loadById($folderId);
		if (!$folder)
		{
			return '409 Conflict';
		}
		if (!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
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

		if (!$subFolder)
		{
			return '409 Conflict';
		}

		$this->response->AddHeader('Content-length: 0');
		$this->response->AddHeader('Location: ' . ($request->GetParameter("HTTPS") === "on" ? "https" : "http") . '://' . $request->GetParameter('HTTP_HOST') . $requestPath);

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
		$requestPath = $request->getPath();
		if ($this->isSystemStorageElement($requestPath))
		{
			return '403 Forbidden';
		}

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
		list($storage, $path) = $this->parsePath($requestPath);
		$objectId = Driver::getInstance()->getUrlManager()->resolveObjectIdFromPath($storage, $path);
		if (!$objectId)
		{
			return '404 Not Found';
		}

		/** @var File|Folder $object */
		$object = BaseObject::loadById($objectId);
		if (!$object)
		{
			return '404 Not Found';
		}

		$securityContext = $object->getStorage()->getCurrentUserSecurityContext();
		if (!$object->canRead($securityContext))
		{
			return '403 Forbidden';
		}

		list($destStorage, $poludest) = $this->parsePath($dest);
		if (!$destStorage)
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
		if (!$targetObjectId)
		{
			return '404 Not Found';
		}

		/** @var File|Folder $folder */
		$folder = Folder::loadById($targetObjectId);
		if (!$targetObjectId)
		{
			return '404 Not Found';
		}

		if ($delete)
		{
			if (!$object->canMove($securityContext, $folder))
			{
				return '403 Forbidden';
			}
		}
		else
		{
			if (!$folder->canAdd($folder->getStorage()->getCurrentUserSecurityContext()))
			{
				return '403 Forbidden';
			}
		}

		$opponent = false;
		if ($overwrite)
		{
			$opponent = BaseObject::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'NAME' => GetFileName($poludest),
					'PARENT_ID' => $folder->getRealObjectId(),
				),
				'limit' => 1
			))->fetch();

			if ($opponent)
				{
				/** @var File|Folder $opponentObject */
				$opponentObject = BaseObject::loadById($opponent['ID']);
				if (!$opponentObject->canMarkDeleted($opponentObject->getStorage()->getCurrentUserSecurityContext()))
				{
					return '403 Forbidden';
				}
				if (!$opponentObject->markDeleted($this->getUser()->getId()))
				{
					return '400 Bad Request';
				}
			}
		}

		if ($delete)
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
	 * @param string  $path
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
			$s .= '/' . $subFolderName;
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
		$requestPath = $request->getPath();

		list($storage, $path) = self::ParsePath($requestPath);

		if (!$storage)
		{
			return '409 Conflict';
		}

		$path = CDavVirtualFileSystem::GetLockPath("WS" . ($storage->getId()), $path);

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
		$requestPath = $request->getPath();

		list($storage, $path) = self::ParsePath($requestPath);

		if (!$storage)
		{
			return '409 Conflict';
		}

		$path = CDavVirtualFileSystem::GetLockPath("WS" . ($storage->getId()), $path);

		return (CDavVirtualFileSystem::Unlock($path, $httpLocktoken) ? '204 No Content' : '409 Conflict');
	}

	protected function CheckLock($path)
	{
		/** @var Storage $storage */
		list($storage, $path) = $this->parsePath($path);
		if (!$storage)
			return false;

		$path = CDavVirtualFileSystem::GetLockPath("WS" . ($storage->getId()), $path);

		return CDavVirtualFileSystem::CheckLock($path);
	}

	/**
	 * @param                    $path
	 * @param File|Folder|Object $object
	 *
	 * @return CDavResource
	 */
	protected function getResourceByObject($path, BaseObject $object)
	{
		$isFolder = $object instanceof Folder;
		$resource = new CDavResource($path . ($isFolder && mb_substr($path, -1, 1) != "/" ? "/" : ""));

		$resource->AddProperty('name', $object->getName());

		if ($object instanceof File)
			$resource->AddProperty('getcontentlength', $object->getSize());
		$resource->AddProperty('creationdate', $object->getCreateTime()->getTimestamp());
		$resource->AddProperty('getlastmodified', $object->getUpdateTime()->getTimestamp());
		$resource->AddProperty('iscollection', $isFolder ? '1' : '0');

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

	private function isSystemStorageElement($requestPath)
	{
		if (preg_match('#^(/docs/[^/]+/?)$#', $requestPath)
			|| preg_match('#^(/docs/'.$this->titleGroupStoragesQuote.'/[^/]+/?)$#', $requestPath)
			|| preg_match('#^(/docs/'.$this->titleUserStoragesQuote.'/[^/]+/?)$#', $requestPath))
		{
			return true;
		}

		return false;
	}

	private function fillSystemStorage(&$arResources, $requestPath)
	{
		if ($requestPath === '/docs/')
		{
			$arResources[] = $this->getResourceObjectRoot("");
			if ($this->request->GetDepth())
			{
				$arResources[] = $this->getResourceObjectRoot(Loc::getMessage('DAV_USER_STORAGES'));
				$arResources[] = $this->getResourceObjectRoot(Loc::getMessage('DAV_GROUP_STORAGES'));

				$this->fillResourceRootStorages(ProxyType\Common::class, $arResources, $requestPath);
			}

			return true;
		}
		elseif (preg_match('#^(/docs/'.$this->titleUserStoragesQuote.'/?)$#', $requestPath))
		{
			if ($this->request->GetDepth())
			{
				$this->fillResourceRootStorages(ProxyType\User::class, $arResources, $requestPath);
			}
			return true;
		}
		elseif (preg_match('#^(/docs/'.$this->titleGroupStoragesQuote.'/?)$#', $requestPath))
		{
			if ($this->request->GetDepth())
			{
				$this->fillResourceRootStorages(ProxyType\Group::class, $arResources, $requestPath);
			}
			return true;
		}

		return false;
	}

	private function isStorage($requestPath)
	{
		if (preg_match('#^(/docs/[^/]+\[(\d+)\]/?)$#', $requestPath, $matches)
		|| preg_match('#^(/docs/' . $this->titleUserStoragesQuote . '/[^/]+\[(\d+)\]/?)$#', $requestPath, $matches)
		|| preg_match('#^(/docs/' . $this->titleGroupStoragesQuote . '/[^/]+\[(\d+)\]/?)$#', $requestPath, $matches))
		{
			return $matches;
		}

		return false;
	}

	private function getStorageId($path)
	{
		$path = rtrim($path, "/");
		preg_match("#\[(\d+)\]$#", $path, $match);

		return array_pop($match);
	}

	protected function getResourceObjectRoot($path)
	{
		$resource = new CDavResource('/docs/' . $path. '/');

		$resource->AddProperty('iscollection', '1');
		$resource->AddProperty('resourcetype', array('collection', ''));
		$resource->AddProperty('getcontenttype', 'httpd/unix-directory');

		$resource->AddProperty("supportedlock",
			"<D:lockentry><D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry><D:lockentry><D:lockscope><D:shared/></D:lockscope><D:locktype><D:write/></D:locktype></D:lockentry>"
		);

		return $resource;
	}

	private function fillResourceRootStorages ($entityType, &$arResources, $requestPath)
	{
		$storages = $this->getStoragesByEntityType($entityType, $requestPath);

		foreach ($storages as $objectId => $item)
		{
			$storageId = $this->getStorageId($item['URL']);
			$storage = Storage::loadById($storageId);
			$object = $storage->getRootObject();

			if (!$object)
			{
				continue;
			}

			$securityContext = $object->getStorage()->getCurrentUserSecurityContext();

			if (!$object->canRead($securityContext))
			{
				continue;
			}

			$arResources[] = $this->getResourceByObject($item['URL'], $object);
		}

		return true;
	}

	private function getStoragesByEntityType($entityType, $path)
	{
		$diskSecurityContext = $this->getSecurityContextByUser($this->getUser());
		$filterReadableList = array('=STORAGE.ENTITY_TYPE' => $entityType);

		$storages = [];

		foreach (Storage::getReadableList($diskSecurityContext, array('filter' => $filterReadableList)) as $storage)
		{
			$proxyType = $storage->getProxyType();
			$url = $path . $proxyType->getEntityTitle() . ' [' . $storage->getId() . ']/';
			$storages[$storage->getRootObjectId()] = array(
				"TITLE" => $proxyType->getEntityTitle(),
				"URL" => $url,
			);
		}

		return $storages;
	}

	private function getEntityType(Storage $storage)
	{
		if ($storage->getProxyType() instanceof \Bitrix\Disk\ProxyType\User)
		{
			return 'user';
		}
		if ($storage->getProxyType() instanceof \Bitrix\Disk\ProxyType\Common)
		{
			return 'common';
		}
		if ($storage->getProxyType() instanceof \Bitrix\Disk\ProxyType\Group)
		{
			return 'group';
		}

		return 'notype';
	}

	private function getSecurityContextByUser($user)
	{
		$diskSecurityContext = new DiskSecurityContext($user);

		if (Loader::includeModule('socialnetwork'))
		{
			if (\CSocnetUser::isCurrentUserModuleAdmin())
			{
				$diskSecurityContext = new FakeSecurityContext($user);
			}
		}

		if (User::isCurrentUserAdmin())
		{
			$diskSecurityContext = new FakeSecurityContext($user);
		}

		return $diskSecurityContext;
	}

	private function countQuotaSizeStorage(Storage $storage)
	{
		$quotaSize = [];
		$fullSize = 0;

		$restriction = $storage->isEnabledSizeLimitRestriction();
		$usedStorageSize = $storage->getRootObject()->countSizeOfVersions();

		if ($restriction)
		{
			$fullSize = $storage->getSizeLimit();
			$usedSize = $usedStorageSize;
		}
		else
		{
			$userId = $this->getUser()->getId();
			$fullSize = \Bitrix\Main\Config\Option::get('main', 'disk_space', 0) * 1048576;
			$indicator = new \Bitrix\Disk\Volume\Bfile();
			$diskInfo = $indicator->setOwner($userId)->purify()->measure()->loadTotals();
			$usedSize = $diskInfo ? $diskInfo["FILE_SIZE"] : 0;
		}

		if ($fullSize > 0)
		{
			$quotaSize['used_size'] = $usedStorageSize ? $usedStorageSize : 0;
			$quotaSize['available_size'] = $fullSize - $usedSize;

			return $quotaSize;
		}
		else
		{
			return null;
		}
	}

	private function countQuotaSizeDisk()
	{
		$quotaSize = array();
		$fullSize = 0;

		$userId = $this->getUser()->getId();
		$fullSize = \Bitrix\Main\Config\Option::get('main', 'disk_space', 0) * 1048576;
		$indicator = new \Bitrix\Disk\Volume\Bfile();
		$diskInfo = $indicator->setOwner($userId)->purify()->measure()->loadTotals();
		$usedSize = $diskInfo ? $diskInfo["FILE_SIZE"] : 0;
		$quotaSize['used_size'] = $usedSize;
		$quotaSize['available_size'] = $fullSize - $usedSize;

		return $fullSize > 0 ? $quotaSize : null;
	}

	/**
	 * @return array|bool|\CUser
	 */
	protected function getUser()
	{
		global $USER;
		return $USER;
	}

}
