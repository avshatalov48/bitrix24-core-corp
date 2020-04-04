<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Voximplant\Security\Permissions;

class VoximplantNumbersAjaxController extends \Bitrix\Main\Engine\Controller
{
	protected $permissions;
	public function __construct(Main\Request $request = null)
	{
		parent::__construct($request);

		Main\Loader::includeModule('voximplant');
		$this->permissions = Permissions::createWithCurrentUser();
	}

	public function getUserOptionsAction($userId)
	{
		$userId = (int)$userId;

		if (!$this->permissions->canPerform(Permissions::ENTITY_USER, Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new Main\Error(GetMessage('ACCESS_DENIED'), 'access_denied');
			return null;
		}

		$userRow = Main\UserTable::getRow([
			'select' => ['UF_VI_BACKPHONE', 'UF_VI_PHONE', 'UF_VI_PHONE_PASSWORD', 'UF_PHONE_INNER'],
			'filter' => ['=ID' => $userId]
		]);

		$viAccount = new \CVoxImplantAccount();

		return [
			'extension' => $userRow['UF_PHONE_INNER'],
			'userLine' => $userRow['UF_VI_BACKPHONE'],
			'phoneEnabled' => $userRow['UF_VI_PHONE'] == 'Y' ? 'Y' : 'N',
			'phoneLogin' => 'phone' . $userId,
			'phonePassword' => $userRow['UF_VI_PHONE_PASSWORD'],
			'phoneServer' => str_replace('voximplant.com', 'bitrixphone.com', $viAccount->GetCallServer()),
		];
	}

	public function saveUserOptionsAction($userId, array $options)
	{
		global $USER_FIELD_MANAGER;

		$userId = (int)$userId;

		if(!CVoxImplantUser::canModify($userId, $this->permissions))
		{
			$this->errorCollection[] = new Main\Error(GetMessage('ACCESS_DENIED'), 'access_denied');
			return null;
		}

		$userRow = Main\UserTable::getRow([
			'select' => ['UF_VI_BACKPHONE', 'UF_VI_PHONE', 'UF_VI_PHONE_PASSWORD', 'UF_PHONE_INNER'],
			'filter' => ['=ID' => $userId]
		]);

		$changedFields = [];

		if($options['extension'] != $userRow['UF_PHONE_INNER'])
		{
			$changedFields['UF_PHONE_INNER'] = trim($options['extension']);
		}

		$viUser = new CVoxImplantUser();
		if($options['phoneEnabled'] != $userRow['UF_VI_PHONE'])
		{
			$changedFields['UF_VI_PHONE'] = $options['phoneEnabled'] == 'Y' ? 'Y' : 'N';

			if($options['phoneEnabled'] == 'N')
			{
				$viUser->UpdateUserPassword($userId, CVoxImplantUser::MODE_PHONE);
			}

			$viUser->SetPhoneActive($userId, $options['phoneEnabled'] == "Y" ? true : false);
		}

		if ($options['phoneEnabled'] == 'Y')
		{
			$options['phonePassword'] = trim($options['phonePassword']);
			if ($options['phonePassword'] != $userRow['UF_VI_PHONE_PASSWORD'])
			{
				$pass = $viUser->UpdateUserPassword($userId, CVoxImplantUser::MODE_PHONE, $options["phonePassword"]);
				if (!$pass)
				{
					$this->errorCollection[] = new Main\Error($viUser->GetError()->msg);
					return null;
				}
			}
		}

		if($options['userLine'] != $userRow['UF_VI_BACKPHONE'])
		{
			$changedFields['UF_VI_BACKPHONE'] = $options['userLine'];
			$viUser->SetUserPhone($userId, $options['userLine']);
		}

		$USER_FIELD_MANAGER->EditFormAddFields("USER", $changedFields);

		$obUser = new CUser();
		if(!$obUser->Update($userId, $changedFields, true))
		{
			$this->errorCollection[] = new Main\Error($obUser->LAST_ERROR);
			return null;
		}

		$viHttp = new CVoxImplantHttp();
		$viHttp->ClearConfigCache();
		CVoxImplantUser::clearCache($userId);

		return true;
	}

	public function getPhoneAuthAction($userId)
	{
		if(!CVoxImplantUser::canModify($userId, $this->permissions))
		{
			$this->errorCollection[] = new Main\Error(GetMessage('ACCESS_DENIED'), 'access_denied');
			return null;
		}

		$viUser = new CVoximplantUser();
		$userInfo = $viUser->GetUserInfo($userId, true);
		if (!is_array($userInfo))
		{
			$this->errorCollection[] = new Main\Error($viUser->GetError()->msg);
			return array();
		}
		unset($userInfo['user_password']);

		return [
			'phoneLogin' => $userInfo["phone_login"],
			'phonePassword' => $userInfo["phone_password"],
		];
	}
}
