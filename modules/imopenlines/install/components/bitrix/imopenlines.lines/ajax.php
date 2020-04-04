<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

define("IM_AJAX_INIT", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('imopenlines'))
{
	return;
}

Loc::loadMessages(__FILE__);

class ImOpenLinesListAjaxController
{
	protected $errors = array();
	protected $action = null;
	protected $responseData = array();
	protected $requestData = array();
	
	/** @var \Bitrix\ImOpenlines\Security\Permissions */
	protected $userPermissions;

	/** @var HttpRequest $request */
	protected $request = array();

	protected function getActions()
	{
		return array(
			'create',
			'activate',
			'deactivate',
			'delete',
		);
	}

	protected function create()
	{
		if(!$this->userPermissions->canPerform(
			\Bitrix\ImOpenlines\Security\Permissions::ENTITY_LINES, 
			\Bitrix\ImOpenlines\Security\Permissions::ACTION_MODIFY
		))
		{
			$this->errors[] = Loc::getMessage('OL_PERMISSION_CREATE_LINE');
			return;
		}
		
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canActivateLine())
		{
			$this->responseData['limited'] = true;
			$this->errors[] = '';
			return;
		}
		
		$this->responseData['config_id'] = $configManager->create();
	}

	protected function activate()
	{
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canEditLine($this->requestData['CONFIG_ID']))
		{
			$this->errors[] = Loc::getMessage('OL_PERMISSION_MODIFY_LINE');
			return;
		}
		if(!$configManager->canActivateLine())
		{
			$this->responseData['limited'] = true;
			$this->errors[] = '';
			return;
		}
		

		$configManager->setActive($this->requestData['CONFIG_ID'], true);
	}

	protected function deactivate()
	{
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canEditLine($this->requestData['CONFIG_ID']))
		{
			$this->errors[] = Loc::getMessage('OL_PERMISSION_MODIFY_LINE');
			return;
		}
		$configManager->setActive($this->requestData['CONFIG_ID'], false);
	}

	protected function delete()
	{
		$configManager = new \Bitrix\ImOpenLines\Config();
		if(!$configManager->canEditLine($this->requestData['CONFIG_ID']))
		{
			$this->errors[] = Loc::getMessage('OL_PERMISSION_MODIFY_LINE');
			return;
		}
		return $configManager->delete($this->requestData['CONFIG_ID']);
	}

	protected function prepareRequestData()
	{
		$this->userPermissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();
		$this->requestData = array(
			'CONFIG_ID' => intval($this->request->get('config_id'))
		);
	}

	protected function giveResponse()
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();

		header('Content-Type:application/json; charset=UTF-8');
		echo \Bitrix\Main\Web\Json::encode(
			$this->responseData + array(
				'error' => $this->hasErrors(),
				'text' => implode('<br>', $this->errors),
			)
		);

		\CMain::finalActions();
		exit;
	}

	protected function getActionCall()
	{
		return array($this, $this->action);
	}

	protected function hasErrors()
	{
		return count($this->errors) > 0;
	}

	protected function check()
	{
		if(!in_array($this->action, $this->getActions()))
		{
			$this->errors[] = 'Action "' . $this->action . '" not found.';
		}
		elseif(!check_bitrix_sessid() || !$this->request->isPost())
		{
			$this->errors[] = 'Security error.';
		}
		elseif(!is_callable($this->getActionCall()))
		{
			$this->errors[] = 'Action method "' . $this->action . '" not found.';
		}

		return !$this->hasErrors();
	}

	public function exec()
	{
		$this->request = Context::getCurrent()->getRequest();
		$this->action = $this->request->get('action');

		$this->prepareRequestData();

		if($this->check())
		{
			call_user_func_array($this->getActionCall(), array($this->requestData));
		}
		$this->giveResponse();
	}
}

$controller = new ImOpenLinesListAjaxController();
$controller->exec();