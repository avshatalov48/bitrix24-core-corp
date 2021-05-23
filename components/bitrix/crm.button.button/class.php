<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\SiteButton\Internals\ButtonTable;
use Bitrix\Crm\SiteButton\Manager;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);


class CCrmSiteButtonButtonComponent extends \CBitrixComponent
{
	protected $errors = array();

	public function prepareResult()
	{
		$this->arResult['COLOR_BACKGROUND'] = htmlspecialcharsbx($this->arParams['COLOR_BACKGROUND']);
		$this->arResult['COLOR_ICON'] = htmlspecialcharsbx($this->arParams['COLOR_ICON']);
		if(!$this->arResult['COLOR_BACKGROUND'])
		{
			$this->arResult['COLOR_BACKGROUND'] = '#339BFF';
		}
		if(!$this->arResult['COLOR_ICON'])
		{
			$this->arResult['COLOR_ICON'] = '#FFFFFF';
		}

		switch (intval($this->arParams['LOCATION']))
		{
			case ButtonTable::ENUM_LOCATION_TOP_LEFT:
				$this->arResult['LOCATION'] = 'top-left';
				break;
			case ButtonTable::ENUM_LOCATION_TOP_MIDDLE:
				$this->arResult['LOCATION'] = 'top-middle';
				break;
			case ButtonTable::ENUM_LOCATION_TOP_RIGHT:
				$this->arResult['LOCATION'] = 'top-right';
				break;
			case ButtonTable::ENUM_LOCATION_BOTTOM_LEFT:
				$this->arResult['LOCATION'] = 'bottom-left';
				break;
			case ButtonTable::ENUM_LOCATION_BOTTOM_MIDDLE:
				$this->arResult['LOCATION'] = 'bottom-middle';
				break;

			case ButtonTable::ENUM_LOCATION_BOTTOM_RIGHT:
			default:
				$this->arResult['LOCATION'] = 'bottom-right';
				break;
		}

		if(!isset($this->arParams['WIDGETS']))
		{
			$this->arResult['WIDGETS'] = array_keys(Manager::getTypeList());
		}
		else
		{
			$this->arResult['WIDGETS'] = $this->arParams['WIDGETS'];
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