<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Limits;

Loc::loadLanguageFile(__DIR__.'/component.php');

class VoximplantSipAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('voximplant');
	}

	public function createSipConnectionAction($type, $title, $server, $login, $password, $authUser = "", $outboundProxy = "")
	{
		if (!Limits::canManageTelephony())
		{
			$this->addError(new \Bitrix\Main\Error(Loc::getMessage("VOX_CONF_SIP_PAID_PLAN_REQUIRED"), "paid_plan_required"));
			return null;
		}

		$type = $type == CVoxImplantSip::TYPE_CLOUD ? CVoxImplantSip::TYPE_CLOUD : CVoxImplantSip::TYPE_OFFICE;

		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_SIP_ACCESS_DENIED"));
			return null;
		}

		$viSip = new CVoxImplantSip();
		$sipFields = array(
			'TYPE' => $type,
			'PHONE_NAME' => $title,
			'SERVER' => $server,
			'LOGIN' => $login,
			'PASSWORD' => $password,
		);

		if($type === CVoxImplantSip::TYPE_CLOUD)
		{
			$sipFields['AUTH_USER'] = $authUser;
			$sipFields['OUTBOUND_PROXY'] = $outboundProxy;
		}

		$configId = $viSip->Add($sipFields);
		if (!$configId)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error($viSip->GetError()->msg);
			return null;
		}

		return [
			'configId' => $configId
		];
	}

	public function getSipConnectionsAction($type)
	{
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_SIP_ACCESS_DENIED"));
			return null;
		}

		$result = [];
		$cursor = Bitrix\Voximplant\ConfigTable::getList(Array(
			'select' => Array('ID', 'SEARCH_ID', 'PHONE_NAME'),
			'filter' => Array(
				'=PORTAL_MODE' => CVoxImplantConfig::MODE_SIP,
				'=SIP_CONFIG.TYPE' => $type
			)
		));
		while ($row = $cursor->fetch())
		{
			if ($row['PHONE_NAME'] == '')
			{
				$row['PHONE_NAME'] = mb_substr($row['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_CLOUD_TITLE'): GetMessage('VI_CONFIG_SIP_OFFICE_TITLE');
				$row['PHONE_NAME'] = str_replace('#ID#', $row['ID'], $row['PHONE_NAME']);
			}
			$result[] = $row;
		}
		return $result;
	}

	public function deleteSipConnectionAction($configId)
	{
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage("VI_CONFIG_SIP_ACCESS_DENIED"));
			return null;
		}

		$viSip = new CVoxImplantSip();
		$viSip->Delete($configId);
	}

	// statistics
	public function showSipCloudFormAction() {}
	public function showSipOfficeFormAction() {}
	public function buySipConnectorAction() {}
}
