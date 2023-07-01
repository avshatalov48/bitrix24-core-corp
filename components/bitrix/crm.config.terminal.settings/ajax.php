<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

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
	public function saveSettingsAction()
	{
		$paymentSlipManager = self::getPaymentSlipManager();
		if (!$paymentSlipManager || !\CCrmSaleHelper::isShopAccess('admin'))
		{
			return [];
		}
		$paymentSlipConfig = $paymentSlipManager->getConfig();
		$changedValues = is_array($this->request->get('changedValues')) ? $this->request->get('changedValues') : [];

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
}