<?php

use Bitrix\Crm\Service\Router;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!Loader::includeModule('crm'))
{
	ShowError('Module "crm" is not installed');
	return;
}

class CrmRouterComponent extends Bitrix\Crm\Component\Base
{
	/** @var Router */
	protected $router;

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

		$isSefMode = true;
		if($this->arParams['isSefMode'] === 'n')
		{
			$isSefMode = false;
		}

		$this->router = \Bitrix\Crm\Service\Container::getInstance()->getRouter();
		$this->router->setSefMode($isSefMode);
		$root = $this->arParams['root'];
		if(is_string($root) && !empty($root))
		{
			$this->router->setRoot($root);
		}
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$parseResult = $this->router->parseRequest();

		if(!$parseResult->isFound())
		{
			$componentName = $this->router->getDefaultComponent();
			$componentParameters = $this->router->getDefaultComponentParameters();
		}
		else
		{
			$componentName = $parseResult->getComponentName();
			$componentParameters = $parseResult->getComponentParameters();
		}

		$this->arResult['componentName'] = $componentName;
		$this->arResult['componentParameters'] = $componentParameters;
		$this->arResult['templateName'] = $parseResult->getTemplateName();
		$this->arResult['isIframe'] = $this->isIframe();
		$this->arResult['isUsePadding'] = $this->isUsePadding($componentName);
		$this->arResult['isPlainView'] = $this->isPlainView($componentName);
		$this->arResult['roots'] = $this->getAllRoots();
		$this->arResult['isUseToolbar'] = 'Y';

		$this->includeComponentTemplate();
	}

	protected function isUsePadding(string $componentName): bool
	{
		return $componentName === 'bitrix:crm.item.automation';
	}

	protected function isPlainView(string $componentName): bool
	{
		return (
			$componentName === 'bitrix:crm.item.details'
			|| $componentName === 'bitrix:crm.quote.details'
		);
	}

	protected function getAllRoots(): array
	{
		$customRoots = array_values($this->router->getCustomRoots());

		$currentRoot = $this->router->getRoot();
		$defaultRoot = $this->router->getDefaultRoot();

		return array_merge($customRoots, [$currentRoot, $defaultRoot]);
	}
}
