<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('imconnector') || !Loader::includeModule('imopenlines'))
{
	return;
}

Loc::loadMessages(__FILE__);

class ImConnectorLiveChatAjaxController
{
	protected $errors = array();
	protected $action = null;
	protected $responseData = array();
	protected $requestData = array();

	/** @var \Bitrix\Main\HttpRequest $request */
	protected $request = array();

	protected function getActions()
	{
		return array(
			'formatName',
			'checkName',
		);
	}

	protected function formatName()
	{
		$alias = \Bitrix\ImOpenLines\LiveChatManager::prepareAlias($this->requestData['ALIAS']);
		$this->responseData['ALIAS'] = $alias? $alias: '';
	}

	protected function checkName()
	{
		$alias = \Bitrix\ImOpenLines\LiveChatManager::prepareAlias($this->requestData['ALIAS']);
		$this->responseData['ALIAS'] = $alias ? $alias : '';

		if ($this->responseData['ALIAS'] !== '')
		{
			$manager = new \Bitrix\ImOpenLines\LiveChatManager($this->requestData['CONFIG_ID']);
			$result = $manager->checkAvailableName($this->responseData['ALIAS']);

			$this->responseData['AVAILABLE'] = $result ? 'Y':'N';
		}
		else
		{
			$this->responseData['AVAILABLE'] = 'Y';
		}
	}

	protected function checkPermissions()
	{
		$configManager = new \Bitrix\ImOpenLines\Config();
		return $configManager->canEditLine($this->requestData['CONFIG_ID']);
	}

	protected function prepareRequestData()
	{
		$this->requestData = array(
			'CONFIG_ID' => intval($this->request->get('CONFIG_ID')),
			'ALIAS' => $this->request->get('ALIAS')
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
		if(!$this->checkPermissions())
		{
			$this->errors[] = Loc::getMessage('IMCONNECTOR_PERMISSION_DENIED');
		}
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
		$this->action = $this->request->get('ACTION');

		$this->prepareRequestData();

		if($this->check())
		{
			call_user_func_array($this->getActionCall(), array($this->requestData));
		}
		$this->giveResponse();
	}
}

$controller = new ImConnectorLiveChatAjaxController();
$controller->exec();