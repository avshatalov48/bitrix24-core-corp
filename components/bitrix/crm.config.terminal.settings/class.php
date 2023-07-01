<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\SalesCenter\Component\PaymentSlip;

final class CrmConfigTerminalSettings extends CBitrixComponent
{
	public function executeComponent()
	{
		if (
			!Main\Loader::includeModule('salescenter')
			|| !Main\Loader::includeModule('crm')
		)
		{
			return;
		}

		if (!\CCrmSaleHelper::isShopAccess('admin'))
		{
			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				"bitrix:ui.info.error",
				"",
				[
					'TITLE' => Main\Localization\Loc::getMessage('CRM_CONFIG_TERMINAL_ACCESS_DENIED_TITLE'),
					'DESCRIPTION' => Main\Localization\Loc::getMessage('CRM_CONFIG_TERMINAL_ACCESS_DENIED_SUBTITLE', [
						'#LINK_START#' => '<a onclick="top.BX.Helper.show(\'redirect=detail&code=16377052\')" style="cursor: pointer">',
						'#LINK_END#' => '</a>',
					]),
					'IS_HTML' => 'Y',
				]
			);
		}
		else
		{
			$this->fillPreparedSettings();
			$this->includeComponentTemplate();
		}
	}

	/**
	 * @return \Bitrix\Salescenter\PaymentSlip\PaymentSlipManager|null
	 */
	private static function getPaymentSlipManager()
	{
		return \Bitrix\Crm\Integration\SalesCenterManager::getInstance()->getPaymentSlipSenderManager();
	}

	private function fillPreparedSettings(): void
	{
		$paymentSlipManager = self::getPaymentSlipManager();

		$this->arResult['SETTINGS_PARAMS'] = [];

		if ($paymentSlipManager)
		{
			$this->arResult['SETTINGS_PARAMS'] = [
				'isSmsSendingEnabled' => $paymentSlipManager->getConfig()->isSendingEnabled(),
				'isNotificationsEnabled' => $paymentSlipManager->getConfig()->isNotificationsEnabled(),
				'activeSmsServices' => $paymentSlipManager->getConfig()->getAvailableSmsServices(),
				'paymentSlipLinkScheme' => $this->getPaymentSlipLinkScheme(),
				'connectNotificationsLink' => $paymentSlipManager->getConnectNotificationsLink(),
				'connectServiceLink' => $paymentSlipManager->getConnectServiceLink(),
			];
		}
	}

	private function getPaymentSlipLinkScheme(): string
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();
		$host = $request->isHttps() ? 'https' : 'http';

		return (new Main\Web\Uri($host . '://' . $request->getHttpHost() . PaymentSlip::SLIP_LINK_PATH . '/'));
	}
}