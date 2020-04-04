<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\WebForm\Script;
use Bitrix\Crm\WebForm\Internals\FormTable;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmButtonWebFormWidgetComponent extends \CBitrixComponent
{
	protected $errors = array();

	public function prepareResult()
	{
		$this->arResult['FORM_ID'] = $this->arParams['FORM_ID'];
		$this->arResult['REMOVE_COPYRIGHT'] = $this->arParams['REMOVE_COPYRIGHT'];
		$this->arResult['SCRIPT_LOADER'] = '';
		$this->arResult['TITLE'] = $this->arParams['TITLE'];
		if (Loader::includeModule('intranet'))
		{
			$this->arResult['REF_LINK'] = CIntranetUtils::getB24Link('crmwidget');
		}
	}

	public function checkParams()
	{
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