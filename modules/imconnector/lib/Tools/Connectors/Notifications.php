<?php
namespace Bitrix\ImConnector\Tools\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Internals\AgreementTable;

use Bitrix\ImConnector\Limit;

class Notifications
{
	protected const CODE_TERMS_AGREEMENT = 'imconnector_terms_notifications_v2';
	protected const DATA_PROVIDER_CODE = 'imconnector/notifications';

	/**
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		if (!Loader::includeModule('notifications'))
		{
			return false;
		}

		return !Loader::includeModule('bitrix24') || \CBitrix24::getPortalZone() === 'ru';
	}

	/**
	 * @return bool
	 */
	public function canUse(): bool
	{
		return Loader::includeModule('notifications') && Limit::canUseConnector('notifications');
	}

	/**
	 * @return int
	 */
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
	public function getAgreementTerms(): ?array
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

	/**
	 * @return bool
	 */
	public function addUserConsentAgreementTerms(): bool
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