<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Crm\Integration\UserConsent;
use Bitrix\Main\Web\Json;

/**
 * Class SalesCenterUserConsent
 */
class SalesCenterUserConsent extends CBitrixComponent implements Controllerable
{
	/**
	 * @deprecated
	 * @see SALESCENTER_USER_CONSENTS
	 */
	const SALESCENTER_USER_CONSENT_ID = "~SALESCENTER_USER_CONSENT_ID";
	/**
	 * @deprecated
	 * @see SALESCENTER_USER_CONSENTS
	 */
	const SALESCENTER_USER_CONSENT_CHECKED = "~SALESCENTER_USER_CONSENT_CHECKED";

	const SALESCENTER_USER_CONSENT_ACTIVE = "~SALESCENTER_USER_CONSENT_ACTIVE";
	const SALESCENTER_USER_CONSENTS = '~SALESCENTER_USER_CONSENTS';

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
		$userConsents = $this->getUserConsents();
		if (!$userConsents)
		{
			$userConsents = $this->getDefaultUserConsents();
		}

		$this->arResult['USER_CONSENTS'] = $userConsents;
		$this->arResult['ACTIVE'] = $this->getUserConsentActive();
	}

	public function getUserConsents(): array
	{
		$userConsents = $this->getUserConsentsFromMultipleOption();
		if (is_array($userConsents))
		{
			return $userConsents;
		}

		$userConsentId = $this->getUserConsentId();
		if ($userConsentId)
		{
			return [
				[
					'ID' => (int)$userConsentId,
					'CHECKED' => $this->getUserConsentCheckStatus(),
					'REQUIRED' => 'Y',
				]
			];
		}

		return [];
	}

	private function getUserConsentsFromMultipleOption(): ?array
	{
		$option = Option::get('salescenter', self::SALESCENTER_USER_CONSENTS, null);
		if (!$option)
		{
			return null;
		}

		try
		{
			$decodedOption = Json::decode($option);
			if (!is_array($decodedOption))
			{
				return null;
			}
		}
		catch (\Exception $e)
		{
			return null;
		}

		return $decodedOption;
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
	 * @return array
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function getDefaultUserConsents(): array
	{
		$agreementId = null;

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
			foreach ($result as $config)
			{
				$agreementId = $config['AGREEMENT_ID'];
			}

			if ($agreementId)
			{
				$agreements = [
					[
						'ID' => (int)$agreementId,
						'CHECKED' => 'Y',
						'REQUIRED' => 'Y',
					]
				];
				Option::set(
					'salescenter',
					self::SALESCENTER_USER_CONSENTS,
					Json::encode($agreements),
				);

				return $agreements;
			}
		}

		if (
			!$agreementId
			&& Application::getInstance()->getLicense()->getRegion() === 'by'
			&& Loader::includeModule('crm')
		)
		{
			$agreementId = UserConsent::getDefaultAgreementId();
			if (!$agreementId)
			{
				return [];
			}

			return [
				[
					'ID' => (int)$agreementId,
					'CHECKED' => 'Y',
					'REQUIRED' => 'Y',
				]
			];
		}

		return [];
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
	public function saveUserConsentAction($formData): void
	{
		if (isset($formData['USERCONSENT']['ACTIVE']) && $formData['USERCONSENT']['ACTIVE'] == 'Y')
		{
			Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ACTIVE, 'Y');

			if (!empty($formData['USERCONSENT']['AGREEMENTS']))
			{
				$agreements = $this->parseAgreementsFromForm($formData['USERCONSENT']['AGREEMENTS']);
				Option::set(
					'salescenter',
					self::SALESCENTER_USER_CONSENTS,
					Json::encode($agreements),
				);
			}
			else
			{
				Option::set('salescenter', self::SALESCENTER_USER_CONSENTS, []);
			}
		}
		else
		{
			Option::set('salescenter', self::SALESCENTER_USER_CONSENT_ACTIVE, 'N');
		}
	}

	private function parseAgreementsFromForm(mixed $agreements): array
	{
		if (!is_array($agreements))
		{
			return [];
		}

		$parsedAgreements = [];
		foreach ($agreements as $agreement)
		{
			if (
				!isset($agreement['ID'])
				|| (int)$agreement['ID'] <= 0
				|| !isset($agreement['CHECKED'])
				|| !isset($agreement['REQUIRED'])
			)
			{
				continue;
			}
			$parsedAgreements[] = [
				'ID' => (int)$agreement['ID'],
				'CHECKED' => $agreement['CHECKED'] === 'Y' ? 'Y' : 'N',
				'REQUIRED' => $agreement['REQUIRED'] === 'Y' ? 'Y' : 'N',
			];
		}

		return $parsedAgreements;
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