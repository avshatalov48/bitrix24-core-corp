<?php

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class DocumentGeneratorRegionComponent extends CBitrixComponent
{
	/**
	 * @param array $params
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		$params = parent::onPrepareComponentParams($params);

		return $params;
	}

	public function executeComponent()
	{
		if(!Loader::includeModule('documentgenerator'))
		{
			$this->showError(Loc::getMessage('DOCGEN_REGION_MODULE_DOCGEN_ERROR'));
			return;
		}

		if(!\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyTemplates())
		{
			$this->showError(Loc::getMessage('DOCGEN_REGION_PERMISSIONS_ERROR'));
			return;
		}

		$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_REGION_ADD_REGION_TITLE');
		$regionId = isset($this->arParams['id']) && $this->arParams['id'] > 0 ? intval($this->arParams['id']) : false;
		if($regionId > 0)
		{
			$region = \Bitrix\DocumentGenerator\Model\RegionTable::getById($regionId)->fetch();
			if($region)
			{
				$this->arResult['region'] = $this->prepareCultureData($region);
				$this->arResult['region']['phrases'] = DataProviderManager::getInstance()->getRegionPhrases($regionId);
				$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_REGION_EDIT_REGION_TITLE', ['#TITLE#' => $region['TITLE']]);
			}
		}

		$this->arResult['cultures'] = $this->getCultures();
		$this->arResult['nameFormats'] = $this->getNameFormats();
		$this->arResult['dateFormats'] = $this->getDateFormats();
		$this->arResult['timeFormats'] = $this->getTimeFormats();
		$this->arResult['phrases'] = $this->getPhrases();

		global $APPLICATION;
		$APPLICATION->SetTitle($this->arResult['TITLE']);

		$this->includeComponentTemplate();
	}

	protected function showError($error)
	{
		ShowError($error);
		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	protected function getCultures()
	{
		$cultures = $languageIds = [];
		$cultureList = \Bitrix\Main\Localization\CultureTable::getList();
		while($culture = $cultureList->fetch())
		{
			$languageIds[$culture['CODE']] = $culture['ID'];
			$cultures[$culture['ID']] = $this->prepareCultureData($culture);
		}
		if(empty($languageIds))
		{
			return $cultures;
		}
		$languageList = \Bitrix\Main\Localization\LanguageTable::getList(['filter' => ['@LID' => array_keys($languageIds)]]);
		while($language = $languageList->fetch())
		{
			$cultures[$languageIds[$language['LID']]]['NAME'] = $language['NAME'];
		}

		return $cultures;
	}

	/**
	 * @return array
	 */
	protected function getNameFormats()
	{
		return \CSite::GetNameTemplates();
	}

	/**
	 * @return array
	 */
	protected function getDateFormats()
	{
		return ["DD.MM.YYYY", "DD/MM/YYYY", "MM.DD.YYYY", "MM/DD/YYYY", "YYYY/MM/DD", "YYYY-MM-DD"];
	}

	/**
	 * @return array
	 */
	protected function getTimeFormats()
	{
		return ["HH:MI:SS", "H:MI:SS T"];
	}

	/**
	 * @param array $culture
	 * @return array
	 */
	protected function prepareCultureData(array $culture)
	{
		$culture['FORMAT_TIME'] = str_replace($culture["FORMAT_DATE"]." ", "", $culture["FORMAT_DATETIME"]);
		return $culture;
	}

	/**
	 * @return array
	 */
	protected function getPhrases()
	{
		$phrases = [];
		$phraseCodes = array_keys(DataProviderManager::getInstance()->getRegionPhrases('ru'));
		foreach($phraseCodes as $code)
		{
			$meaning = Loc::getMessage('DOCGEN_'.$code);
			if(!$meaning)
			{
				$meaning = $code;
			}
			$phrases[$code] = $meaning;
		}

		return $phrases;
	}
}