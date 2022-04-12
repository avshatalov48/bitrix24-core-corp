<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Engine\Contract\Controllerable,
	Bitrix\Main\NotSupportedException,
	Bitrix\Main\NotImplementedException;

IncludeModuleLangFile(__FILE__);

class SalesCenterCrmStore extends CBitrixComponent implements Controllerable
{
	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			return;
		}
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	private function prepareResult()
	{
		if(in_array($this->getZone(), ['ru','by','kz']))
		{
			$this->arResult['URL'] = 'https://www.youtube.com/embed/NoNRcCsWmjw?feature=oembed';
		}
		else
		{
			$this->arResult['URL'] = 'https://www.youtube.com/embed/C9-_EaSZ3p4?feature=oembed';
		}
	}

	private function getZone()
	{
		return \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}
}