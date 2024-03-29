<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Tracking;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CrmTrackingComponent extends CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			return false;
		}
		if (!Tracking\Manager::isAccessible())
		{
			$this->errors->setError(new Error('Not available.'));
			return false;
		}
		return true;
	}

	protected function initParams()
	{
		$this->arParams['SEF_MODE'] = isset($this->arParams['SEF_MODE']) ? $this->arParams['SEF_MODE'] : 'Y';
		$this->arParams['SEF_FOLDER'] = isset($this->arParams['SEF_FOLDER']) ? $this->arParams['SEF_FOLDER'] : '';
		$this->arParams['ELEMENT_ID'] = isset($this->arParams['ELEMENT_ID']) ? $this->arParams['ELEMENT_ID'] : $this->request->get('id');

		$this->arParams['IFRAME'] = isset($this->arParams['IFRAME']) ? $this->arParams['IFRAME'] : true;
		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);
		$this->arResult['PATH_TO_USER_PROFILE'] = isset($this->arParams['PATH_TO_USER_PROFILE']) ? $this->arParams['PATH_TO_USER_PROFILE'] : '/company/personal/user/#id#/';

		if (!isset($this->arParams['VARIABLE_ALIASES']))
		{
			$this->arParams['VARIABLE_ALIASES'] = array(
				"list" => [],
				"edit" => [],
				"expenses" => [],
				"call" => [],
				"mail" => [],
				"site" => [],
				"order" => [],
				"channel" => [],
				"settings" => [],
			);
		}

		$arDefaultUrlTemplates404 = array(
			"list" => "list/",
			"add" => "source/edit/0/",
			"edit" => "source/edit/#id#/",
			"archive" => "source/archive/",
			"expenses" => "expenses/#id#/",
			"channel" => "channel/#id#/",
			"utm" => "source/utm/?code=#id#",
			"call" => "channel/call/",
			"mail" => "channel/mail/",
			"site" => "channel/site/#id#/",
			"site24" => "channel/site24/",
			"shop24" => "channel/shop24/",
			"crm-shop" => "channel/crm-shop/",
			"order" => "channel/order/",
			"settings" => "settings/",
		);

		$componentPage = 'list';
		if ($this->arParams['SEF_MODE'] == 'Y')
		{
			$arDefaultVariableAliases404 = array();
			$arComponentVariables = array('id');
			$arVariables = array();
			$arUrlTemplates = CComponentEngine::makeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams['SEF_URL_TEMPLATES'] ?? []);
			$arVariableAliases = CComponentEngine::makeComponentVariableAliases($arDefaultVariableAliases404, $this->arParams['VARIABLE_ALIASES']);
			$componentPage = CComponentEngine::parseComponentPath($this->arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

			if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
			{
				$componentPage = 'list';
			}

			CComponentEngine::initComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
			foreach ($arUrlTemplates as $url => $value)
			{
				$key = 'PATH_TO_'.mb_strtoupper($url);
				$this->arResult[$key] = isset($this->arParams[$key][0]) ? $this->arParams[$key] : $this->arParams['SEF_FOLDER'] . $value;
			}

		}
		else
		{
			$arComponentVariables = array(
				isset($this->arParams['VARIABLE_ALIASES']['id']) ? $this->arParams['VARIABLE_ALIASES']['id'] : 'id'
			);

			$arDefaultVariableAliases = array(
				'id' => 'id'
			);
			$arVariables = array();
			$arVariableAliases = CComponentEngine::makeComponentVariableAliases($arDefaultVariableAliases, $this->arParams['VARIABLE_ALIASES']);
			CComponentEngine::initComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

			if (isset($_REQUEST['edit']))
			{
				$componentPage = 'edit';
			}

			/**	@var CMain $APPLICATION */
			global $APPLICATION;
			foreach ($arDefaultUrlTemplates404 as $url => $value)
			{
				$key = 'PATH_TO_'.mb_strtoupper($url);
				$value = mb_substr($value, 0, -1);
				$value = str_replace('/', '&ID=', $value);
				$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : null;
				$this->arResult[$key] = $APPLICATION->GetCurPage() . "?$value" . ($lang ? "&lang=$lang" : '');
			}
		}

		$componentPage = $componentPage == 'add' ? 'edit' : $componentPage;

		if (!is_array($this->arResult))
		{
			$this->arResult = array();
		}

		$this->arResult = array_merge(
			array(
				'COMPONENT_PAGE' => $componentPage,
				'VARIABLES' => $arVariables,
				'ALIASES' => $this->arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
				'ID' => isset($arVariables['id']) ? strval($arVariables['id']) : '',
				'PATH_TO_USER_PROFILE' => $this->arParams['PATH_TO_USER_PROFILE'] ?? null
			),
			$this->arResult
		);
	}

	protected function prepareResult()
	{
		return true;
	}

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		$this->initParams();
		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
			return;
		}

		$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
		$isAvailable = $toolsManager->checkCrmAvailability();
		if (!$isAvailable)
		{
			print AvailabilityManager::getInstance()->getCrmInaccessibilityContent();

			return false;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->includeComponentTemplate($this->arResult['COMPONENT_PAGE']);
	}
}