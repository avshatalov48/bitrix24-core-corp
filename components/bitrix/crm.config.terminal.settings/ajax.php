<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Terminal\Config\TerminalPaysystemManager;
use Bitrix\Main\Loader;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;

/**
 * Class SalesCenterPaySystemAjaxController
 */
class CrmConfigTerminalSettingsController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @return \Bitrix\Salescenter\PaymentSlip\PaymentSlipManager|null
	 */
	private static function getPaymentSlipManager()
	{
		return \Bitrix\Crm\Integration\SalesCenterManager::getInstance()->getPaymentSlipSenderManager();
	}

	/**
	 * @return TerminalPaysystemManager
	 */
	private static function getPaysystemManager(): TerminalPaysystemManager
	{
		return TerminalPaysystemManager::getInstance();
	}

	/**
	 * @param \Bitrix\Main\Engine\Action $action
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		Loader::includeModule('crm');
		return parent::processBeforeAction($action);
	}

	/**
	 * @return array
	 */
	public function saveSettingsAction(array $changedValues = [])
	{
		$paymentSlipManager = self::getPaymentSlipManager();
		$paysystemManager = self::getPaysystemManager();

		// TODO check rights, maybe SaleManager::getInstance()->isFullAccess() ?
		if (!$paymentSlipManager || !\CCrmSaleHelper::isShopAccess('admin'))
		{
			return [];
		}
		$paymentSlipConfig = $paymentSlipManager->getConfig();
		$paysystemConfig = $paysystemManager->getConfig();

		if (
			isset($changedValues['selectedServiceId'])
			&& is_string($changedValues['selectedServiceId'])
		)
		{
			if (!$paymentSlipConfig->setSelectedServiceId($changedValues['selectedServiceId']))
			{
				unset($changedValues['selectedServiceId']);
			}
		}

		if (isset($changedValues['isSmsSendingEnabled']))
		{
			$changedValues['isSmsSendingEnabled'] = ($changedValues['isSmsSendingEnabled'] === 'true');
			$paymentSlipConfig->setEnablingSending($changedValues['isSmsSendingEnabled']);
		}

		if (
			isset($changedValues['terminalDisabledPaysystems'])
			&& is_array($changedValues['terminalDisabledPaysystems'])
		)
		{
			$paysystemConfig->setDisabledPaysystems($changedValues['terminalDisabledPaysystems']);
		}

		if (isset($changedValues['terminalPaysystemsAllEnabled']))
		{
			$paysystemConfig->enableAllPaysystems();
		}

		if (isset($changedValues['isLinkPaymentEnabled']))
		{
			$changedValues['isLinkPaymentEnabled'] = ($changedValues['isLinkPaymentEnabled'] === 'true');
			$paysystemConfig->setLinkPaymentEnabled($changedValues['isLinkPaymentEnabled']);
		}

		if (isset($changedValues['isSbpEnabled']))
		{
			$changedValues['isSbpEnabled'] = ($changedValues['isSbpEnabled'] === 'true');
			$paysystemConfig->setSbpEnabled($changedValues['isSbpEnabled']);
		}

		if (isset($changedValues['isSberQrEnabled']))
		{
			$changedValues['isSberQrEnabled'] = ($changedValues['isSberQrEnabled'] === 'true');
			$paysystemConfig->setSberQrEnabled($changedValues['isSberQrEnabled']);
		}

		return [
			'changedValues' => $changedValues,
		];
	}

	/**
	 * @return array
	 */
	public function updateServicesListAction()
	{
		$paymentSlipManager = self::getPaymentSlipManager();
		if ($paymentSlipManager && \CCrmSaleHelper::isShopAccess('admin'))
		{
			return [
				'isUCNEnabled' => $paymentSlipManager->getConfig()->isNotificationsEnabled(),
				'activeSmsServices' => $paymentSlipManager->getConfig()->getAvailableSmsServices(),
			];
		}

		return [
			'isUCNEnabled' => false,
			'activeSmsServices' => [],
		];
	}

	/**
	 * @return array
	 */
	public function updatePaysystemPathsAction()
	{
		$terminalPaysystemManager = self::getPaysystemManager();

		if (
			!Loader::includeModule('salescenter')
			|| !SaleManager::getInstance()->isFullAccess())
		{
			return [
				'sbp' => '',
				'sberbankQr' => '',
			];
		}

		return [
			'sbp' => $terminalPaysystemManager->getSbpPaysystemPath(),
			'sberbankQr' => $terminalPaysystemManager->getSberQrPaysystemPath(),
			'isSbpConnected' => $terminalPaysystemManager->isSbpPaysystemConnected(),
			'isSberQrConnected' => $terminalPaysystemManager->isSberQrPaysystemConnected(),
			'isAnyPaysystemActive' => $terminalPaysystemManager->isAnyPaysystemActive(),
			'availablePaysystems' => $terminalPaysystemManager->getAvailablePaysystems(),
		];
	}

	public function updateConnectedSiteParamsAction()
	{
		if (!Loader::includeModule('salescenter'))
		{
			return [
				'connectedSiteId' => 0,
			];
		}

		return [
			'isConnectedSiteExists' => LandingManager::getInstance()->isSiteExists(),
			'connectedSiteId' => LandingManager::getInstance()->getConnectedSiteId(),
			'isConnectedSitePublished' => LandingManager::getInstance()->isSitePublished(),
			'isPhoneConfirmed' => LandingManager::getInstance()->isPhoneConfirmed(),
		];
	}

	/**
	 * @param string $collapsed
	 * @return void
	 */
	public function updatePaysystemsCollapsedAction(string $collapsed): void
	{
		self::getPaysystemManager()->getConfig()->setCollapsed($collapsed === 'true');
	}

	/**
	 * @param string $collapsed
	 * @return void
	 */
	public function updateSmsCollapsedAction(string $collapsed): void
	{
		if (!Loader::includeModule('salescenter'))
		{
			return;
		}
		$paymentSlipManager = self::getPaymentSlipManager();
		if (!$paymentSlipManager)
		{
			return;
		}
		$paymentSlipManager->getConfig()->setCollapsed($collapsed === 'true');
	}
}
