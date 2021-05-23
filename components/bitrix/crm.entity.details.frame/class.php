<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule("crm");

class CCrmEntityDetailsFrameComponent extends CBitrixComponent
{
	/** @var HttpRequest  */
	protected $request = null;

	public function executeComponent()
	{
		$this->arResult['ENTITY_TYPE_ID'] = isset($this->arParams['~ENTITY_TYPE_ID'])
			? (int)$this->arParams['~ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID'])
			? (int)$this->arParams['~ENTITY_ID'] : 0;
		$this->arResult['EXTRAS'] = isset($this->arParams['~EXTRAS']) && is_array($this->arParams['~EXTRAS'])
			? $this->arParams['~EXTRAS'] : array();

		$this->arResult['ENABLE_TITLE_EDIT'] = isset($this->arParams['~ENABLE_TITLE_EDIT'])
			? (bool)$this->arParams['~ENABLE_TITLE_EDIT'] : false;

		$this->arResult['IFRAME'] = isset($this->request['IFRAME']) && $this->request['IFRAME'] === 'Y';
		$this->arResult['IFRAME_USE_SCROLL'] = $this->request['IFRAME_USE_SCROLL'] == 'Y';
		$this->includeComponentTemplate();
	}
}