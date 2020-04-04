<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	return;

Loc::loadMessages(__FILE__);

class CrmProductAjaxController
{
	const STATUS_SUCCESS   = 'success';
	const STATUS_DENIED    = 'denied';
	const STATUS_ERROR     = 'error';
	
	static $allowedViewOptions = array('crm_product_template_list_default');

	protected $errors;
	protected $action;
	protected $viewOptionId;

	protected $rightSideWidth;
	protected $rightSideClosed;

	public function __construct()
	{
		$this->errors = array();
		$this->action = '';
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
		$this->errors[] = array('code' => 0, 'message' => $e->getMessage());
		$this->sendJsonErrorResponse();
	}

	protected function checkRequest()
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
				'code' => 0,
				'message' => GetMessage('CRM_PRODUCT_AJAX_ERROR_EMPTY_ACTION')
			);
		}
		if (!$this->errors)
		{
			switch ($action)
			{
				case 'saveviewoptions':
					$this->action = $action;
					$rightSideWidth = (isset($_REQUEST['rightSideWidth'])) ? intval($_REQUEST['rightSideWidth']) : 0;
					if ($rightSideWidth <= 0)
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'rightSideWidth')
							)
						);
					}
					$this->rightSideWidth = $rightSideWidth;
					$rightSideClosed = (isset($_REQUEST['rightSideClosed'])) ? strval($_REQUEST['rightSideClosed']) : '';
					if ($rightSideClosed === '')
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'rightSideClosed')
							)
						);
					}
					$this->rightSideClosed = ($rightSideClosed === 'Y') ? 'Y' : 'N';
					$viewOptionId = (isset($_REQUEST['viewOptionId'])) ? strval($_REQUEST['viewOptionId']) : '';
					if ($viewOptionId === '' || !in_array($viewOptionId, self::$allowedViewOptions, true))
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'viewOptionId')
							)
						);
					}
					$this->viewOptionId = $viewOptionId;
					break;

				default:
					$this->errors[] = array(
						'code' => 0,
						'message' => GetMessage(
							'CRM_PRODUCT_AJAX_AJAX_ERROR_UNKNOWN_ACTION',
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
			case 'saveviewoptions':
				$this->processActionSaveViewOptions();
				break;
		}
	}

	protected function processActionSaveViewOptions()
	{
		$optionValue = array(
			'rightSideWidth' => $this->rightSideWidth,
			'rightSideClosed' => $this->rightSideClosed
		);
		CUserOptions::SetOption('crm', $this->viewOptionId, $optionValue);
		$this->sendJsonSuccessResponse(array("OK"));
	}
}

$controller = new CrmProductAjaxController();
$controller->exec();
