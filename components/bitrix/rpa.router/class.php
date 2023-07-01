<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	ShowError(\Bitrix\Main\Localization\Loc::getMessage('RPA_MODULE_ERROR'));
	return;
}

class RpaRouterComponent extends Bitrix\Rpa\Components\Base
{
	/** @var \Bitrix\Rpa\UrlManager */
	protected $urlManager;

	public function onPrepareComponentParams($arParams): array
	{
		$arParams = parent::onPrepareComponentParams($arParams);

		if(!is_array($arParams))
		{
			$arParams = [];
		}

		$this->fillParameterFromRequest('isSefMode', $arParams);

		return $arParams;
	}

	protected function init(): void
	{
		parent::init();

		if (!Main\Loader::includeModule('bizproc'))
		{
			$this->errorCollection->setError(new Main\Error(Loc::getMessage('BIZPROC_MODULE_ERROR')));
		}

		$isSefMode = true;
		if(isset($this->arParams['isSefMode']) && $this->arParams['isSefMode'] === 'n')
		{
			$isSefMode = false;
		}

		$this->arResult['taskCountersPullTag'] = \Bitrix\Rpa\Driver::getInstance()->getPullManager()->subscribeOnTaskCounters();
		$this->urlManager = \Bitrix\Rpa\Driver::getInstance()->getUrlManager();
		$this->urlManager->setSefMode($isSefMode);
		$root = $this->arParams['root'];
		if(is_string($root) && !empty($root))
		{
			$this->urlManager->setRoot($root);
		}
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->getApplication()->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('RPA_TOP_PANEL_PANEL'));
			$this->includeComponentTemplate();
			return;
		}

		$parseResult = $this->urlManager->parseRequest();

		if(!$parseResult->isFound())
		{
			$componentName = $this->getDefaultComponent();
		}
		else
		{
			$componentName = $parseResult->getComponentName();
		}

		$this->arResult['urlTemplates'] = $this->urlManager->getPreparedTemplates();

		$this->arResult['componentName'] = $componentName;
		$this->arResult['componentParameters'] = $parseResult->getComponentParameters();
		$this->arResult['templateName'] = $parseResult->getTemplateName();

		$this->includeComponentTemplate();
	}

	protected function getDefaultComponent(): string
	{
		return 'bitrix:rpa.panel';
	}
}
