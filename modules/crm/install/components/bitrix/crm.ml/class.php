<?php

use Bitrix\Crm\Ml\Scoring;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmMlComponent extends CBitrixComponent
{
	/**
	 * @var ErrorCollection
	 */
	protected ErrorCollection $errors;

	protected function checkRequiredParams(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module "crm" is not installed.'));

			return false;
		}

		return true;
	}

	protected function initParams(): void
	{
		$this->arParams['SEF_MODE'] = $this->arParams['SEF_MODE'] ?? 'Y';
		$this->arParams['SEF_FOLDER'] = $this->arParams['SEF_FOLDER'] ?? '';
		$this->arParams['ELEMENT_ID'] = $this->arParams['ELEMENT_ID'] ?? $this->request->get('id');

		$this->arParams['IFRAME'] = $this->arParams['IFRAME'] ?? true;
		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(['#NOBR#','#/NOBR#'], ['',''], $this->arParams['NAME_TEMPLATE']);
		$this->arResult['PATH_TO_USER_PROFILE'] = $this->arParams['PATH_TO_USER_PROFILE'] ?? '/company/personal/user/#id#/';

		if (!isset($this->arParams['VARIABLE_ALIASES']))
		{
			$this->arParams['VARIABLE_ALIASES'] = [
				'detail' => [],
			];
		}

		$arDefaultUrlTemplates404 = [
			'detail' => '#entity_type#/#id#/detail',
			'list' => 'model/list/',
		];

		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			$arDefaultVariableAliases404 = [];
			$arComponentVariables = ['id'];
			$arVariables = [];

			$arUrlTemplates = CComponentEngine::makeComponentUrlTemplates(
				$arDefaultUrlTemplates404,
				$this->arParams['SEF_URL_TEMPLATES'] ?? ''
			);
			$arVariableAliases = CComponentEngine::makeComponentVariableAliases(
				$arDefaultVariableAliases404,
				$this->arParams['VARIABLE_ALIASES']
			);
			$componentPage = CComponentEngine::parseComponentPath(
				$this->arParams['SEF_FOLDER'],
				$arUrlTemplates,
				$arVariables
			);

			CComponentEngine::initComponentVariables(
				$componentPage,
				$arComponentVariables,
				$arVariableAliases,
				$arVariables
			);

			foreach ($arUrlTemplates as $url => $value)
			{
				$key = 'PATH_TO_' . mb_strtoupper($url);
				$this->arResult[$key] = isset($this->arParams[$key][0])
					? $this->arParams[$key]
					: $this->arParams['SEF_FOLDER'] . $value;
			}
		}
		else
		{
			$arComponentVariables = [$this->arParams['VARIABLE_ALIASES']['id'] ?? 'id'];
			$arDefaultVariableAliases = ['id' => 'id'];
			$arVariables = [];

			$arVariableAliases = CComponentEngine::makeComponentVariableAliases(
				$arDefaultVariableAliases,
				$this->arParams['VARIABLE_ALIASES']
			);
			CComponentEngine::initComponentVariables(
				false,
				$arComponentVariables,
				$arVariableAliases,
				$arVariables
			);

			if (isset($_REQUEST['edit']))
			{
				$componentPage = 'edit';
			}

			global $APPLICATION;

			foreach ($arDefaultUrlTemplates404 as $url => $value)
			{
				$key = 'PATH_TO_' . mb_strtoupper($url);
				$value = mb_substr($value, 0, -1);
				$value = str_replace('/', '&ID=', $value);
				$lang = $_REQUEST['lang'] ?? null;
				$this->arResult[$key] = $APPLICATION->GetCurPage() . "?$value" . ($lang ? "&lang=$lang" : "");
			}
		}

		if (!is_array($this->arResult))
		{
			$this->arResult = [];
		}

		$this->arResult = array_merge(
			array(
				'COMPONENT_PAGE' => $componentPage,
				'VARIABLES' => $arVariables,
				'ALIASES' => $this->arParams['SEF_MODE'] === 'Y' ? []: $arVariableAliases,
				'ID' => isset($arVariables['id']) ? (int)($arVariables['id']) : '',
				'TYPE' => (string)($arVariables['entity_type'] ?? ''),
				'PATH_TO_USER_PROFILE' => $this->arParams['PATH_TO_USER_PROFILE'] ?? ''
			),
			$this->arResult
		);
	}

	protected function prepareResult(): bool
	{
		return true;
	}

	protected function printErrors(): void
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent(): void
	{
		$this->errors = new ErrorCollection();
		$this->initParams();

		if (!$this->checkRequiredParams())
		{
			$this->printErrors();

			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();

			return;
		}

		if (Scoring::isScoringAvailable())
		{
			if (empty($this->arResult['COMPONENT_PAGE']))
			{
				LocalRedirect('/crm/');
			}
			else
			{
				$this->includeComponentTemplate($this->arResult['COMPONENT_PAGE']);
			}
		}
		else
		{
			LocalRedirect('/ai/');
		}
	}
}
