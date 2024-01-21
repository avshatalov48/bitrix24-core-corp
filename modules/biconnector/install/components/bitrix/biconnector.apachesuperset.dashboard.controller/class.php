<?php

use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class ApacheSupersetDashboardController extends CBitrixComponent
{
	private const URL_TEMPLATE_LIST = 'list';
	private const URL_TEMPLATE_DETAIL = 'detail';

	public function onPrepareComponentParams($params)
	{
		if (!is_array($params))
		{
			$params = [];
		}

		$params['SEF_URL_TEMPLATES'] = $params['SEF_URL_TEMPLATES'] ?? [];
		$params['VARIABLE_ALIASES'] = $params['VARIABLE_ALIASES'] ?? [];

		return parent::onPrepareComponentParams($params);
	}

	public function executeComponent()
	{
		$templateUrls = self::getTemplateUrls();

		$variables = [];
		$template = '';

		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			[$template, $variables] = $this->processSefMode($templateUrls);
		}

		$this->arResult['VARIABLES'] = $variables;

		$this->arResult['CAN_SEND_STARTUP_METRIC'] = self::canSendStartupSupersetMetric();

		$this->arResult['ERROR_MESSAGES'] = [];
		$this->arResult['FEATURE_AVAILABLE'] = true;
		$this->arResult['TOOLS_AVAILABLE'] = true;
		$this->arResult['HELPER_CODE'] = null;

		if (!\CCrmPerms::IsAccessEnabled())
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_APACHESUPERSET_DASHBOARD_CONTROLLER');
			$this->includeComponentTemplate($template);

			return;
		}

		if (Option::get('biconnector', 'release_bi_superset', 'N') !== 'Y')
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_APACHESUPERSET_DASHBOARD_CONTROLLER');
			$this->includeComponentTemplate($template);

			return;
		}

		if (
			!Loader::includeModule('bitrix24')
			|| !\Bitrix\Bitrix24\Feature::isFeatureEnabled('bi_constructor')
		)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_APACHESUPERSET_DASHBOARD_CONTROLLER');
			$this->arResult['FEATURE_AVAILABLE'] = false;
			$this->arResult['HELPER_CODE'] = 'limit_crm_BI_constructor';
			$this->includeComponentTemplate($template);

			return;
		}

		if (
			class_exists('Bitrix\Intranet\Settings\Tools\ToolsManager')
			&& !ToolsManager::getInstance()->checkAvailabilityByMenuId('crm_bi')
		)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_APACHESUPERSET_DASHBOARD_CONTROLLER');
			$this->arResult['TOOLS_AVAILABLE'] = false;
			$this->arResult['HELPER_CODE'] = 'limit_BI_off';
		}

		$this->includeComponentTemplate($template);
	}

	private static function canSendStartupSupersetMetric(): bool
	{
		$supersetStatus = \Bitrix\BIConnector\Integration\Superset\SupersetInitializer::getSupersetStatus();
		$metricAlreadySend = \Bitrix\Main\Config\Option::get('biconnector', 'superset_startup_metric_send', false);

		return (
			$supersetStatus === \Bitrix\BIConnector\Integration\Superset\SupersetInitializer::SUPERSET_STATUS_READY
			&& !$metricAlreadySend
		);
	}

	private static function getTemplateUrls(): array
	{
		return [
			self::URL_TEMPLATE_LIST => 'bi/dashboard/',
			self::URL_TEMPLATE_DETAIL => 'bi/dashboard/detail/',
		];
	}

	private function processSefMode($templateUrls): array
	{
		$templateUrls = CComponentEngine::MakeComponentUrlTemplates($templateUrls, $this->arParams['SEF_URL_TEMPLATES']);

		foreach ($templateUrls as $name => $url)
		{
			$this->arResult['PATH_TO'][strtoupper($name)] = $this->arParams['SEF_FOLDER'].$url;
		}

		$variableAliases = CComponentEngine::MakeComponentVariableAliases([], $this->arParams['VARIABLE_ALIASES']);

		$variables = [];
		$template = CComponentEngine::ParseComponentPath($this->arParams['SEF_FOLDER'], $templateUrls, $variables);

		if (!is_string($template) || !isset($templateUrls[$template]))
		{
			$template = key($templateUrls);
		}

		CComponentEngine::InitComponentVariables($template, [], $variableAliases, $variables);

		return [$template, $variables, $variableAliases];
	}
}
