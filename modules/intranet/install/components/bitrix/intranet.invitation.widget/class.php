<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Main\Engine;

class IntranetInvitationWidgetComponent extends \CBitrixComponent implements Engine\Contract\Controllerable
{
	public function onPrepareComponentParams($arParams)
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return parent::onPrepareComponentParams($arParams);
		}

		if (
			Loader::includeModule('extranet')
			&& (\CExtranet::isExtranetSite() || !\CExtranet::IsIntranetUser())
		)
		{
			return parent::onPrepareComponentParams($arParams);
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
		$collabFeatureOn = Loader::includeModule('socialnetwork') &&
			\Bitrix\Socialnetwork\Collab\CollabFeature::isOn();
		$this->arResult['isExtranetAvailable'] = Main\ModuleManager::isModuleInstalled('extranet') && !$collabFeatureOn;
		$this->arResult['isCollabAvailable'] = 
			Main\ModuleManager::isModuleInstalled('extranet') 
			&& $collabFeatureOn 
			&& ToolsManager::getInstance()->checkAvailabilityByToolId('collab');
		;
		$this->arResult['invitationCounter'] = $intranetUser->getTotalInvitationCounterValue();
		$this->arResult['counterId'] = Intranet\Invitation::getTotalInvitationCounterId();
		$this->arResult['shouldShowStructureCounter'] = $this->shouldShowStructureCounter();
		$this->prepareSkeleton();

		return parent::onPrepareComponentParams($arParams);
	}

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

		$this->includeComponentTemplate();
	}

	private function prepareSkeleton(): void
	{
		$items = [
			[
				'type' => 'item', 'height' => 46,
			],
			[
				'type' => 'split', 'height' => 90,
			],
			[
				'type' => 'item', 'height' => 41,
			],
			[
				'type' => 'item', 'height' => 64,
			],
		];

		$this->arResult['skeleton'] = [
			'header' => false,
			'footer' => false,
			'items' => $items,
		];
	}

	public function getDataAction(): array
	{
		return $this->arResult;
	}

	private function shouldShowStructureCounter(): bool
	{
		return
			(bool)Main\Config\Option::get('humanresources', 'public_structure_is_available', false) === true
			&& \CUserOptions::GetOption("humanresources", 'first_time_opened', 'N') === 'N'
		;
	}

	public function configureActions(): array
	{
		return [];
	}
}
