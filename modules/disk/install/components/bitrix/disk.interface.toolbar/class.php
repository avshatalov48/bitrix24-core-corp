<?php
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskInterfaceToolbarComponent extends BaseComponent
{
	protected function prepareParams()
	{
		parent::prepareParams();

		if(empty($this->arParams['BUTTONS']) || !is_array($this->arParams['BUTTONS']))
		{
			$this->arParams['BUTTONS'] = array();
		}

		if(isset($this->arParams['TOOLBAR_ID']) && $this->arParams['TOOLBAR_ID'] !== '')
		{
			$this->arParams['TOOLBAR_ID'] = preg_replace('/[^a-z0-9_]/i', '', $this->arParams['TOOLBAR_ID']);
		}
		else
		{
			$this->arParams['TOOLBAR_ID'] = 'toolbar_' . (strtolower(randString(5)));
		}
		if(empty($this->arParams['DROPDOWN_FILTER']) || !is_array($this->arParams['DROPDOWN_FILTER']))
		{
			$this->arParams['DROPDOWN_FILTER'] = null;
			$this->arParams['DROPDOWN_FILTER_CURRENT_LABEL'] = null;
		}
		if(empty($this->arParams['CLASS_NAME']))
		{
			$this->arParams['CLASS_NAME'] = '';
		}

		return $this;
	}

	protected function processActionDefault()
	{
		$this->arResult['DROPDOWN_FILTER_JS'] = null;
		if($this->arParams['DROPDOWN_FILTER'])
		{
			$this->arResult['DROPDOWN_FILTER_JS'] = $this->reformatDropdownToJs($this->arParams['DROPDOWN_FILTER']);
			if(empty($this->arParams['DROPDOWN_FILTER_CURRENT_LABEL']))
			{
				$firstFilter = reset($this->arParams['DROPDOWN_FILTER']);
				$this->arResult['DROPDOWN_FILTER_CURRENT_LABEL'] = $firstFilter['TEXT'];
			}
			else
			{
				$this->arResult['DROPDOWN_FILTER_CURRENT_LABEL'] = $this->arParams['DROPDOWN_FILTER_CURRENT_LABEL'];
			}
		}
		$this->includeComponentTemplate();
	}

	protected function reformatDropdownToJs(array $dropDownItems)
	{
		$jsItems = array();
		foreach($dropDownItems as $item)
		{
			$className = '';
			$text = CUtil::JSEscape($item['TEXT']);
			$title = CUtil::JSEscape($item['TITLE']);
			$href = CUtil::JSEscape($item['HREF']);
			if(!empty($item['CLASS_NAME']))
			{
				$className = ", className : '". CUtil::JSEscape($item['CLASS_NAME']) . "'";
			}
			$jsItems[] = "{text : '{$text}', title : '{$title}', href : '{$href}', {$className} }";
		}
		unset($item);

		return '[' . implode(', ', $jsItems) . ']';
	}
}