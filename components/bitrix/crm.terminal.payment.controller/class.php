<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Crm;

class CrmTerminalPaymentControllerComponent extends \CBitrixComponent
{
	public const SALE_SECTION = '/shop/terminal/';
	public const CRM_SECTION = '/crm/terminal/';

	private const URL_TEMPLATE_PAYMENT_DETAIL = 'detail';
	private const URL_TEMPLATE_PAYMENT_LIST = 'list';

	private bool $isIframe = false;

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
		$this->initConfig();

		if (Main\Loader::includeModule('crm'))
		{
			/** installing demo data for crm used for PresetCrmStoreMenu creation*/
			\CAllCrmInvoice::installExternalEntities();
		}

		if (!Crm\Terminal\AvailabilityManager::getInstance()->isAvailable())
		{
			$this->redirectToHome();
		}

		$templateUrls = self::getTemplateUrls();

		$variables = [];
		$template = '';
		
		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			[$template, $variables] = $this->processSefMode($templateUrls);
		}
		
		$this->arResult['VARIABLES'] = $variables;
		$this->arResult['REQUESTED_PAGE'] = $this->arParams['REQUESTED_PAGE'];

		$this->includeComponentTemplate($template);
	}

	public function isIframeMode(): bool
	{
		return $this->isIframe;
	}

	protected function initConfig(): void
	{
		$this->isIframe = $this->request->get('IFRAME') === 'Y' && $this->request->get('IFRAME_TYPE') === 'SIDE_SLIDER';
	}

	private static function getTemplateUrls(): array
	{
		return [
			self::URL_TEMPLATE_PAYMENT_LIST => '',
			self::URL_TEMPLATE_PAYMENT_DETAIL => 'details/#PAYMENT_ID#/',
		];
	}

	private function processSefMode($templateUrls): array
	{
		$templateUrls = CComponentEngine::MakeComponentUrlTemplates($templateUrls, $this->arParams['SEF_URL_TEMPLATES']);

		foreach ($templateUrls as $name => $url)
		{
			$this->arResult['PATH_TO'][strtoupper($name)] = $this->arParams['SEF_FOLDER'] . $url;
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

	private function redirectToHome(): void
	{
		$pageLink = '/';

		if (Main\Loader::includeModule('intranet'))
		{
			$pageLink = \CIntranetUtils::getB24FirstPageLink();
		}

		if ($pageLink)
		{
			LocalRedirect($pageLink);
		}
	}
}
