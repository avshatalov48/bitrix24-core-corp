<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Main\Engine;

class IntranetInvitationWidgetComponent extends \CBitrixComponent
{
	public function executeComponent(): void
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return;
		}

		if (Loader::includeModule('extranet') && \CExtranet::isExtranetSite())
		{
			return;
		}

		$this->arResult['isCurrentUserAdmin'] = Intranet\CurrentUser::get()->isAdmin();
		$this->arResult['isInvitationAvailable'] = \CBitrix24::isInvitingUsersAllowed();
		$this->arResult['structureLink'] = '/company/vis_structure.php';
		$this->arResult['invitationLink'] = $this->arResult['isCurrentUserAdmin'] || $this->arResult['isInvitationAvailable']
			? Engine\UrlManager::getInstance()->create('getSliderContent', [
				'c' => 'bitrix:intranet.invitation',
				'mode' => Engine\Router::COMPONENT_MODE_AJAX,
				'analyticsLabel[source]' => 'headerPopup',
			]) : '';
		$this->arResult['isExtranetAvailable'] = Main\ModuleManager::isModuleInstalled('extranet');

		$this->includeComponentTemplate();
	}
}
