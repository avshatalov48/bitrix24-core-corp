<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

class RpaFeedbackComponent extends \Bitrix\Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		static::fillParameterFromRequest('context', $arParams);
		return $arParams;
	}

	protected function init(): void
	{
		parent::init();

		if(!\Bitrix\Rpa\Driver::getInstance()->getBitrix24Manager()->isEnabled())
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('RPA_FEEDBACK_BITRIX24_ERROR')));
		}
	}


	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$b24Manager = \Bitrix\Rpa\Driver::getInstance()->getBitrix24Manager();

		$this->arResult = $b24Manager->getFeedbackFormInfo();
		$this->arResult['type'] = 'slider_inline';
		$this->arResult['fields']['values']['CONTACT_EMAIL'] = CurrentUser::get()->getEmail();
		$this->arResult['presets'] = [
			'from_domain' => defined('BX24_HOST_NAME') ? BX24_HOST_NAME : Option::get('main', 'server_name', ''),
			'b24_plan' => $b24Manager->getLicenseType(),
			'b24_zone' => $b24Manager->getPortalZone(),
			'c_name' => CurrentUser::get()->getFullName(),
			'sender_page' => $this->arParams['context'] ?? 'panel',
		];

		$this->getApplication()->setTitle(Loc::getMessage('RPA_FEEDBACK_TITLE'));
		$this->includeComponentTemplate();
	}
}