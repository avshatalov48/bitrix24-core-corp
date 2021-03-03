<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

Loc::loadMessages(__FILE__);

class CIntranetUserProfilePasswordComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	protected $errorCollection;

	public function onPrepareComponentParams($params)
	{
		$this->errorCollection = new ErrorCollection();

		return $params;
	}

	protected function listKeysSignedParameters()
	{
		return array(
			'USER_ID'
		);
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	public function configureActions()
	{
		return array();
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	protected function getPermissions()
	{
		static $cache = false;

		if (
			$cache === false
			&& Loader::includeModule('socialnetwork')
		)
		{
			global $USER;

			$currentUserPerms = \CSocNetUserPerms::initUserPerms(
				$USER->getId(),
				$this->arParams["USER_ID"],
				\CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false)
			);

			$result = [
				'edit' => (
					$currentUserPerms["IsCurrentUser"]
					|| (
						$currentUserPerms["Operations"]["modifyuser"]
						&& $currentUserPerms["Operations"]["modifyuser_main"]
					)
				)
			];

			if(
				!ModuleManager::isModuleInstalled("bitrix24")
				&& $USER->isAdmin()
				&& !$currentUserPerms["IsCurrentUser"]
			)
			{
				$result['edit'] = (
					$result['edit']
					&& \CSocNetUser::isCurrentUserModuleAdmin(SITE_ID, false)
				);
			}

			$cache = $result;
		}
		else
		{
			$result = $cache;
		}

		return $result;
	}

	public function saveAction(array $data)
	{
		global $USER;

		if(empty($data) || $data['PASSWORD'] == '')
		{
			$this->errorCollection[] = new Error(Loc::getMessage('INTRANET_USER_PROFILE_NOTHING_TO_SAVE'));
			return null;
		}

		$this->arResult['Permissions'] = $this->getPermissions();
		if(!$this->arResult['Permissions']['edit'])
		{
			$this->errorCollection[] = new Error(Loc::getMessage('INTRANET_USER_PROFILE_ACCESS_DENIED'));
			return null;
		}

		$fields = array(
			'PASSWORD' => $data["PASSWORD"],
			'CONFIRM_PASSWORD' => $data["CONFIRM_PASSWORD"]
		);

		if(!$USER->Update($this->arParams['USER_ID'], $fields))
		{
			$this->errorCollection[] = new Error($USER->LAST_ERROR);
			return null;
		}
		else
		{
			return true;
		}
	}

	public function getFieldInfo()
	{
		$passwordPolicy = CUser::GetGroupPolicy($this->arParams["USER_ID"]);
		$passDesc = is_array($passwordPolicy) && isset($passwordPolicy["PASSWORD_REQUIREMENTS"]) ? $passwordPolicy["PASSWORD_REQUIREMENTS"] : "";

		$fields = array(
			array(
				"title" => Loc::getMessage("INTRANET_USER_PROFILE_FIELD_PASSWORD"),
				"name" => "PASSWORD",
				"type" => "password",
				"editable" => true,
				"data" => array(
					"desc" => $passDesc
				)
			),
			array(
				"title" => Loc::getMessage("INTRANET_USER_PROFILE_FIELD_CONFIRM_PASSWORD"),
				"name" => "CONFIRM_PASSWORD",
				"type" => "password",
				"editable" => true,
				"visibilityPolicy" => 'edit',
			)
		);

		return $fields;
	}

	public function getConfig()
	{
		$formConfig = array(
			array(
				'name' => 'password',
				'title' => Loc::getMessage("INTRANET_USER_PROFILE_SECTION_PASSWORD"),
				'type' => 'section',
				'elements' => array(
					array('name' => 'PASSWORD'),
					array('name' => 'CONFIRM_PASSWORD'),
				),
				'data' => array('isChangeable' => false, 'isRemovable' => false)
			)
		);

		return $formConfig;
	}

	public function executeComponent()
	{
		global $USER;

		\CJSCore::Init("loader");

		$permissions = $this->getPermissions();
		if (!$permissions['edit'] && $USER->GetID() != $this->arParams["USER_ID"])
		{
			return;
		}

		$this->arResult["IsOwnProfile"] = $this->arParams["USER_ID"] === $USER->GetID();
		$this->arResult["FormFields"] = $this->getFieldInfo();
		$this->arResult["FormConfig"] = $this->getConfig();

		$this->includeComponentTemplate();
	}
}
?>