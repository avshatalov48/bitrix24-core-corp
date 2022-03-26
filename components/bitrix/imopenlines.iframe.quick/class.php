<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Imopenlines\Limit;
use Bitrix\ImOpenlines\QuickAnswers\QuickAnswer;

use Bitrix\Main\Localization\Loc;

class BotcontrollerIframeQuick extends CBitrixComponent
{
	/** @var array */
	private $sections = [];

	public function executeComponent()
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		CJSCore::Init(array('fx'));
		$lang = 'en';
		if (in_array($this->arParams['LANG'], ['ru','kz','ua']))
		{
			$lang = 'ru';
		}
		$this->includeComponentLang('class.php', $lang);

		$APPLICATION->restartBuffer();

		$this->arResult['IMOP_ID'] = $this->arParams['IMOP_ID'];
		$this->arResult['CAN_USE_QUICK_ANSWERS'] = Limit::canUseQuickAnswers();
		$listDataManager = new \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager($this->arResult['IMOP_ID']);
		if(!$listDataManager->isHasRights())
		{
			$this->includeComponentTemplate('denied');
			return true;
		}
		QuickAnswer::setDataManager($listDataManager);

		$this->arResult['ALL_URL'] = QuickAnswer::getUrlToList();
		$this->arResult['ALL_COUNT'] = QuickAnswer::getCount();

		$this->sections = array_merge(
			[[
				'NAME' => Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_ALL'),
				'ID' => 0,
				'CODE' => 'ALL',
				'SELECTED' => true,
			]],
			QuickAnswer::getSectionList()
		);

		$this->arResult['SECTIONS'] = $this->getSectionList();
		$this->arResult['BUTTONS'] = $this->prepareSectionsForInterfaceButtons();
		$this->arResult['DARK_MODE'] = ($this->arParams['DARK_MODE'] ?? 'N') === 'Y';

		$this->includeComponentTemplate();
	}

	protected function getSectionList()
	{
		$result = [];

		$converter = \Bitrix\Main\Text\Converter::getHtmlConverter();

		foreach($this->sections as $section)
		{
			$result[(int)$section['ID']] = [
				'NAME' =>  $converter->encode($section['NAME']),
				'ID' => (int)$section['ID'],
				'CODE' =>  $converter->encode($section['CODE']),
				'SELECTED' => false,
			];
		}

		return $result;
	}

	protected function markSelectedSection($sectionId = 0)
	{
		foreach($this->arResult['SECTIONS'] as &$section)
		{
			$section['SELECTED'] = false;
		}
		$this->arResult['SECTIONS'][$sectionId]['SELECTED'] = true;
	}

	protected function prepareSectionsForInterfaceButtons()
	{
		$uri = new \Bitrix\Main\Web\Uri($this->arResult['PATH']);
		if($this->arResult['SEARCH'])
		{
			$uri->addParams(array('search' => $this->arResult['SEARCH']));
		}
		$buttons = [];
		foreach($this->sections as $section)
		{
			$uri->deleteParams(array('sectionId'));
			$uri->addParams(array('sectionId' => $section['ID']));
			$buttons[] = array(
				'TEXT' => $section['NAME'],
				'IS_ACTIVE' => $section['SELECTED'],
				'CLASS' => 'imopenlines-iframe-quick-menu-item',
				'ID' => (int)$section['ID'],
				'URL' => 'javascript:void(0);',
				'ON_CLICK' => 'BX.delegate(window.quickAnswersManagerInstance.setSearchSection('.(int)$section['ID'].', true), window.quickAnswersManagerInstance);'
			);
		}

		return $buttons;
	}
}