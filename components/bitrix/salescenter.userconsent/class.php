<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Crm\Integration\UserConsent;

/**
 * Class SalesCenterUserConsent
 */
class SalesCenterUserConsent extends CBitrixComponent implements Controllerable
{
	const SALESCENTER_USER_CONSENT_ID = "~SALESCENTER_USER_CONSENT_ID";
	const SALESCENTER_USER_CONSENT_CHECKED = "~SALESCENTER_USER_CONSENT_CHECKED";
	const SALESCENTER_USER_CONSENT_ACTIVE = "~SALESCENTER_USER_CONSENT_ACTIVE";

	/**
	 * @return mixed|void
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function executeComponent()
	{
		if(!Loader::includeModule('salescenter'))
		{
			$this->showError(Loc::getMessage('SALESCENTER_MODULE_ERROR'));
			return;
		}

		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	/**
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function prepareResult()
	{
		$userConsent = $this->getUserConsentId();
		if ($userConsent === false)
		{
			$userConsent = $this->getDefaultUserConsent();
		}

		$this->arResult['ID'] = (int)$userConsent;
		$this->arResult['CHECK'] = $this->getUserConsentCheckStatus();
		$this->arResult['ACTIVE'] = $this->getUserConsentActive();
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getUserConsentId()
	{
		return Option::get('salescenter', self::SALESCENTER_USER_CONSENT_ID, false);
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getDefaultUserConsent()
	{
		$agreementId = false;
		if (Loader::includeModule('imopenlines'))
		{
			$configManager = new \Bitrix\ImOpenLines\Config();
			$result = $configManager->getList(
				[
					'select' => ['AGREEMENT_ID'],
					'filter' => ['>AGREEMENT_ID' => 0],
					'order' => ['ID'],
					'limit' => 1
				]
			);
			foreach ($result as $id => $config)
			{
				$agreementId = $config['AGREEMENT_ID'];
			}

			if ($agreementId)
			{
				Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ID, $agreementId);
				Option::set('salescenter', self::SALESCENTER_USER_CONSENT_CHECKED, 'Y');
			}
		}

		if (
			!$agreementId
			&& Application::getInstance()->getLicense()->getRegion() === 'by'
			&& Loader::includeModule('crm')
		)
		{
			$agreementId = UserConsent::getDefaultAgreementId();
		}

		return $agreementId;
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getUserConsentCheckStatus()
	{
		return Option::get('salescenter', self::SALESCENTER_USER_CONSENT_CHECKED, 'Y');
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function getUserConsentActive()
	{
		return Option::get('salescenter', self::SALESCENTER_USER_CONSENT_ACTIVE, 'Y');
	}

	/**
	 * @param $formData
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function saveUserConsentAction($formData)
	{
		if (isset($formData['USERCONSENT']['ACTIVE']) && $formData['USERCONSENT']['ACTIVE'] == 'Y')
		{
			Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ACTIVE, 'Y');

			if (isset($formData['USERCONSENT']['AGREEMENT_ID']) && !empty($formData['USERCONSENT']['AGREEMENT_ID']))
			{
				$agreementId = $formData['USERCONSENT']['AGREEMENT_ID'];
				Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ID, $agreementId);

				$check = 'N';
				if (isset($formData['USERCONSENT']['CHECK']))
				{
					$check = $formData['USERCONSENT']['CHECK'];
				}
				Option::set('salescenter', self::SALESCENTER_USER_CONSENT_CHECKED, $check);
			}
			else
			{
				Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ID, 0);
			}
		}
		else
		{
			Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ACTIVE, 'N');
		}
	}

	/**
	 * @param $error
	 */
	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}
}