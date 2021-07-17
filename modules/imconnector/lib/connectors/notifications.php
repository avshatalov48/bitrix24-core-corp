<?php

namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Internals\AgreementTable;

class Notifications extends Base
{
	public const CONNECTOR_ID = 'notifications';
	protected const CODE_TERMS_AGREEMENT = 'imconnector_terms_notifications';
	protected const DATA_PROVIDER_CODE = 'imconnector/notifications';

	protected static function getIdAgreementTerms(): int
	{
		$agreementRow = AgreementTable::getRow([
			'select' => ['ID'],
			'filter' => [
				'=CODE' => self::CODE_TERMS_AGREEMENT,
				'=ACTIVE' => Agreement::ACTIVE
			],
		]);

		$id = $agreementRow ? (int)$agreementRow['ID'] : 0;

		if (!$id)
		{
			$addResult = AgreementTable::add([
				'CODE' => self::CODE_TERMS_AGREEMENT,
				'NAME' => Loc::getMessage('IMCONNECTOR_NOTIFICATIONS_TOS_AGREEMENT_NAME'),
				'AGREEMENT_TEXT' => Loc::getMessage('IMCONNECTOR_NOTIFICATIONS_TOS_AGREEMENT_TEXT'),
				'TYPE' => Agreement::TYPE_CUSTOM,
				'DATA_PROVIDER' => self::DATA_PROVIDER_CODE,
				'IS_AGREEMENT_TEXT_HTML' => 'Y',
			]);
			if ($addResult->isSuccess())
			{
				$id = $addResult->getId();
			}
		}

		return (int)$id;
	}

	/**
	 * @return array
	 */
	public static function getAgreementTerms(): ?array
	{
		$result = [];
		$agreementId = self::getIdAgreementTerms();

		if(!empty($agreementId))
		{
			$agreement = new Agreement($agreementId);
			if($agreement->isExist())
			{
				$result = [
					'title' => $agreement->getData()['NAME'],
					'html' => $agreement->getHtml()
				];
			}
		}

		return $result;
	}

	public static function addUserConsentAgreementTerms(): bool
	{
		$result = false;

		$agreementId = self::getIdAgreementTerms();
		if(!empty($agreementId))
		{
			$result = Consent::addByContext(
				$agreementId,
				self::DATA_PROVIDER_CODE
			);
		}

		return (bool)$result;
	}
}