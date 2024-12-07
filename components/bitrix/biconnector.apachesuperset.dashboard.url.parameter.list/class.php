<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('biconnector');

class ApacheSupersetDashboardUrlParameterComponent extends CBitrixComponent
{
	use Main\ErrorableImplementation;

	public function configureActions()
	{
		return [];
	}

	public function onPrepareComponentParams($arParams)
	{
		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		$checkAccessResult = $this->checkAccess();
		if (!$checkAccessResult->isSuccess())
		{
			foreach ($checkAccessResult->getErrorMessages() as $message)
			{
				$this->arResult['ERROR_MESSAGES'][] = $message;
			}
			$this->includeComponentTemplate();

			return;
		}

		$this->arResult['TITLE'] = Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_TITLE');
		$this->prepareSections();
		$this->prepareColumns();
		$this->includeComponentTemplate();
	}

	private function prepareSections(): void
	{
		$sections = [
			[
				'title' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_GLOBAL_SECTION'),
				'code' => 'global',
				'rows' => $this->formatParameter(UrlParameter\ScopeMap::getGlobals()),
			],
		];

		$map = UrlParameter\ScopeMap::getMap();
		foreach ($map as $scopeCode => $scopeParams)
		{
			$sections[] = [
				'title' => ScopeService::getInstance()->getScopeName($scopeCode),
				'code' => $scopeCode,
				'rows' => $this->formatParameter($scopeParams),
			];
		}

		$this->arResult['SECTIONS']  = $sections;
	}

	private function prepareColumns(): void
	{
		$this->arResult['COLUMNS']  = [
			[
				'code' => 'code',
				'title' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_COLUMN_CODE_TITLE'),
			],
			[
				'code' => 'title',
				'title' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_COLUMN_CODE_NAME'),
			],
			[
				'code' => 'description',
				'title' => Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_COLUMN_CODE_DESCRIPTION'),
			],
		];
	}

	/**
	 * @param UrlParameter\Parameter[] $params
	 * @return array
	 */
	private function formatParameter(array $params): array
	{
		$result = [];
		foreach ($params as $param)
		{
			$result[] = [
				'code' => $param->code(),
				'title' => $param->title(),
				'description' => $param->description(),
			];
		}

		return $result;
	}

	private function checkAccess(): Main\Result
	{
		$result = new Main\Result();

		if (Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled('bi_constructor'))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_BIC_UNAVAILABLE_ERROR')));

			return $result;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_APACHE_SUPERSET_DASHBOARD_URL_PARAM_LIST_BBIC_ACCESS_ERROR')));

			return $result;
		}

		return $result;
	}
}
