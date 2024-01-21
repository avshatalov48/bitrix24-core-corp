<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

class IntranetToolInaccessibilityComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->prepareParams();

		$this->includeComponentTemplate();
	}

	private function prepareParams(): void
	{
		$sliderCode = $this->arParams['SLIDER_CODE'] ?? null;
		$locationHref = $this->arParams['LOCATION_HREF'] ?? '/';
		$script = $this->getJs($sliderCode);

		$this->arResult['sliderScript'] = $script;
		$this->arResult['locationHref'] = $locationHref;
	}

	private function getJs(?string $id): string
	{
		if (!$id || !Loader::includeModule('ui'))
		{
			return '';
		}

		return '
			top && top.BX.loadExt("ui.info-helper").then(() => {
				top.BX.UI.InfoHelper.show("' . CUtil::JSEscape($id) . '");
			});
		';
	}
}
