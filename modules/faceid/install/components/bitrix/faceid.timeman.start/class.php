<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die;

if (!\Bitrix\Main\Loader::includeModule("faceid"))
	return;

class FaceidTimemanStartComponent extends CBitrixComponent
{
	/**
	 * Start Component
	 */
	public function executeComponent()
	{
		// disabled for some portals
		if (!\Bitrix\FaceId\FaceId::isAvailable())
		{
			die;
		}

		\Bitrix\Main\Loader::includeModule('faceid');

		$this->arResult['IS_B24'] = \Bitrix\Main\Loader::includeModule('bitrix24');

		// portal has license to module
		$this->arResult['TIMEMAN_AVAILABLE'] = \Bitrix\Main\Loader::includeModule('timeman') ||
			($this->arResult['IS_B24'] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("timeman"))
		;

		// portal has license and module is enabled
		$this->arResult['TIMEMAN_ENABLED'] = \Bitrix\Main\Loader::includeModule('timeman') ||
			($this->arResult['IS_B24'] && \Bitrix\Bitrix24\Feature::isFeatureEnabled("timeman")
				&& \Bitrix\Main\Loader::includeModule('timeman')
			)
		;

		// init popup
		if ($this->arResult['IS_B24'])
		{
			\CBitrix24::initLicenseInfoPopupJS();
		}

		// output
		$this->includeComponentTemplate();
	}
}