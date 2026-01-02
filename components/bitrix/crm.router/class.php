<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Crm\Component;

if (!Loader::includeModule('crm'))
{
	ShowError('Module "crm" is not installed');

	return;
}

class CrmRouterComponent extends Component\Base
{
	public function onPrepareComponentParams($arParams): array
	{
		$arParams = parent::onPrepareComponentParams($arParams);
		if (!is_array($arParams))
		{
			$arParams = [];
		}

		$this->fillParameterFromRequest('isSefMode', $arParams);

		return $arParams;
	}

	public function executeComponent(): void
	{
		$this->init();

		if ($this->getErrors())
		{
			$this->showFirstErrorViaInfoErrorUI();

			return;
		}

		$this->arResult['page'] = $this->router->matchPage($this->request);
		$this->arResult['anchors'] = $this->router->getSidePanelAnchors();
		$this->arResult['isIframe'] = $this->isIframe();

		$this->includeComponentTemplate();
	}

	protected function init(): void
	{
		parent::init();

		$this->router->setSefMode($this->isSefMode());
		$this->router->setRoot($this->getRoot());
	}

	private function isSefMode(): bool
	{
		return ($this->arParams['isSefMode'] ?? null) !== 'n';
	}

	private function getRoot(): ?string
	{
		$root = $this->arParams['root'] ?? null;
		if (!is_string($root) || empty($root))
		{
			return $this->router->getRoot();
		}

		return $root;
	}
}
