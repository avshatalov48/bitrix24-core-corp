<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\SalesCenterManager;
use Bitrix\Crm\Terminal\Config\TerminalPaysystemManager;
use Bitrix\Main;
use Bitrix\SalesCenter\Component\PaymentSlip;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Salescenter\PaymentSlip\PaymentSlipManager;
use Bitrix\UI\Util;

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
	 * @return PaymentSlipManager|null
	 */
	private static function getPaymentSlipManager()
	{
		return SalesCenterManager::getInstance()->getPaymentSlipSenderManager();
	}

	/**
	 * @return TerminalPaysystemManager
	 */
	private static function getPaysystemManager(): TerminalPaysystemManager
	{
		return TerminalPaysystemManager::getInstance();
	}

	private function fillPreparedSettings(): void
	{
		$paymentSlipManager = self::getPaymentSlipManager();
		$paysystemManager = self::getPaysystemManager();

		$settings = [];

		// SMS settings
		if ($paymentSlipManager)
		{
			$settings = [
				'isSmsSendingEnabled' => $paymentSlipManager->getConfig()->isSendingEnabled(),
				'isNotificationsEnabled' => $paymentSlipManager->getConfig()->isNotificationsEnabled(),
				'activeSmsServices' => $paymentSlipManager->getConfig()->getAvailableSmsServices(),
				'paymentSlipLinkScheme' => $this->getPaymentSlipLinkScheme(),
				'connectNotificationsLink' => $paymentSlipManager->getConnectNotificationsLink(),
				'connectServiceLink' => $paymentSlipManager->getConnectServiceLink(),
				'isSmsCollapsed' => $paymentSlipManager->getConfig()->isCollapsed(),
			];
		}

		// Paysystem settings
		$settings['hasPaysystemsPermission'] = SaleManager::getInstance()->isFullAccess();
		$settings['isLinkPaymentEnabled'] = $paysystemManager->getConfig()->isLinkPaymentEnabled();
		$settings['isSbpEnabled'] = $paysystemManager->getConfig()->isSbpEnabled();
		$settings['isSbpConnected'] = $paysystemManager->isSbpPaysystemConnected();
		$settings['sbpConnectPath'] = $paysystemManager->getSbpPaysystemPath();
		$settings['isSberQrEnabled'] = $paysystemManager->getConfig()->isSberQrEnabled();
		$settings['isSberQrConnected'] = $paysystemManager->isSberQrPaysystemConnected();
		$settings['sberQrConnectPath'] = $paysystemManager->getSberQrPaysystemPath();
		$settings['availablePaysystems'] = $paysystemManager->getAvailablePaysystems();
		$settings['terminalDisabledPaysystems'] = $paysystemManager->getConfig()->getTerminalDisabledPaysystems();
		$settings['isRuZone'] = $paysystemManager->getConfig()->isRuZone();
		$settings['isPaysystemsCollapsed'] = $paysystemManager->getConfig()->isCollapsed();
		$settings['paysystemsArticleUrl'] = Util::getArticleUrlByCode(19342732);
		$settings['paysystemPanelPath'] = $paysystemManager->getPaysystemPanelPath();
		$settings['isAnyPaysystemActive'] = $paysystemManager->isAnyPaysystemActive();
		$settings['isPhoneConfirmed'] = LandingManager::getInstance()->isPhoneConfirmed();
		$settings['connectedSiteId'] = LandingManager::getInstance()->getConnectedSiteId();
		$settings['isConnectedSitePublished'] = LandingManager::getInstance()->isSitePublished();
		$settings['isConnectedSiteExists'] = LandingManager::getInstance()->isSiteExists();

		$this->arResult['SETTINGS_PARAMS'] = $settings;
	}

	private function getPaymentSlipLinkScheme(): string
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();
		$host = $request->isHttps() ? 'https' : 'http';

		return (new Main\Web\Uri($host . '://' . $request->getHttpHost() . PaymentSlip::SLIP_LINK_PATH . '/'));
	}
}
