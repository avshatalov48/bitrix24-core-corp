<?php

use Bitrix\Crm\Order\Import\Instagram;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CrmOrderImportInstagramObserver extends CBitrixComponent
{
	protected function checkModules()
	{
		if (!Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_OIIO_FEEDBACK_MODULE_ERROR'));

			return false;
		}

		return true;
	}

	protected function getImportPath()
	{
		$path = new Uri(Option::get('crm', 'path_to_order_import_instagram'));
		$path->addParams(['show_new' => 'y']);

		return $path->getUri();
	}

	public function executeComponent()
	{
		if ($this->checkModules() && Instagram::isAvailable())
		{
			$this->arResult['HAS_NEW_MEDIA'] = Instagram::checkNewMediaOption();
			$this->arResult['PATH_TO_IMPORT'] = $this->getImportPath();
			$this->includeComponentTemplate();
		}
	}
}