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

		if (
			Loader::includeModule('extranet')
			&& (\CExtranet::isExtranetSite() || !\CExtranet::IsIntranetUser())
		)
		{
			return;
		}

		$intranetUser = new Intranet\User();
		$this->arResult['isCurrentUserAdmin'] = $intranetUser->isAdmin();
		$this->arResult['isInvitationAvailable'] = \CBitrix24::isInvitingUsersAllowed();
		$this->arResult['structureLink'] = '/company/vis_structure.php';
		$this->arResult['invitationLink'] = $this->arResult['isCurrentUserAdmin'] || $this->arResult['isInvitationAvailable']
			? Engine\UrlManager::getInstance()->create('getSliderContent', [
				'c' => 'bitrix:intranet.invitation',
				'mode' => Engine\Router::COMPONENT_MODE_AJAX,
				'analyticsLabel[source]' => 'headerPopup',
			]) : '';
		$this->arResult['isExtranetAvailable'] = Main\ModuleManager::isModuleInstalled('extranet') && !\Bitrix\Socialnetwork\Collab\CollabFeature::isOn();
		$this->arResult['isCollabAvailable'] = Main\ModuleManager::isModuleInstalled('extranet') && \Bitrix\Socialnetwork\Collab\CollabFeature::isOn();
		$this->arResult['invitationCounter'] = $intranetUser->getTotalInvitationCounterValue();
		$this->arResult['counterId'] = Intranet\Invitation::getTotalInvitationCounterId();
		$this->arResult['shouldShowStructureCounter'] = $this->shouldShowStructureCounter();

		$this->includeComponentTemplate();
	}

	private function shouldShowStructureCounter(): bool
	{
		return
			(bool)Main\Config\Option::get('humanresources', 'public_structure_is_available', false) === true
			&& \CUserOptions::GetOption("humanresources", 'first_time_opened', 'N') === 'N'
		;
	}
}
