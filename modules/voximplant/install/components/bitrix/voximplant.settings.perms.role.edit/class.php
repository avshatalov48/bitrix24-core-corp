<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Model;
use Bitrix\Voximplant\Security\Permissions;
use Bitrix\Voximplant\Security\RoleManager;

Loc::loadMessages(__FILE__);

class CVoximplantRoleEditComponent extends CBitrixComponent
{
	protected $new = false;
	protected $saveMode = false;
	protected $isSlider = false;
	protected $id;
	protected $errors;

	public function __construct($component)
	{
		parent::__construct($component);

		\Bitrix\Main\Loader::includeModule('voximplant');

		$this->errors = new \Bitrix\Main\ErrorCollection();

		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$this->id = (int)$request['ID'];
		if($this->id == 0)
			$this->new = true;

		if($request['act'] === 'save' && check_bitrix_sessid())
			$this->saveMode = true;

		if($request['IFRAME'] === 'Y' && $request['IFRAME_TYPE'] === 'SIDE_SLIDER')
		{
			$this->isSlider = true;
		}
	}

	protected function prepareData()
	{
		$this->initKeysArResult();

		if($this->errors->isEmpty())
		{
			$role = Model\RoleTable::getById($this->id)->fetch();
			if(!$role)
			{
				$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('VOXIMPLANT_ROLE_NOT_FOUND'));
				$this->new = true;
			}
			else
			{
				$this->arResult['NAME'] = $role['NAME'];
			}
			$rolePermissions = Permissions::getNormalizedPermissions(RoleManager::getRolePermissions($this->id));
			$this->arResult['ID'] = ($this->new ? 0 : $this->id);
			$this->arResult['PERMISSIONS'] = $rolePermissions;
		}
		else
		{
			$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
			$this->arResult['ID'] = ($this->new ? 0 : $this->id);
			$this->arResult['NAME'] = (string)$request['NAME'];
			$this->arResult['PERMISSIONS'] = (array)$request['PERMISSIONS'];
			$this->arResult['ERRORS'] = $this->errors;
		}

		$this->arResult['PERMISSION_MAP'] = Permissions::getMap();
		$this->arResult['PERMISSIONS_URL'] = CVoxImplantMain::GetPublicFolder()."permissions.php";
		$this->arResult['CAN_EDIT'] = \Bitrix\Voximplant\Security\Helper::canUse();
		if(!$this->arResult['CAN_EDIT'])
		{
			$this->arResult['TRIAL'] = CVoxImplantMain::GetSecurityTrialText();
		}
	}

	protected function initKeysArResult(): void
	{
		$this->arResult['NAME'] = null;
		$this->arResult['ID'] = null;
		$this->arResult['PERMISSION_MAP'] = null;
		$this->arResult['PERMISSIONS'] = null;
		$this->arResult['PERMISSIONS_URL'] = null;
		$this->arResult['ERRORS'] = null;
		$this->arResult['CAN_EDIT'] = null;
		$this->arResult['TRIAL'] = null;
	}

	protected function save()
	{
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$roleId = (int)$request['ID'];
		$roleName = (string)$request['NAME'];
		$permissions = $request['PERMISSIONS'];
		
		if($roleName == '')
		{
			$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('VOXIMPLANT_ROLE_ERROR_EMPTY_NAME'));
			return false;
		}

		if($role = Model\RoleTable::getById($roleId)->fetch())
		{
			$saveResult = Model\RoleTable::update(
				$role['ID'],
				array(
					'NAME' => $roleName
				)
			);
		}
		else
		{
			$saveResult = Model\RoleTable::add(array(
				'NAME' => $request['NAME']
			));
			$roleId = $saveResult->getId();
		}

		if(!$saveResult->isSuccess())
		{
			$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('VOXIMPLANT_ROLE_SAVE_ERROR'));
			return false;
		}
		else if(is_array($permissions))
		{
			RoleManager::setRolePermissions($roleId, $permissions);
		}

		return true;
	}

	public function executeComponent()
	{
		$permissions = Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY))
		{
			ShowError(Loc::getMessage('VOXIMPLANT_ROLE_ERROR_INSUFFICIENT_RIGHTS'));
			return false;
		}

		if($this->saveMode)
		{
			if(\Bitrix\Voximplant\Security\Helper::canUse())
			{
				if($this->save())
				{
					if(!$this->isSlider)
					{
						LocalRedirect(CVoxImplantMain::GetPublicFolder()."permissions.php");
					}
				}
			}
			else
			{
				$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('VOXIMPLANT_ROLE_LICENSE_ERROR'));
			}
		}

		$this->prepareData();
		$this->includeComponentTemplate();
		return $this->arResult;
	}
}