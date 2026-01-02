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

		$this->arResult['forms'] = $b24Manager->getFeedbackFormInfo();

		$this->getApplication()->setTitle(Loc::getMessage('RPA_FEEDBACK_TITLE'));
		$this->includeComponentTemplate();
	}
}