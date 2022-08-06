<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Config\Option;
use \Bitrix\Main\Type\Date;
use Bitrix\Bitrix24;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CIntranetLicenseWidgetComponent extends CBitrixComponent
{
	public function executeComponent(): void
	{
		//This task is frozen for now
		return;
		if (!(
			CurrentUser::get()->getId() > 0
			&& !(Loader::includeModule('extranet') && \CExtranet::isExtranetSite())
			&& ($license = \Bitrix\Main\Application::getInstance()->getLicense())
			&& ($license->isDemo() || $license->isTimeBound())
		))
		{
			return;
		}

		$this->arResult['isDemo'] = $license->isDemo();
		$this->arResult['expireDate'] = $license->getExpireDate();

		$this->includeComponentTemplate();
	}
}
