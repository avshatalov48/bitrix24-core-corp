<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\DocumentGenerator\Model\Role;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class DocumentGeneratorSettingsPermsComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		if(!Loader::includeModule('documentgenerator'))
		{
			$this->showError(Loc::getMessage('DOCGEN_SETTINGS_PERMS_MODULE_DOCGEN_ERROR'));
			return;
		}

		if(!\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifySettings())
		{
			$this->showError(Loc::getMessage('DOCGEN_SETTINGS_PERMS_PERMISSIONS_ERROR'));
			return;
		}

		$this->arResult['isPermissionsFeatureEnabled'] = Bitrix24Manager::isPermissionsFeatureEnabled();

		$this->arResult['roles'] = \Bitrix\DocumentGenerator\Model\RoleTable::getList()->fetchCollection();
		$roleAccessCodes = $accessCodes = [];
		$roleAccessList = \Bitrix\DocumentGenerator\Model\RoleAccessTable::getList(['select' => [
			'ID', 'ROLE_ID', 'ROLE_NAME' => 'ROLE.NAME', 'ACCESS_CODE',
		]]);
		while($roleAccessCode = $roleAccessList->fetch())
		{
			$roleAccessCodes[$roleAccessCode['ID']] = $roleAccessCode;
			$accessCodes[] = $roleAccessCode['ACCESS_CODE'];
		}

		if(isset($this->arParams['roleId']))
		{
			$this->setTitle(Loc::getMessage('DOCGEN_SETTINGS_PERMS_ADD_ROLE_TITLE'));
			if($this->arParams['roleId'] > 0)
			{
				$roles = $this->arResult['roles'];
				/** @var \Bitrix\Main\ORM\Objectify\Collection $roles */
				$role = $roles->getByPrimary($this->arParams['roleId']);
				if($role)
				{
					/** @var Role $role */
					$this->setTitle(Loc::getMessage('DOCGEN_SETTINGS_PERMS_EDIT_ROLE_TITLE', ['#ROLE#' => $role->getName()]));
				}
				else
				{
					$this->showError(Loc::getMessage('DOCGEN_SETTINGS_PERMS_EDIT_ROLE_NOT_FOUND'));
					$role = new \Bitrix\DocumentGenerator\Model\Role();
				}
			}
			else
			{
				$role = new \Bitrix\DocumentGenerator\Model\Role();
			}
			$this->arResult['role'] = $role;

			$this->includeComponentTemplate('edit_role');
			return;
		}

		$accessCodes = $this->getAccessCodesInfo($accessCodes);
		foreach($roleAccessCodes as $id => $roleAccessCode)
		{
			if(isset($accessCodes[$roleAccessCode['ACCESS_CODE']]))
			{
				$codeDescription = $accessCodes[$roleAccessCode['ACCESS_CODE']];
				$roleAccessCodes[$id]['ACCESS_PROVIDER'] = $codeDescription['provider'];
				$roleAccessCodes[$id]['ACCESS_NAME'] = $codeDescription['name'];
			}
			else
			{
				$roleAccessCodes[$id]['ACCESS_NAME'] = Loc::getMessage('DOCGEN_SETTINGS_PERMS_UNKNOWN_ACCESS_CODE');
			}
		}
		$this->arResult['roleAccessCodes'] = $roleAccessCodes;

		$this->setTitle(Loc::getMessage('DOCGEN_SETTINGS_PERMS_TITLE'));
		$this->includeComponentTemplate();
	}

	/**
	 * @param \Bitrix\DocumentGenerator\Model\Role $role
	 * @return bool|string
	 */
	public function getEditRoleUrl(\Bitrix\DocumentGenerator\Model\Role $role = null)
	{
		static $componentPath;
		if($componentPath === null)
		{
			$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.settings.perms');
			$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
		}
		if(!$componentPath)
		{
			return false;
		}
		$roleId = 0;
		if($role)
		{
			$roleId = $role->getId();
		}
		$uri = new \Bitrix\Main\Web\Uri($componentPath);
		$uri->addParams(['roleId' => $roleId]);

		return $uri->getLocator();
	}

	/**
	 * @param array $accessCodes
	 * @return array
	 */
	protected function getAccessCodesInfo(array $accessCodes)
	{
		$accessManager = new CAccess();
		return $accessManager->GetNames($accessCodes);
	}

	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @param string $title
	 */
	protected function setTitle($title)
	{
		global $APPLICATION;
		$APPLICATION->SetTitle($title);
	}
}