<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Internals\AgreementTable;

use Bitrix\ImConnector\Result;

Loc::loadMessages(__FILE__);

/**
 * Class FbInstagramDirect
 * @package Bitrix\ImConnector\Connectors
 */
class FbInstagramDirect extends Base
{
	protected const CODE_TERMS_AGREEMENT = 'imconnector_terms_fbinstagramdirect';
	protected const DATA_PROVIDER_CODE = 'imconnector/fbinstagramdirect';

	//Input
	/**
	 * @param $message
	 * @param $line
	 * @return Result
	 */
	public function processingInputNewMessage($message, $line): Result
	{
		if(!empty($message['message']['unsupported_message']))
		{
			unset($message['message']['unsupported_message']);

			$message['message']['text'] = Loc::getMessage('IMCONNECTOR_MESSAGE_UNSUPPORTED_INSTAGRAM');
		}

		return parent::processingInputNewMessage($message, $line);
	}
	//END Input

	//Terms
	/**
	 * @return int
	 */
	protected static function getIdAgreementTerms(): int
	{
		$id = 0;

		$agreementRaw = AgreementTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=CODE' => self::CODE_TERMS_AGREEMENT,
				'ACTIVE' => Agreement::ACTIVE
			],
			'limit' => 1
		]);

		if (!($id  = $agreementRaw->fetch()['ID']))
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

		return (int)$id;
	}

	/**
	 * @return array
	 */
	public static function getAgreementTerms(): array
	{
		$result = [];
		$agreementId = self::getIdAgreementTerms();

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

	/**
	 * @return bool
	 */
	public static function isUserConsentAgreementTerms(): bool
	{
		$result = true;

		$agreementId = self::getIdAgreementTerms();
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
	//END Terms
}