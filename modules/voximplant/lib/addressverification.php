<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__.'/../classes/general/vi_phone.php');
Loc::loadMessages(__FILE__);

class AddressVerification
{
	/** @var \CVoxImplantError */
	protected $error;

	/**
	 * AddressVerification constructor.
	 */
	public function __construct()
	{
		$this->error = new \CVoxImplantError(null, '', '');
	}

	/**
	 * Returns available address verifications for linking with phone number.
	 * @param string $countryCode The 2-letter country code.
	 * @param string $categoryName The phone category name.
	 * @param string $regionCode The phone region code. Mandatory for verification type LOCAL.
	 * @return array|false
	 */
	public function getAvailableVerifications($countryCode, $categoryName, $regionCode = '')
	{
		//test data
		if(false)
		{
			return [
				"VERIFICATIONS_AVAILABLE" => 2,
				"VERIFICATIONS_PENDING" => 0,
				"VERIFIED_ADDRESS" => [
					[
						"ID" => 77,
						"EXTERNAL_ID" => 123456,
						"COUNTRY_CODE" => "DE",
						"PHONE_CATEGORY_NAME" => "GEOGRAPHIC",
						"SALUTATION" => "MR",
						"CITY" => "Machern",
						"ZIP_CODE" => 4827,
						"STREET" => "Zweenfurther",
						"BUILDING_NUMBER" => 99,
						"COMPANY" => "",
						"FIRST_NAME" => "Ivan",
						"LAST_NAME" => "Petrov",
						"BUILDING_LETTER" => "a",
						"PHONE_REGION_CODE" => "",
						"STATUS" => "VERIFIED",
						"COUNTRY" => "Germany",
					],
					[
						"ID" => 79,
						"EXTERNAL_ID" => 123457,
						"COUNTRY_CODE" => "DE",
						"PHONE_CATEGORY_NAME" => "GEOGRAPHIC",
						"SALUTATION" => "MR",
						"CITY" => "Machern",
						"ZIP_CODE" => 4827,
						"STREET" => "Reestrasse",
						"BUILDING_NUMBER" => 12,
						"COMPANY" => "",
						"FIRST_NAME" => "Ivan",
						"LAST_NAME" => "Petrov",
						"BUILDING_LETTER" => "a",
						"PHONE_REGION_CODE" => "",
						"STATUS" => "VERIFIED",
						"COUNTRY" => "Germany",
					],
				]
			];
		}

		$httpClient = new \CVoxImplantHttp();
		$result = (array)$httpClient->GetAvailableVerifications($countryCode, $categoryName, $regionCode);
		if($result)
		{
			if(is_array($result['VERIFIED_ADDRESS']))
			{
				foreach ($result['VERIFIED_ADDRESS'] as &$address)
				{
					$address = (array)$address;
					if(isset($address['COUNTRY_CODE']))
						$address['COUNTRY'] = Loc::getMessage('VI_PHONE_CODE_'.$address['COUNTRY_CODE']);
				}
			}
			return $result;
		}
		else
		{
			$this->error = new \CVoxImplantError(__METHOD__, $httpClient->GetError()->code, $httpClient->GetError()->msg);
			return false;
		}
	}

	/**
	 * Returns account's address verifications.
	 * @param string $countryCode The 2-letter country code.
	 * @param string $phoneCategoryName The phone category name.
	 * @param string $phoneRegionCode The phone region code. Mandatory for verification type LOCAL.
	 * @param null $verified Return only verified addresses.
	 * @param null $inProgress Show only address verifications, that are in progress.
	 * @return array|false
	 */
	public function getVerifications($countryCode = '', $phoneCategoryName = '', $phoneRegionCode = '', $verified = null, $inProgress = null)
	{
		$httpClient = new \CVoxImplantHttp();
		$result = (array)$httpClient->GetVerifications($countryCode, $phoneCategoryName, $phoneRegionCode, $verified, $inProgress);
		if($result)
		{
			if(is_array($result['VERIFIED_ADDRESS']))
			{
				foreach ($result['VERIFIED_ADDRESS'] as &$address)
				{
					$address = (array)$address;
					if(isset($address['COUNTRY_CODE']))
						$address['COUNTRY'] = Loc::getMessage('VI_PHONE_CODE_'.$address['COUNTRY_CODE']);

					if(isset($address['STATUS']))
						$address['STATUS_NAME'] = \CVoxImplantDocuments::GetStatusName($address['STATUS']);
				}
			}
			return $result;
		}
		else
		{
			$this->error = new \CVoxImplantError(__METHOD__, $httpClient->GetError()->code, $httpClient->GetError()->msg);
			return false;
		}
	}

	/**
	 * Notifies user, that sent documents, about the finishing of the verification process.
	 * @param array $params Array of parameters of the callback.
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function notifyUserWithVerifyResult(array $params)
	{
		if(!\Bitrix\Main\Loader::includeModule('im'))
			return;

		$userId = $this->getFilledByUser();
		if($userId === false)
			return;

		if(!isset($params['STATUS']) || !($params['STATUS'] === 'ACCEPTED' || $params['STATUS'] === 'REJECTED'))
			return;

		$phoneManageUrl = \CVoxImplantHttp::GetServerAddress().\CVoxImplantMain::GetPublicFolder().'lines.php';

		$attach = new \CIMMessageParamAttach(null, "#95c255");
		$attach->AddGrid(array(
			array(
				"NAME" => Loc::getMessage('ADDRESS_VERIFICATION_NOTIFY_HEAD_'.$params['STATUS']),
				"VALUE" => Loc::getMessage('ADDRESS_VERIFICATION_NOTIFY_BODY_'.$params['STATUS'], array('#REJECT_REASON#' => $params['COMMENT'])),
			)
		));
		$attach->AddLink(array(
			"NAME" => Loc::getMessage('ADDRESS_VERIFICATION_NOTIFY_LINK_'.$params['STATUS']),
			"LINK" => $phoneManageUrl
		));

		$messageFields = array(
			"TO_USER_ID" => $userId,
			"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
			"MESSAGE" => Loc::getMessage('ADDRESS_VERIFICATION_NOTIFY'),
			"MESSAGE_OUT" => Loc::getMessage('ADDRESS_VERIFICATION_NOTIFY_HEAD_'.$params['STATUS'])." ".Loc::getMessage('ADDRESS_VERIFICATION_NOTIFY_BODY_'.$params['STATUS']).": ".$phoneManageUrl,
			"ATTACH" => Array($attach)
		);

		$mess = \CIMNotify::Add($messageFields);
	}


	/**
	 * Stores ID of the user, who was the last to fill documents.
	 * @param int $userId Id of the user.
	 * @return void
	 */
	public function setFilledByUser($userId)
	{
		$userId = (int)$userId;
		if($userId === 0)
			return;

		\Bitrix\Main\Config\Option::set('voximplant', 'address_verification_filled_by', $userId);
	}

	/**
	 * Returns ID of the user, who was the last to fill documents.
	 * @return int|false User ID or false if not set.
	 */
	public function getFilledByUser()
	{
		$lastFilledBy = (int)\Bitrix\Main\Config\Option::get('voximplant', 'address_verification_filled_by');
		return ($lastFilledBy > 0 ? $lastFilledBy : false);
	}

	/**
	 * Returns last error
	 * @return \CVoxImplantError
	 */
	public function getError()
	{
		return $this->error;
	}
}
