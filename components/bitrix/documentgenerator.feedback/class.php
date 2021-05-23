<?php

use Bitrix\DocumentGenerator\Integration\Bitrix24Manager;
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class DocumentsFeedbackComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		Loc::loadLanguageFile(__FILE__);

		if(!\Bitrix\Main\Loader::includeModule('documentgenerator'))
		{
			ShowError(Loc::getMessage('DOCGEN_FEEDBACK_MODULE_ERROR'));
			$this->includeComponentTemplate();
			return;
		}
		if(!Bitrix24Manager::isEnabled())
		{
			ShowError(Loc::getMessage('DOCGEN_FEEDBACK_BITRIX24_ERROR'));
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult = Bitrix24Manager::getFeedbackFormInfo(LANGUAGE_ID);
		$this->arResult['type'] = 'slider_inline';
		$this->arResult['fields']['values']['CONTACT_EMAIL'] = \Bitrix\Main\Engine\CurrentUser::get()->getEmail();
		$this->arResult['presets'] = [
			'b24_plan' => \CBitrix24::getLicenseType(),
			'b24_zone' => \CBitrix24::getPortalZone(),
			'c_name' => \Bitrix\Main\Engine\CurrentUser::get()->getFullName(),
			'user_status' => \CBitrix24::IsPortalAdmin(\Bitrix\Main\Engine\CurrentUser::get()->getId()),
			'template_name' => $this->arParams['templateName'],
			'template_code' => $this->arParams['templateCode'],
			'sender_page' => $this->arParams['provider'],
		];
		$this->includeComponentTemplate();
	}
}