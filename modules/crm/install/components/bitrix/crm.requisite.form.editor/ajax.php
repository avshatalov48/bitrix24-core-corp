<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$siteID = isset($_REQUEST['site']) ? substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	return;

Loc::loadMessages(__FILE__);

class CrmRequisiteFormEditorController
{
	const STATUS_SUCCESS   = 'success';
	const STATUS_DENIED    = 'denied';
	const STATUS_ERROR     = 'error';

	const ERR_EXCEPTION             = 1;
	const ERR_EMPTY_ACTION          = 2;
	const ERR_REQ_PARAM_ABSENT      = 3;
	const ERR_REQUISITE_NOT_FOUND   = 4;
	const ERR_UNKNOWN_ACTION        = 5;
	const ERR_REQUISITE_DELETE      = 6;

	protected $errors;
	protected $action;
	protected $requisiteId;
	protected $requisite;

	public function __construct()
	{
		$this->errors = array();
		$this->action = '';
		$this->requisiteId = 0;
		$this->requisite = new \Bitrix\Crm\EntityRequisite();
	}

	public function exec()
	{
		try
		{
			\CUtil::JSPostUnescape();

			$this->checkRequest();

			$this->processAction();
		}
		catch(\Exception $e)
		{
			$this->runProcessingException($e);
		}
	}

	public function end()
	{
		include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
		exit;
	}

	protected function getUser()
	{
		global $USER;

		return $USER;
	}

	protected function sendJsonResponse($response)
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}

		global $APPLICATION;
		$APPLICATION->restartBuffer();
		while(ob_end_clean());

		header('Content-Type:application/json; charset=UTF-8');
		echo Json::encode($response);

		$this->end();
	}

	protected function sendJsonAccessDeniedResponse($message = '')
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_DENIED,
			'message' => $message
		));
	}

	protected function sendJsonSuccessResponse(array $response = array())
	{
		$json = array(
			'status' => self::STATUS_SUCCESS,
			'response' => $response
		);
		$this->sendJsonResponse($json);
	}

	protected function sendJsonErrorResponse()
	{
		$this->sendJsonResponse(array(
			'status' => self::STATUS_ERROR,
			'errors' => $this->errors
		));
	}

	protected function runProcessingException(\Exception $e)
	{
		$this->errors[] = array('code' => self::ERR_EXCEPTION, 'message' => $e->getMessage());
		$this->sendJsonErrorResponse();
	}

	protected function checkRequest()
	{
		$action = '';
		if (!$this->errors)
		{
			if(!$this->getUser() || !$this->getUser()->getId() || !$this->getUser()->IsAuthorized()
				|| $_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid())
			{
				$this->sendJsonAccessDeniedResponse();
			}

			$action = (isset($_REQUEST['action']) && is_string($_REQUEST['action'])) ? strtolower($_REQUEST['action']) : '';
			if (empty($action))
			{
				$this->errors[] = array(
					'code' => self::ERR_EMPTY_ACTION,
					'message' => GetMessage('CRM_REQUISITE_FORM_EDITOR_AJAX_ERROR_EMPTY_ACTION')
				);
			}
		}

		if (!$this->errors)
		{
			switch ($action)
			{
				case 'deleterequisite':
					$this->action = $action;
					$requisiteId = isset($_REQUEST['requisite_id']) ? (int)$_REQUEST['requisite_id'] : 0;
					if ($requisiteId <= 0)
					{
						$this->errors[] = array(
							'code' => self::ERR_REQ_PARAM_ABSENT,
							'message' => GetMessage(
								'CRM_REQUISITE_FORM_EDITOR_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'requisite_id')
							)
						);
					}

					$this->requisiteId = $requisiteId;
					unset($requisiteId);
					$requisiteInfo = null;
					if (!$this->errors)
					{
						$requisiteInfo = $this->requisite->getById($this->requisiteId);
						if (!is_array($requisiteInfo))
						{
							$this->errors[] = array(
								'code' => self::ERR_REQUISITE_NOT_FOUND,
								'message' => GetMessage(
									'CRM_REQUISITE_FORM_EDITOR_AJAX_ERROR_REQUISITE_NOT_FOUND',
									array('#ID#' => $this->requisiteId)
								)
							);
						}
					}
					if (!$this->errors)
					{
						$entityTypeId = isset($requisiteInfo['ENTITY_TYPE_ID']) ? (int)$requisiteInfo['ENTITY_TYPE_ID'] : 0;
						$entityId = isset($requisiteInfo['ENTITY_ID']) ? (int)$requisiteInfo['ENTITY_ID'] : 0;
						if (!$this->requisite->validateEntityUpdatePermission($entityTypeId, $entityId))
						{
							$this->sendJsonAccessDeniedResponse();
						}
					}
					break;

				default:
					$this->errors[] = array(
						'code' => self::ERR_UNKNOWN_ACTION,
						'message' => GetMessage(
							'CRM_REQUISITE_FORM_EDITOR_AJAX_ERROR_UNKNOWN_ACTION',
							array('#ACTION#' => $_REQUEST['action'])
						)
					);
			}
		}
		if ($this->errors)
			$this->sendJsonErrorResponse();
	}

	protected function processAction()
	{
		if (empty($this->action))
			return;

		switch ($this->action)
		{
			case 'deleterequisite':
				$this->processActionDeleteRequisite();
				break;
		}
	}

	protected function processActionDeleteRequisite()
	{
		if(!($result = $this->requisite->delete($this->requisiteId)))
		{
			if ($result instanceof \Bitrix\Main\Entity\Result)
			{
				foreach ($result->getErrors() as $err)
					$this->errors[] = array('code' => self::ERR_REQUISITE_DELETE, 'message' => $err->getMessage());
			}
			else
			{
				$this->errors[] = array(
					'code' => self::ERR_REQUISITE_DELETE,
					'message' => GetMessage(
						'CRM_REQUISITE_FORM_EDITOR_AJAX_ERROR_REQUISITE_DELETE',
						array('#ID#' => $this->requisiteId)
					)
				);
			}
		}
		$this->sendJsonSuccessResponse(array('id' => $this->requisiteId));
	}
}

$controller = new CrmRequisiteFormEditorController();
$controller->exec();
