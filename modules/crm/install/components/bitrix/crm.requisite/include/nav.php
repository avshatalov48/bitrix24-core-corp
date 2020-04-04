<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!isset($this->arResult['INTERNAL']) || !$this->arResult['INTERNAL'])
{
	if(isset($this->arResult['CRM_CUSTOM_PAGE_TITLE']))
		$this->getApp()->SetTitle($this->arResult['CRM_CUSTOM_PAGE_TITLE']);
	elseif (isset($this->arResult['ELEMENT']['ID']))
	{
		$this->getApp()->AddChainItem(GetMessage('CRM_REQUISITE_NAV_TITLE_LIST'), $this->arParams['PATH_TO_REQUISITE_LIST']);
		if (!empty($this->arResult['ELEMENT']['ID']))
			$this->getApp()->SetTitle(GetMessage('CRM_REQUISITE_NAV_TITLE_EDIT', array('#NAME#' => $this->arResult['ELEMENT']['TITLE'])));
		else
			$this->getApp()->SetTitle(GetMessage('CRM_REQUISITE_NAV_TITLE_ADD'));
	}
	else
	{
		$this->getApp()->SetTitle(GetMessage('CRM_REQUISITE_NAV_TITLE_LIST'));
	}
}
?>