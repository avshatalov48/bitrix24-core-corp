<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class CIntranetSocnetEmailSettingsComponent extends \CBitrixComponent
{
	public function executeComponent()
	{
		global $USER;

		\CJSCore::Init(array('clipboard'));

		$this->arResult['EMAIL_FORWARD_TO'] = array();

		if (
			$this->arParams['USER_ID'] == intval($USER->GetID())
			&& \Bitrix\Main\Loader::includeModule('mail')
			&& method_exists('Bitrix\Mail\User','getForwardTo')
		)
		{
			if (ModuleManager::isModuleInstalled('blog'))
			{
				$res = Bitrix\Mail\User::getForwardTo(SITE_ID, $this->arParams['USER_ID'], 'BLOG_POST');
				if (is_array($res))
				{
					list($emailForwardTo) = $res;
					if ($emailForwardTo)
					{
						$this->arResult['EMAIL_FORWARD_TO']['BLOG_POST'] = $emailForwardTo;
					}
				}
			}

			if (ModuleManager::isModuleInstalled('tasks'))
			{
				$res = Bitrix\Mail\User::getForwardTo(SITE_ID, $this->arParams['USER_ID'], 'TASKS_TASK');
				if (is_array($res))
				{
					list($emailForwardTo) = $res;
					if ($emailForwardTo)
					{
						$this->arResult['EMAIL_FORWARD_TO']['TASKS_TASK'] = $emailForwardTo;
					}
				}
			}
		}

		$this->includeComponentTemplate();
	}
}
?>