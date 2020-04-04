<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenlines\Model;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CImOpenlinesRoleEditComponent extends CBitrixComponent
{
	protected $new = false;
	protected $saveMode = false;
	protected $id;
	protected $errors;

	public function __construct($component)
	{
		parent::__construct($component);

		$this->errors = new \Bitrix\Main\ErrorCollection();

		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		$this->id = (int)$request['ID'];
		if($this->id == 0)
			$this->new = true;

		if($request['act'] === 'save' && check_bitrix_sessid())
			$this->saveMode = true;
	}

	protected function checkModules()
	{
		$result = true;
		if(!Loader::includeModule('imopenlines'))
		{
			ShowError(Loc::getMessage('IMOL_PERM_MODULE_ERROR'));
			$result = false;
		}

		return $result;
	}

	protected function prepareData()
	{
		if($this->errors->isEmpty())
		{
			$role = Model\RoleTable::getById($this->id)->fetch();
			if(!$role)
			{
				$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('IMOL_ROLE_NOT_FOUND'));
				$this->new = true;
			}
			else
			{
				$this->arResult['NAME'] = $role['NAME'];
			}
			$rolePermissions = \Bitrix\ImOpenlines\Security\Permissions::getNormalizedPermissions(
				\Bitrix\ImOpenlines\Security\RoleManager::getRolePermissions($this->id)
			);
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

		$this->arResult['PERMISSION_MAP'] = \Bitrix\ImOpenlines\Security\Permissions::getMap();
		$this->arResult['PERMISSIONS_URL'] = \Bitrix\ImOpenlines\Common::getPublicFolder()."permissions.php";
		$this->arResult['CAN_EDIT'] = \Bitrix\ImOpenlines\Security\Helper::canUse();
		if(!$this->arResult['CAN_EDIT'])
		{
			$this->arResult['TRIAL'] = \Bitrix\ImOpenlines\Security\Helper::getTrialText();
		}

		$this->arResult['IFRAME'] = $this->request['IFRAME'] === 'Y';

		$uri = new \Bitrix\Main\Web\Uri(htmlspecialchars_decode(POST_FORM_ACTION_URI));
		$uri->addParams(array('action-line' => 'role-add'));
		$this->arResult['ACTION_URI'] = htmlspecialcharsbx($uri->getUri());
	}

	protected function save()
	{
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$roleId = (int)$request['ID'];
		$roleName = (string)$request['NAME'];
		$permissions = $request['PERMISSIONS'];

		if($roleName == '')
		{
			$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('IMOL_ROLE_ERROR_EMPTY_NAME'));
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
			$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('IMOL_ROLE_SAVE_ERROR'));
			return false;
		}
		else if(is_array($permissions))
		{
			\Bitrix\ImOpenlines\Security\RoleManager::setRolePermissions($roleId, $permissions);
		}

		return true;
	}

	public function executeComponent()
	{
		if ($this->checkModules())
		{
			$permissions = \Bitrix\ImOpenlines\Security\Permissions::createWithCurrentUser();
			if(!$permissions->canPerform(\Bitrix\ImOpenlines\Security\Permissions::ENTITY_SETTINGS, \Bitrix\ImOpenlines\Security\Permissions::ACTION_MODIFY))
			{
				ShowError(Loc::getMessage('IMOL_ROLE_ERROR_INSUFFICIENT_RIGHTS'));
				return false;
			}

			if($this->saveMode)
			{
				if(\Bitrix\ImOpenlines\Security\Helper::canUse())
				{
					$this->save();
				}
				else
				{
					$this->errors[] = new \Bitrix\Main\Error(Loc::getMessage('IMOL_ROLE_LICENSE_ERROR'));
				}
			}

			$this->prepareData();
			$this->includeComponentTemplate();
		}

		return $this->arResult;
	}
}