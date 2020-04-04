<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm;

Loc::loadMessages(__FILE__);


class CCrmWebFormScriptComponent extends \CBitrixComponent
{
	protected $errors = array();

	public function prepareResult()
	{
		if(is_array($this->arParams['FORM']))
		{
			$formData = $this->arParams['FORM'];
			$isAvailableEmbedding = $this->arParams['IS_AVAILABLE_EMBEDDING'];
		}
		else
		{
			$form = new WebForm\Form($this->arParams['FORM_ID']);
			$formData = $form->get();
			$isAvailableEmbedding = $form->isEmbeddingAvailable();
		}

		$scriptParams = array();
		$this->arResult = array(
			'FORM_NAME' => $formData['CAPTION'] ? $formData['CAPTION'] : $formData['NAME'],
			'LINK' =>  WebForm\Script::getUrlContext($formData, $this->arParams['PATH_TO_WEB_FORM_FILL']),
			'SCRIPTS' => WebForm\Script::getListContext($formData, $scriptParams, $this->arParams['PATH_TO_WEB_FORM_FILL']),
			'VIEWS' => $formData['FORM_SETTINGS']['VIEWS'],
			'IS_AVAILABLE_EMBEDDING' => $isAvailableEmbedding && WebForm\Manager::isEmbeddingAvailable()
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