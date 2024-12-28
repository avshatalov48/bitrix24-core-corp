<?php

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loader::includeModule("biconnector");

class ApacheSupersetExternalDatasetController extends CBitrixComponent
{

	private const URL_TEMPLATE_DATASET = 'dataset';
	private const URL_TEMPLATE_SOURCE = 'source';

	public function onPrepareComponentParams($arParams)
	{
		$arParams['SEF_URL_TEMPLATES'] ??= [];
		$arParams['VARIABLE_ALIASES'] ??= [];

		return parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS))
		{
			$this->printError(
				Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_CONTROLLER_PERMISSION_SUPERSET_ERROR')
			);

			return;
		}

		if (!SupersetInitializer::isSupersetExist())
		{
			LocalRedirect('/bi/dashboard');
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			$this->printError(
				Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_CONTROLLER_PERMISSION_ERROR')
			);

			return;
		}

		$template = '';
		$templateUrls = self::getTemplateUrls();

		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			$template = $this->processSefMode($templateUrls);
		}

		if (!isset($this->arParams['COMPONENT_PARAMS']) || !is_array($this->arParams['COMPONENT_PARAMS']))
		{
			$this->arParams['COMPONENT_PARAMS'] = [];
		}
		$this->arParams['COMPONENT_PARAMS']['IFRAME'] = true;

		$this->includeComponentTemplate($template);
	}

	private function processSefMode($templateUrls): string
	{
		$variables = [];
		$template = CComponentEngine::ParseComponentPath(
			$this->arParams['SEF_FOLDER'],
			$templateUrls,
			$variables
		);

		if (!is_string($template) || !isset($templateUrls[$template]))
		{
			$template = key($templateUrls);
		}

		return $template;
	}

	/**
	 * Show component errors.
	 *
	 * @return void
	 */
	private function printError($errorMessage): void
	{
		Toolbar::deleteFavoriteStar();
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'TITLE' => $errorMessage,
			]
		);
	}

	private static function getTemplateUrls(): array
	{
		return [
			self::URL_TEMPLATE_DATASET => 'bi/dataset/',
			self::URL_TEMPLATE_SOURCE => 'bi/source/',
		];
	}
}
