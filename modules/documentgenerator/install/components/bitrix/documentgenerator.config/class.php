<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class DocumentGeneratorConfigComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	public function executeComponent()
	{
		if(!Loader::includeModule('documentgenerator'))
		{
			$this->showError(Loc::getMessage('DOCGEN_CONFIG_MODULE_DOCGEN_ERROR'));
			return;
		}

		if(!\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifySettings())
		{
			$this->showError(Loc::getMessage('DOCGEN_CONFIG_PERMISSIONS_ERROR'));
			return;
		}

		$this->arResult = [];

		$this->arResult['TOP_VIEW_TARGET_ID'] = false;
		$isIframe = $this->request->get('IFRAME') == 'Y' ? true : false;
		if(SITE_TEMPLATE_ID == "bitrix24")
		{
			if($isIframe)
			{
				$this->arResult['TOP_VIEW_TARGET_ID'] = 'inside_pagetitle';
			}
			else
			{
				$this->arResult['TOP_VIEW_TARGET_ID'] = 'pagetitle';
			}
		}

		$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_CONFIG_TITLE');
		$this->arResult['enablePublicSign'] = (Option::get('documentgenerator', 'document_enable_public_b24_sign', 'Y') == 'Y');

		global $APPLICATION;
		$APPLICATION->SetTitle($this->arResult['TITLE']);

		$this->includeComponentTemplate();
	}

	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @param array $config
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function saveConfigAction(array $config)
	{
		if(!Loader::includeModule('documentgenerator'))
		{
			return;
		}
		if(!\Bitrix\DocumentGenerator\Integration\Bitrix24Manager::isRestrictionsActive())
		{
			Option::set('documentgenerator', 'document_enable_public_b24_sign', $config['document_enable_public_b24_sign']);
		}
	}
}