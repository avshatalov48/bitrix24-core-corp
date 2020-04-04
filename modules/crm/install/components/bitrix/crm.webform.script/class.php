<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Script;
use Bitrix\Crm\WebForm\Internals\FormTable;

Loc::loadMessages(__FILE__);


class CCrmWebFormScriptComponent extends \CBitrixComponent
{
	protected $errors = array();

	public function prepareResult()
	{
		if(is_array($this->arParams['FORM']))
		{
			$formData = $this->arParams['FORM'];
		}
		else
		{
			$formData = FormTable::getRowById($this->arParams['FORM_ID']);
		}

		$scriptParams = array();
		$this->arResult = array(
			'FORM_NAME' => $formData['CAPTION'] ? $formData['CAPTION'] : $formData['NAME'],
			'LINK' =>  Script::getUrlContext($formData, $this->arParams['PATH_TO_WEB_FORM_FILL']),
			'SCRIPTS' => Script::getListContext($formData, $scriptParams, $this->arParams['PATH_TO_WEB_FORM_FILL'])
		);
	}

	public function checkParams()
	{
		$this->arParams['CACHE_TIME'] = (int) isset($this->arParams['CACHE_TIME']) ? $this->arParams['CACHE_TIME'] : 3600;
		$this->arParams['FORM_ID'] = (int) $this->arParams['FORM_ID'];
		$this->arParams['SCRIPTS'] = $this->arParams['SCRIPTS'] ? $this->arParams['SCRIPTS'] : $this->request->get('SCRIPTS');
		$this->arParams['TEMPLATE_CONTAINER_ID'] = $this->arParams['TEMPLATE_CONTAINER_ID'] ? $this->arParams['TEMPLATE_CONTAINER_ID'] : '';

		return true;
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return;
		}

		if (!$this->checkParams())
		{
			$this->showErrors();
			return;
		}

		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function checkModules()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if(count($this->errors) <= 0)
		{
			return;
		}

		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}
}