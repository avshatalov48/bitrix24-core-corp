<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	return;

require_once ('helper.php');

Loc::loadMessages(__FILE__);

class CrmProductSectionCrumbsController
{
	const STATUS_SUCCESS   = 'success';
	const STATUS_DENIED    = 'denied';
	const STATUS_ERROR     = 'error';

	const DEF_URL_TEMPLATE = '#section_id#';

	protected $errors;
	protected $action;

	protected $catalogId;
	protected $sectionId;

	public function __construct()
	{
		$this->errors = array();
		$this->action = '';
		$this->catalogId = 0;
		$this->sectionId = 0;
		$this->urlTemplate = self::DEF_URL_TEMPLATE;
		$this->jsEventsMode = false;
		$this->componentId = '';
		$this->helper = new CCrmProductSectionCrumbsHelper;
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
			|| $_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid() || !$this->helper->checkRights())
		{
			$this->sendJsonAccessDeniedResponse();
		}

		$action = (isset($_REQUEST['action']) && is_string($_REQUEST['action'])) ? strtolower($_REQUEST['action']) : '';
		if (empty($action))
		{
			$this->errors[] = array(
				'code' => 0,
				'message' => GetMessage('CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_EMPTY_ACTION')
			);
		}
		if (!$this->errors)
		{
			switch ($action)
			{
				case 'getcrumbs':
					$this->action = $action;
					$catalogId = (isset($_REQUEST['catalogId'])) ? intval($_REQUEST['catalogId']) : 0;
					if ($catalogId <= 0)
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'catalogId')
							)
						);
					}
					$this->catalogId = $catalogId;
					$sectionId = (isset($_REQUEST['sectionId'])) ? strval($_REQUEST['sectionId']) : '';
					if (strlen($sectionId) <= 0)
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'sectionId')
							)
						);
					}
					$this->sectionId = $sectionId;
					$urlTemplate = (!empty($_REQUEST['urlTemplate'])) ? strval($_REQUEST['urlTemplate']) : '';
					if (empty($urlTemplate))
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'urlTemplate')
							)
						);
					}
					$this->urlTemplate = $urlTemplate;
					$jsEventsMode = (!empty($_REQUEST['jsEventsMode'])) ? strval($_REQUEST['jsEventsMode']) : 'N';
					if (empty($jsEventsMode))
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'jsEventsMode')
							)
						);
					}
					$this->jsEventsMode = ($jsEventsMode === 'Y');
					$componentId = (!empty($_REQUEST['componentId'])) ? strval($_REQUEST['componentId']) : '';
					if (empty($componentId))
					{
						$this->errors[] = array(
							'code' => 0,
							'message' => GetMessage(
								'CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_REQUIRED_PARAMETER',
								array('#PARAM#' => 'componentId')
							)
						);
					}
					$this->componentId = $componentId;
					break;

				default:
					$this->errors[] = array(
						'code' => 0,
						'message' => GetMessage(
							'CRM_PRODUCT_SECTION_CRUMBS_AJAX_ERROR_UNKNOWN_ACTION',
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
			case 'getcrumbs':
				$this->processActionGetCrumbs();
				break;
		}
	}

	protected function processActionGetCrumbs()
	{
		$response = $this->helper->getCrumbs($this->catalogId, $this->sectionId, $this->urlTemplate);
		/*if ($this->jsEventsMode)
		{
			$this->helper->PrepareCrumbLinks($response, $this->componentId);
		}*/
		$this->sendJsonSuccessResponse($response);
	}
}

$controller = new CrmProductSectionCrumbsController();
$controller->exec();
