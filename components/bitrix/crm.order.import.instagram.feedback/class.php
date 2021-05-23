<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CrmOrderImportInstagramFeedback extends CBitrixComponent
{
	protected static function getFeedbackFormInfo($region)
	{
		if ($region == 'ru')
		{
			return ['id' => 46, 'lang' => 'ru', 'sec' => 'wbmv6x'];
		}
		elseif ($region == 'br')
		{
			return ['id' => 54, 'lang' => 'br', 'sec' => '90phl1'];
		}
		elseif ($region == 'la')
		{
			return ['id' => 52, 'lang' => 'la', 'sec' => '2knrdz'];
		}
		elseif ($region == 'de')
		{
			return ['id' => 50, 'lang' => 'de', 'sec' => '3cfxso'];
		}
		elseif ($region == 'ua')
		{
			return ['id' => 56, 'lang' => 'ua', 'sec' => 'b80or8'];
		}
		else // en
		{
			return ['id' => 48, 'lang' => 'en', 'sec' => '4mhlk7'];
		}
	}

	protected function checkModules()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_OIIF_FEEDBACK_MODULE_ERROR'));

			return false;
		}

		return true;
	}

	public function executeComponent()
	{
		Loc::loadLanguageFile(__FILE__);

		if ($this->checkModules())
		{
			global $USER;

			$this->arResult = static::getFeedbackFormInfo(LANGUAGE_ID);
			$this->arResult['type'] = 'slider_inline';
			$this->arResult['fields']['values']['CONTACT_EMAIL'] = \Bitrix\Main\Engine\CurrentUser::get()->getEmail();
			$this->arResult['presets'] = [
				'prod_plan' => \Bitrix\Main\Loader::includeModule('bitrix24') ? \CBitrix24::getLicenseType() : '',
				'typeproduct' => defined('BX24_HOST_NAME') ? 'B24' : 'CP',
				'c_name' => isset($USER) && $USER instanceof \CUser ? $USER->GetFullName() : '',
				'import_count' => \Bitrix\Crm\Order\Import\Instagram::getProductsCount(),
			];
		}

		$this->includeComponentTemplate();
	}
}