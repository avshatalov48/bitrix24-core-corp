<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\UrlManager;
use Bitrix\Main\Errorable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config;
use Exception;

Loc::loadMessages(__FILE__);

abstract class BaseComponent extends \CBitrixComponent implements Errorable
{
	const ERROR_REQUIRED_PARAMETER = 'DISK_BASE_COMPONENT_22001';

	const STATUS_SUCCESS = 'success';
	const STATUS_DENIED  = 'denied';
	const STATUS_ERROR   = 'error';

	/** @var  string */
	protected $actionPrefix = 'action';
	/** @var  string */
	protected $action;
	/** @var  array */
	protected $actionDescription;
	/** @var  string */
	protected $realActionName;
	/** @var  ErrorCollection */
	protected $errorCollection;
	/** @var \CMain */
	protected $application;
	protected $componentId = '';
	protected static $alreadyWrappedForSidepanel = false;

	public function __construct($component = null)
	{
		parent::__construct($component);
		if(!$this->componentId)
		{
			$this->componentId = $this->isAjaxRequest()? randString(7) : $this->randString();
		}

		$this->errorCollection = new ErrorCollection();

		global $APPLICATION;
		$this->application = $APPLICATION;
	}

	final protected function restartBuffer()
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();
	}

	protected function sendResponse($response)
	{
		$this->restartBuffer();

		echo $response;

		$this->end();
	}

	protected function sendJsonResponse($response)
	{
		$this->restartBuffer();

		header('Content-Type:application/json; charset=UTF-8');
		echo Json::encode($response);

		$this->end();
	}

	protected function sendJsonErrorResponse()
	{
		$errors = array();
		foreach($this->getErrors() as $error)
		{
			/** @var Error $error */
			$errors[] = array(
				'message' => $error->getMessage(),
				'code' => $error->getCode(),
			);
		}
		unset($error);
		$this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
			'errors' => $errors,
		));
	}

	protected function sendJsonSuccessResponse(array $response = array())
	{
		$response['status'] = self::STATUS_SUCCESS;
		$this->sendJsonResponse($response);
	}

	protected function sendJsonAccessDeniedResponse($message = '')
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_DENIED,
			'message' => $message,
		));
	}

	protected function showAccessDenied()
	{
		ShowError(Loc::getMessage('DISK_BASE_COMPONENT_ERROR_ACCESS_DENIED'));

		$this->end(false);
	}

	protected function end($terminate = true)
	{
		Diag::getInstance()->logDebugInfo($this->getName());

		if($terminate)
		{
			/** @noinspection PhpUndefinedClassInspection */
			\CMain::finalActions();
			die;
		}
	}

	public static function encodeUrn($urn)
	{
		return Driver::getInstance()->getUrlManager()->encodeUrn($urn);
	}

	public function hasErrors()
	{
		return $this->errorCollection->hasErrors();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function getComponentId()
	{
		return $this->componentId;
	}

	protected function wrapAsSidepanelContent()
	{
		if (self::$alreadyWrappedForSidepanel)
		{
			return false;
		}

		if (
			$this instanceof SidePanelWrappable &&
			($this->request->get('IFRAME') === 'Y' || $this->request->getPost('IFRAME') === 'Y')
		)
		{
			self::$alreadyWrappedForSidepanel = true;

			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				'bitrix:disk.sidepanel.wrapper',
				"",
				array(
					'POPUP_COMPONENT_NAME' => $this->getName(),
					'POPUP_COMPONENT_TEMPLATE_NAME' => "",
					'POPUP_COMPONENT_PARAMS' => $this->arParams,
				)
			);

			return true;
		}

		return false;
	}

	public function isWrappedAsSidepanelContent()
	{
		return self::$alreadyWrappedForSidepanel;
	}

	public function executeComponent()
	{
		try
		{
			if ($this->wrapAsSidepanelContent())
			{
				return;
			}

			Diag::getInstance()->collectDebugInfo($this->componentId, $this->getName());

			$this->resolveAction();
			$this->checkAction();

			$this->checkRequiredModules();
			$this->prepareParams();

			if($this->processBeforeAction($this->getAction()) !== false)
			{
				$this->runAction();
			}

			Diag::getInstance()->logDebugInfo($this->componentId, $this->getName());
		}
		catch(Exception $e)
		{
			$this->runProcessingExceptionComponent($e);
		}
	}

	/**
	 * @return UrlManager
	 */
	protected function getUrlManager()
	{
		return Driver::getInstance()->getUrlManager()->setComponent($this);
	}

	private function setProcessToDefaultAction()
	{
		//Attention! we must accept GET, POST queries on default action for rendering component.
		//And we must never modify data in default action. It will be error.
		$this->realActionName = 'default';
		$this->setAction($this->realActionName, array(
			'method' => array('GET', 'POST'),
			'name' => 'default',
			'check_csrf_token' => false,
		));
	}

	protected function resolveAction()
	{
		$listOfActions = $this->normalizeListOfAction($this->listActions());

		$this->realActionName = null;
		//todo? action prefix? Url Manager?
		$action = $this->arParams[$this->actionPrefix] ?? $this->request->getQuery($this->actionPrefix);
		if($action && is_string($action) && isset($listOfActions[strtolower($action)]))
		{
			$this->realActionName = strtolower($action);
		}

		if(!$this->realActionName || $this->realActionName === 'default')
		{
			$this->setProcessToDefaultAction();
			return $this;
		}

		$description = $listOfActions[$this->realActionName];
		if(!in_array($this->request->getRequestMethod(), $description['method'], true))
		{
			$this->setProcessToDefaultAction();
		}
		else
		{
			$this->setAction($description['name'], $description);
		}

		return $this;
	}

	//todo refactor BaseComponent + Controller normalizeListOfAction, resolveAction.
	//you can use composition in BaseComponent
	protected function normalizeListOfAction(array $listOfActions)
	{
		$normalized = array();
		foreach($listOfActions as $action => $description)
		{
			if(!is_string($action))
			{
				$normalized[$description] = $this->normalizeActionDescription($description, $description);
			}
			else
			{
				$normalized[$action] = $this->normalizeActionDescription($action, $description);
			}
		}
		unset($action, $description);

		return array_change_key_case($normalized, CASE_LOWER);
	}

	protected function normalizeActionDescription($action, $description)
	{
		if(!is_array($description))
		{
			$description = array(
				'method' => array('GET'),
				'name' => $description,
				'check_csrf_token' => false,
			);
		}
		if(empty($description['name']))
		{
			$description['name'] = $action;
		}

		return $description;
	}

	protected function listActions()
	{
		return array();
	}

	protected function checkRequiredModules()
	{
		return $this;
	}

	protected function prepareParams()
	{
		return $this;
	}

	protected function runAction()
	{
		$binder = new Engine\Binder(
			$this,
			'processAction' . $this->getAction(),
			array($this->request->getPostList(), $this->request->getQueryList())
		);

		return $binder->invoke();
	}

	/**
	 * @return string
	 */
	protected function getAction()
	{
		return $this->action;
	}

	/**
	 * @return array
	 */
	protected function getActionDescription()
	{
		return $this->actionDescription;
	}

	/**
	 * @param string $action
	 * @param array  $description
	 * @return $this
	 */
	protected function setAction($action, array $description)
	{
		$this->action = $action;
		$this->actionDescription = $description;

		return $this;
	}

	abstract protected function processActionDefault();

	/**
	 * @param array $inputParams
	 * @param array $required
	 * @return bool
	 */
	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && mb_strlen($inputParams[$item]))))
			{
				$this->errorCollection->add(array(new Error(Loc::getMessage('DISK_BASE_COMPONENT_ERROR_REQUIRED_PARAMETER', array('#PARAM#' => $item)), self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}

	protected function checkRequiredPostParams(array $required)
	{
		$params = array();
		foreach($required as $item)
		{
			$params[$item] = $this->request->getPost($item);
		}
		unset($item);

		return $this->checkRequiredInputParams($params, $required);
	}

	protected function checkRequiredGetParams(array $required)
	{
		$params = array();
		foreach($required as $item)
		{
			$params[$item] = $this->request->getQuery($item);
		}
		unset($item);

		return $this->checkRequiredInputParams($params, $required);
	}

	protected function checkRequiredFilesParams(array $required)
	{
		$params = array();
		foreach($required as $item)
		{
			$params[$item] = $this->request->getFile($item);
		}
		unset($item);

		return $this->checkRequiredInputParams($params, $required);
	}

	/**
	 * Common operations before run action.
	 * @param string $actionName Action name which will be run.
	 * @return bool If method will return false, then action will not execute.
	 */
	protected function processBeforeAction($actionName)
	{
		return true;
	}

	/**
	 * @param Exception $e
	 * @throws \Exception
	 */
	protected function runProcessingExceptionComponent(Exception $e)
	{
		if($this->isAjaxRequest())
		{
			$this->sendJsonResponse(array(
				'status' => static::STATUS_ERROR,
				'message' => $e->getMessage(),
			));
		}
		else
		{
			$exceptionHandling = Config\Configuration::getValue("exception_handling");
			if($exceptionHandling["debug"])
			{
				throw $e;
			}
		}
	}

	/**
	 * Returns whether this is an AJAX (XMLHttpRequest) request.
	 * @return boolean
	 */
	protected function isAjaxRequest()
	{
		return $this->request->isAjaxRequest();
	}

	protected function checkAction()
	{
		if($this->errorCollection->hasErrors())
		{
			$this->sendJsonErrorResponse();
		}
		$description = $this->getActionDescription();

		//if does not exist check_csrf_token we have to check csrf for only POST method.
		if($description['check_csrf_token'] === true || ($this->request->isPost() && !isset($description['check_csrf_token'])))
		{
			if(!check_bitrix_sessid())
			{
				if($this->isAjaxRequest())
				{
					$this->sendJsonAccessDeniedResponse('Wrong csrf token');
				}
				else
				{
					$this->showAccessDenied();
				}
			}
		}
	}

	/**
	 * @internal
	 * @deprecated
	 * @return \CMain
	 */
	protected function getApplication()
	{
		return $this->application;
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