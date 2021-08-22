<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Internals\AgreementTable;

class FbInstagramDirect
{
	protected const CODE_TERMS_AGREEMENT = 'imconnector_terms_fbinstagramdirect';
	protected const DATA_PROVIDER_CODE = 'imconnector/fbinstagramdirect';

	/**
	 * @return int
	 */
	protected function getIdAgreementTerms($isAdded = true): int
	{
		$id = 0;

		$agreement = AgreementTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=CODE' => self::CODE_TERMS_AGREEMENT,
				'=ACTIVE' => Agreement::ACTIVE
			]
		]);

		if(empty($agreement))
		{
			if($isAdded === true)
			{
				$addResult = AgreementTable::add([
					'CODE' => self::CODE_TERMS_AGREEMENT,
					'NAME' => Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_AGREEMENT_TERMS_NAME'),
					'AGREEMENT_TEXT' => Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_AGREEMENT_TERMS_TEXT'),
					'TYPE' => Agreement::TYPE_CUSTOM,
					'DATA_PROVIDER' => self::DATA_PROVIDER_CODE,
					'IS_AGREEMENT_TEXT_HTML' => 'Y',
				]);
				if ($addResult->isSuccess())
				{
					$id = $addResult->getId();
				}
			}
		}
		else
		{
			$id = $agreement['ID'];
		}

		return (int)$id;
	}

	/**
	 * @return array
	 */
	public function getAgreementTerms(): array
	{
		$result = [];
		$agreementId = $this->getIdAgreementTerms();

		if(!empty($agreementId))
		{
			$agreement = new Agreement($agreementId);
			if($agreement->isExist())
			{
				$result = [
					'NAME' => $agreement->getData()['NAME'],
					'HTML' => $agreement->getHtml()
				];
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function addUserConsentAgreementTerms(): bool
	{
		$result = false;

		$agreementId = $this->getIdAgreementTerms();
		if(!empty($agreementId))
		{
			$result = Consent::addByContext(
				$agreementId,
				self::DATA_PROVIDER_CODE
			);
		}

		return (bool)$result;
	}

	/**
	 * @return bool
	 */
	public function isUserConsentAgreementTerms(): bool
	{
		$result = true;

		$agreementId = $this->getIdAgreementTerms();
		if(!empty($agreementId))
		{
			$consent = Consent::getByContext(
				$agreementId,
				self::DATA_PROVIDER_CODE
			);

			if(empty($consent))
			{
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function resetTextAgreementTerms(): bool
	{
		$result = false;

		$agreementId = $this->getIdAgreementTerms(false);
		if(!empty($agreementId))
		{
			$updateResult = AgreementTable::update($agreementId, [
				'AGREEMENT_TEXT' => Loc::getMessage('IMCONNECTOR_FBINSTAGRAMDIRECT_AGREEMENT_TERMS_TEXT')
			]);
			if ($updateResult->isSuccess())
			{
				$result = true;
			}
		}

		return $result;
	}
}