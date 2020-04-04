<?php

namespace Bitrix\Disk\Document;


use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\User;
use Bitrix\Disk\UserConfiguration;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;

class DocumentHandlersManager
{
	const ERROR_UNKNOWN_HANDLER = 'DISK_DOC_HANDM_22001';

	/** @var DocumentHandler[] */
	protected $documentHandlerList = array();
	/** @var  ErrorCollection */
	protected $errorCollection;
	protected $userId;

	public function __construct($user)
	{
		$this->errorCollection = new ErrorCollection;
		$this->userId = User::resolveUserId($user);

		$this->buildDocumentHandlerList();
	}

	/**
	 * Get default cloud document service for current user.
	 * In this method we don't know about local controller.
	 * @return null|DocumentHandler
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getDefaultServiceForCurrentUser()
	{
		//todo may be we should use userId but now we look on $USER;
		static $currentHandler;
		if($currentHandler)
		{
			return $currentHandler;
		}
		$codeForUser = UserConfiguration::getDocumentServiceCode();
		if(empty($codeForUser))
		{
			//todo by default we use googleHandler. But possible create option with default service.
			/** @var GoogleHandler $googleDriveClass */
			$googleDriveClass = GoogleHandler::className();
			$codeForUser = $googleDriveClass::getCode();
		}
		$currentHandler = $this->getHandlerByCode($codeForUser);

		return $currentHandler;
	}

	/**
	 * Gets document handler by code.
	 * @param string $code
	 * @return DocumentHandler|null
	 * @throws SystemException
	 */
	public function getHandlerByCode($code)
	{
		if(!isset($this->documentHandlerList[$code]))
		{
			$this->errorCollection->add(array(new Error("Unknown document handler name {$code}", self::ERROR_UNKNOWN_HANDLER)));
			return null;
		}

		/** @var DocumentHandler $documentHandler */
		$documentHandler = new $this->documentHandlerList[$code]($this->userId);
		if(!$documentHandler instanceof DocumentHandler)
		{
			throw new SystemException("Invalid class '{$this->documentHandlerList[$code]}' for documentHandler. Must be instance of DocumentHandler");
		}

		return $documentHandler;
	}

	/**
	 * Returns all list of document handlers.
	 * @return DocumentHandler[]
	 */
	public function getHandlers()
	{
		$list = [];
		foreach ($this->documentHandlerList as $code => $class)
		{
			$handler = $this->getHandlerByCode($code);
			if (!$this->shouldHideGoogle($handler))
			{
				$list[$code] = $handler;
			}
		}

		return $list;
	}

	private function shouldHideGoogle(DocumentHandler $handler)
	{
		return false;
	}

	/**
	 * Returns all list of document handlers which can import files and folders.
	 * @return DocumentHandler[]
	 */
	public function getHandlersForImport()
	{
		$list = array();
		foreach($this->getHandlers() as $code => $handler)
		{
			if($handler instanceof GoogleViewerHandler)
			{
				continue;
			}

			if($handler instanceof BitrixHandler)
			{
				continue;
			}

			$list[$code] = $handler;
		}
		unset($handler);

		return $list;
	}

	/**
	 * Returns all list of document handlers which can view files.
	 *
	 * @return DocumentHandler[]
	 */
	public function getHandlersForView()
	{
		$list = array();
		foreach($this->getHandlers() as $code => $handler)
		{
			if($handler instanceof IViewer)
			{
				$list[$code] = $handler;
			}
		}
		unset($handler);

		return $list;
	}

	/**
	 * Returns default document handler for view files.
	 *
	 * @return DocumentHandler|null
	 * @throws SystemException
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	public function getDefaultHandlerForView()
	{
		$documentHandler = $this->getHandlerByCode(Configuration::getDefaultViewerServiceCode());
		if(!$documentHandler instanceof IViewer)
		{
			throw new SystemException("Invalid class '{$documentHandler->getCode()}' for documentHandler. Must be implement IViewer");
		}

		return $documentHandler;
	}

	public function isReady(DocumentHandler $documentHandler)
	{
		if(!$documentHandler->checkAccessibleTokenService())
		{
			$this->errorCollection->add($documentHandler->getErrors());
			return false;
		}

		return
			$documentHandler->queryAccessToken()->hasAccessToken() &&
			!$documentHandler->isRequiredAuthorization()
		;
	}

	protected function buildDocumentHandlerList()
	{
		/** @var MyOfficeHandler $myOfficeClass */
		$myOfficeClass = MyOfficeHandler::className();
		/** @var GoogleHandler $googleDriveClass */
		$googleDriveClass = GoogleHandler::className();
		/** @var DropboxHandler $dropboxClass */
		$dropboxClass = DropboxHandler::className();
		/** @var BoxHandler $boxClass */
		$boxClass = BoxHandler::className();
		/** @var YandexDiskHandler $yandexDiskClass */
		$yandexDiskClass = YandexDiskHandler::className();
		/** @var OneDriveHandler $oneDriveClass */
		$oneDriveClass = OneDriveHandler::className();
		/** @var Office365Handler $office365Class */
		$office365Class = Office365Handler::className();
		/** @var GoogleViewerHandler $googleViewerClass */
		$googleViewerClass = GoogleViewerHandler::className();
		/** @var BitrixHandler $bitrixClass */
		$bitrixClass = BitrixHandler::className();
		$this->documentHandlerList = array(
			$bitrixClass::getCode() => $bitrixClass,
			$googleDriveClass::getCode() => $googleDriveClass,
			$oneDriveClass::getCode() => $oneDriveClass,
			$office365Class::getCode() => $office365Class,
			$dropboxClass::getCode() => $dropboxClass,
			$googleViewerClass::getCode() => $googleViewerClass,
			$yandexDiskClass::getCode() => $yandexDiskClass,
			$boxClass::getCode() => $boxClass,
		);

		if (MyOfficeHandler::isEnabled() && MyOfficeHandler::getPredefinedUser($this->userId))
		{
			$this->documentHandlerList[$myOfficeClass::getCode()] = $myOfficeClass;
		}

		$event = new Event(Driver::INTERNAL_MODULE_ID, 'onDocumentHandlerBuildList');
		$event->send();
		if($event->getResults())
		{
			foreach($event->getResults() as $evenResult)
			{
				if($evenResult->getType() != EventResult::SUCCESS)
				{
					continue;
				}
				$result = $evenResult->getParameters();
				if(!is_array($result))
				{
					throw new SystemException('Wrong event result by building DocumentHandlerList. Must be array.');
				}
				if(empty($result['CODE_NAME']))
				{
					throw new SystemException('Wrong event result by building DocumentHandlerList. Could not find CODE_NAME.');
				}
				if(empty($result['CLASS']))
				{
					throw new SystemException('Wrong event result by building DocumentHandlerList. Could not find CLASS.');
				}
				if(is_string($result['CLASS']) && class_exists($result['CLASS']))
				{
					$this->documentHandlerList[$result['CODE_NAME']] = $result['CLASS'];
				}
				else
				{
					throw new SystemException('Wrong event result by building DocumentHandlerList. Could not find class by CLASS.');
				}
			}
		}
	}

	/**
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * @inheritdoc
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}