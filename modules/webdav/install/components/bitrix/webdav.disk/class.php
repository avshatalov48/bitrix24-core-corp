<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//error_reporting(E_ALL);
class CWebDavDiskComponent extends CBitrixComponent
{
	/** @var CWebDavDiskDispatcher|null */
	protected $dispatcher = null;

	/**
	 * @return \CWebDavDiskDispatcher|null
	 */
	public function getDispatcher()
	{
		if($this->dispatcher === null)
		{
			$this->dispatcher = new CWebDavDiskDispatcher();
		}
		return $this->dispatcher;
	}

	/**
	 * @return bool
	 */
	public function checkSession()
	{
		return check_bitrix_sessid();
	}

	/**
	 * @deprecated
	 */
	public static function __enableTimeZone()
	{
		CTimeZone::Enable();
	}

	public function sendJsonResponse($response, $httpStatusCode = null)
	{
		CWebDavTools::sendJsonResponse($response, $httpStatusCode, array($this, '__enableTimeZone'));
	}

	/**
	 * @return CAllUser
	 */
	protected function getUser()
	{
		global $USER;

		return $USER;
	}

	/**
	 * @return CAllMain
	 */
	protected static function getApplication()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	/**
	 * @return $this
	 * @throws Exception
	 */
	protected function checkUser()
	{
		if(!($this->getUser() instanceof CAllUser) || intval($this->getUser()->getId()) <= 0)
		{
			throw new Exception('Wrong auth');
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	protected function checkToken()
	{
		if(!check_bitrix_sessid('token_sid'))
		{
			$this->sendJsonResponse(array(
				'status' => 'error_token_sid',
				'token_sid' => bitrix_sessid(),
			), 403);
		}
		return $this;
	}

	/**
	 * @return $this
	 * @throws Exception
	 */
	protected function runAction()
	{
		if(empty($_REQUEST['action']))
		{
			throw new Exception('Empty action');
		}
		//session commit before action
		//session_write_close();
		if(!CModule::IncludeModule('webdav'))
		{
			$this->sendJsonResponse(array('status' => 'error', 'message' => 'Module webdav not installed'));
		}
		CWebDavDiskDispatcher::storeUsageDisk();
		switch(strtolower($_REQUEST['action']))
		{
			case 'initialize':
				$this->processActionInitialize();
				break;
			case 'snapshot':
				$this->checkToken();
				$this->processActionSnapshot();
				break;
			case 'download':
				$this->checkToken();
				$this->processActionDownload();
				break;
			case 'delete':
				$this->checkToken();
				$this->processActionDelete();
				break;
			case 'upload':
				$this->checkToken();
				$this->processActionUpload();
				break;
			case 'rollbackupload':
				$this->checkToken();
				$this->processActionRollbackUpload();
				break;
			case 'update':
				$this->checkToken();
				$this->processActionUpdate();
				break;
			case 'directory':
				$this->checkToken();
				$this->processActionDirectory();
				break;
			case 'getdiskspace':
				$this->processActionGetDiskSpace();
				break;
			case 'getdiskquota':
				$this->checkToken();
				$this->processActionGetDiskQuota();
				break;
			case 'getmaxpostsize':
				$this->checkToken();
				$this->processActionGetMaxUploadSize();
				break;
			case 'getchunksize':
				$this->checkToken();
				$this->processActionGetChunkSize();
				break;
			case 'getpubliclink':
				$this->checkToken();
				$this->processActionGetPublicLink();
				break;
			default:
				throw new Exception('Wrong action');
		}

		return $this;
	}

	public function executeComponent()
	{
		if(!CModule::IncludeModule('webdav'))
		{
			return false;
		}
		$isVisual = isset($this->arParams['VISUAL'])? (bool)$this->arParams['VISUAL'] : true;
		$pathToAjax = isset($this->arParams['AJAX_PATH'])? $this->arParams['AJAX_PATH'] : '/bitrix/components/bitrix/webdav.disk/ajax.php';
		try
		{
			if($isVisual)
			{
				$diskEnabled =
					\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) &&
					CModule::includeModule('disk');


				$diskQuota = new CDiskQuota;
				$quota = $diskQuota->GetDiskQuota();
				$this->arResult['showDiskQuota'] = false; //$quota !== true; //now without quota
				$this->arResult['diskSpace'] = (float)COption::GetOptionInt('main', 'disk_space')*1024*1024;
				$this->arResult['quota'] = $quota;
				$this->arResult['ajaxIndex'] = $pathToAjax;
				$this->arResult['ajaxStorageIndex'] = '/desktop_app/storage.php';
				if($diskEnabled)
				{
					$this->arResult['isInstalledDisk'] = \Bitrix\Disk\Desktop::isDesktopDiskInstall();
					$this->arResult['personalLibIndex'] = '/company/personal/user/' . $this->getUser()->getId() . '/disk/path/';
				}
				else
				{
					$this->arResult['isInstalledDisk'] = (bool)CWebDavTools::isDesktopDiskInstall();
					$this->arResult['personalLibIndex'] = '/company/personal/user/' . $this->getUser()->getId() . '/files/lib/';
				}
				$this->arResult['isInstalledPull'] = (bool)IsModuleInstalled('pull');
				$this->arResult['currentUser'] = array(
					'id' => $this->getUser()->getId(),
					'formattedName' => $this->getUser()->getFormattedName(),
				);
				$this->getApplication()->addHeadScript('/bitrix/components/bitrix/webdav.disk/disk.js');
				$this->includeComponentTemplate();

				return;
			}

			CTimeZone::Disable();
			//decode from utf-8 to site LANG_CHARSET
			CUtil::decodeURIComponent($_POST);
			$this
				->checkUser()
				->runAction()
			;
			CTimeZone::Enable();
		}
		catch(CWebDavBadStorageAfterMigrateException $e)
		{
			CTimeZone::Enable();
			$this->sendJsonResponse(array(
				'status' => CWebDavDiskDispatcher::STATUS_ERROR,
				'message'=> 'Could not get Disk\\Storage. Perhaps, it is old client, which does not reconnect. ',
			), 500);
		}
		catch(CWebDavSymlinkMoveFakeErrorException $e)
		{
			CTimeZone::Enable();
			$this->sendJsonResponse(array(
				'status' => CWebDavDiskDispatcher::STATUS_ERROR,
				'message'=> 'This is not really error. Move symlink folders.',
			));
		}
		catch(CWebDavAccessDeniedException $e)
		{
			CTimeZone::Enable();
			$this->sendJsonResponse(array(
				'status' => CWebDavDiskDispatcher::STATUS_ACCESS_DENIED,
				'message'=> $e->getMessage(),
			));
		}
		catch(Exception $e)
		{
			CTimeZone::Enable();
			$response = array(
				'status' => 'error',
				'message' => $e->getMessage(),
			);
			if($this->dispatcher)
			{
				$errors = $this->dispatcher->getErrors();
				if($errors)
				{
					$response['detail'] = $errors;
				}
			}

			$this->sendJsonResponse($response);
		}

		return;
	}

	protected function processActionSnapshot()
	{
		//todo version is long int!
		$items = $this->getDispatcher()->processActionSnapshot(array(
			'version' => isset($_REQUEST['version'])? $_REQUEST['version'] : 0,
		));

		$this->sendJsonResponse($items);
	}

	protected function processActionDownload()
	{
		$response = $this->getDispatcher()->processActionDownload(array(
			'id' => isset($_POST['id'])? $_POST['id'] : null,
			'version' => isset($_POST['version'])? $_POST['version'] : null,
			'extra' => isset($_POST['extra'])? $_POST['extra'] : array(),
			'storageId' => isset($_POST['storageId'])? $_POST['storageId'] : null,
			'storageExtra' => isset($_POST['storageExtra'])? $_POST['storageExtra'] : array(),
		));

		$this->sendJsonResponse($response);
	}

	protected function processActionDelete()
	{
		$item = $this->getDispatcher()->processActionDelete(array(
			'id' => isset($_POST['id'])? $_POST['id'] : null,
			'version' => isset($_POST['version'])? $_POST['version'] : null,
			'isDirectory' => isset($_POST['isDirectory'])? $_POST['isDirectory'] == 'true' : false,
			'extra' => isset($_POST['extra'])? $_POST['extra'] : array(),
			'storageId' => isset($_POST['storageId'])? $_POST['storageId'] : null,
			'storageExtra' => isset($_POST['storageExtra'])? $_POST['storageExtra'] : array(),
		));

		$this->sendJsonResponse($item);
	}

	protected function processActionDirectory()
	{
		$item = $this->getDispatcher()->processActionDirectory(array(
			'id' => isset($_POST['id'])? $_POST['id'] : null,
			'name' => isset($_POST['name'])? $_POST['name'] : null,
			'version' => isset($_POST['version'])? $_POST['version'] : null,
			'isDirectory' => isset($_POST['isDirectory'])? $_POST['isDirectory'] == 'true' : false,
			'inRoot' => isset($_POST['inRoot'])? $_POST['inRoot'] == 'true' : false,
			'update' => isset($_POST['update'])? $_POST['update'] == 'true' : false,
			'storageId' => isset($_POST['storageId'])? $_POST['storageId'] : null,
			'storageExtra' => isset($_POST['storageExtra'])? $_POST['storageExtra'] : array(),
			'parentExtra' => isset($_POST['parentExtra'])? $_POST['parentExtra'] : array(),
			'extra' => isset($_POST['extra'])? $_POST['extra'] : array(),
		));

		$this->sendJsonResponse($item);
	}

	protected function processActionUpload()
	{
		$this->sendJsonResponse($this->getDispatcher()->processActionUpload(array(
			'token' => isset($_POST['token'])? $_POST['token'] : null,
			'name' => isset($_POST['name'])? $_POST['name'] : null,
		)));
	}

	protected function processActionGetMaxUploadSize()
	{
		$this->sendJsonResponse($this->getDispatcher()->processActionGetMaxUploadSize());
	}

	protected function processActionGetChunkSize()
	{
		$this->sendJsonResponse($this->getDispatcher()->processActionGetChunkSize(array(
			'name' => isset($_POST['name'])? $_POST['name'] : null,
			'size' => isset($_POST['size'])? $_POST['size'] : null,
		)));
	}

	protected function processActionRollbackUpload()
	{
		$this->sendJsonResponse(
			$this
				->getDispatcher()
				->processActionRollbackUpload(array('token' => $_POST['token']))
		);
	}

	protected function processActionUpdate()
	{
		$item = $this->getDispatcher()->processActionUpdate(array(
			'id' => isset($_POST['id'])? $_POST['id'] : null,
			'name' => isset($_POST['name'])? $_POST['name'] : null,
			'version' => isset($_POST['version'])? $_POST['version'] : null,
			'isDirectory' => isset($_POST['isDirectory'])? $_POST['isDirectory'] == 'true' : false,
			'inRoot' => isset($_POST['inRoot'])? $_POST['inRoot'] == 'true' : false,
			'update' => isset($_POST['update'])? $_POST['update'] == 'true' : false,
			'storageId' => isset($_POST['storageId'])? $_POST['storageId'] : null,
			'storageExtra' => isset($_POST['storageExtra'])? $_POST['storageExtra'] : array(),
			'parentExtra' => isset($_POST['parentExtra'])? $_POST['parentExtra'] : array(),
			'extra' => isset($_POST['extra'])? $_POST['extra'] : array(),
			'token' => empty($_POST['token'])? null : $_POST['token'],
		));

		$this->sendJsonResponse($item);
	}

	protected function processActionInitialize()
	{
		$this->sendJsonResponse(array_merge(
			$this
				->getDispatcher()
				->processActionInitialize(),
			array('token_sid' => bitrix_sessid())
		));
	}

	protected function processActionGetDiskSpace()
	{
		$this->sendJsonResponse(
			$this
				->getDispatcher()
				->processActionGetDiskSpace()
		);
	}

	protected function processActionGetDiskQuota()
	{
		$this->sendJsonResponse(
			$this
				->getDispatcher()
				->processActionGetDiskQuota()
		);
	}

	protected  function processActionGetPublicLink()
	{
		$this->sendJsonResponse(
			$this
				->getDispatcher()
				->processActionGetPublicLink(array(
					'id' => isset($_POST['id']) ? $_POST['id'] : null,
					'isDirectory' => isset($_POST['isDirectory']) ? $_POST['isDirectory'] == 'true' : false,
					'storageId' => isset($_POST['storageId']) ? $_POST['storageId'] : null,
					'storageExtra' => isset($_POST['storageExtra']) ? $_POST['storageExtra'] : array(),
					'extra' => isset($_POST['extra']) ? $_POST['extra'] : array(),
				))
		);
	}
}
