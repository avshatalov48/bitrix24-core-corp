<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImOpenlines\Security\Permissions;

Loc::loadMessages(__FILE__);

class CImOpenLinesReportBoardComponent extends \CBitrixComponent
{
	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		$result = false;

		if (Loader::includeModule('imopenlines'))
		{
			$result = true;
		}
		else
		{
			\ShowError(Loc::getMessage('IMOL_COMPONENT_MODULE_NOT_INSTALLED'));
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	protected function checkAccess()
	{
		$result = true;

		$userPermissions = Permissions::createWithCurrentUser();
		if(!$userPermissions->canViewStatistics())
		{
			\ShowError(Loc::getMessage('OL_COMPONENT_ACCESS_DENIED'));
			$result = false;
		}

		return $result;
	}

	/**
	 * @throws LoaderException
	 */
	public function executeComponent()
	{
		$this->includeComponentLang('class.php');
		if(!Loader::includeModule('report'))
		{
			return false;
		}

		if($this->checkModules())
		{
			if ($this->checkAccess())
			{
				$this->includeComponentTemplate();
			}
			else
			{
				return false;
			}
		}
	}
}