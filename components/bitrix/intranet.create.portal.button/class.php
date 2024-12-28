<?php

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetCreatePortalButton extends \CBitrixComponent
{
	public function executeComponent(): void
	{
		if (
			!Loader::includeModule('extranet')
			|| !\CExtranet::IsExtranetSite()
			|| !ServiceContainer::getInstance()
				->getCollaberService()
				->isCollaberById((int)CurrentUser::get()->getId())
		)
		{
			return;
		}

		$this->arResult['CREATE_URL'] = (new Uri('https://www.bitrix24.net/create/'))->getLocator();

		$this->includeComponentTemplate();
	}
}
