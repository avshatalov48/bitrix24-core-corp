<?php

namespace Bitrix\Voximplant\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\ConfigTable;
use Bitrix\Voximplant\Model\CallerIdTable;

class CallerId extends Engine\Controller
{
	/**
	 * Returns fields of the caller id number.
	 *
	 * @param string $phoneNumber Caller id number.
	 * @return array|null
	 */
	public function getAction($phoneNumber)
	{
		$number = Parser::getInstance()->parse($phoneNumber);

		if(!$number->isValid() && mb_substr($phoneNumber, 0, 1) !== "+")
		{
			$number = Parser::getInstance()->parse("+" . $phoneNumber);
		}

		if(!$number->isValid())
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_WRONG_NUMBER"), "wrong_number");
			return null;
		}
		$normalizedNumber = $number->format(Format::E164);
		// remove +
		$normalizedNumber = mb_substr($normalizedNumber, 1);

		$result = CallerIdTable::getRow(['filter' => ['=NUMBER' => $normalizedNumber]]);
		if(!$result)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_NOT_FOUND"), "not_found");
			return null;
		}

		return [
			'phoneNumber' => "+" . $result['NUMBER'],
			'verified' => $result['VERIFIED'] == 'Y',
			'verifiedUntil' => $result['VERIFIED_UNTIL'] ? $result['VERIFIED_UNTIL']->toString() : null
		];
	}

	/**
	 * Adds new Caller Id number.
	 *
	 * @param string $phoneNumber Number to be added.
	 * @param bool $requestVerification Should a verification call be performed after adding.
	 * @return array|null
	 */
	public function addAction($phoneNumber, $requestVerification = false)
	{
		$number = Parser::getInstance()->parse($phoneNumber);

		if(!$number->isValid())
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_WRONG_NUMBER"), "wrong_number");
			return null;
		}
		$normalizedNumber = $number->format(Format::E164);
		// remove +
		$normalizedNumber = mb_substr($normalizedNumber, 1);

		$checkRow = CallerIdTable::getRow(['filter' => ['=NUMBER' => $normalizedNumber]]);
		if($checkRow)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_ALREADY_EXISTS"), "not_unique");
			return null;
		}

		$apiClient = new \CVoxImplantHttp();
		$result = $apiClient->addCallerID($normalizedNumber);

		if(!$result)
		{
			$this->errorCollection[] = new Error($apiClient->GetError()->msg, $apiClient->GetError()->code);
			return null;
		}

		$verifiedUntil = ($result->verified ? new DateTime($result->verified_until, 'Y-m-d') : null);
		\CVoxImplantPhone::addCallerId($normalizedNumber, $result->verified, $verifiedUntil);

		if(!$result->verified && $requestVerification)
		{
			$verificationResult = $apiClient->VerifyCallerID($normalizedNumber);
			if(!$verificationResult)
			{
				$this->errorCollection[] = new Error($apiClient->GetError()->msg, $apiClient->GetError()->code);
				return null;
			}
		}

		return [
			'number' => $normalizedNumber,
			'verified' => $result->verified ? 'Y' : 'N',
			'verifiedUntil' => $verifiedUntil ? $verifiedUntil->toString() : '',
		];
	}

	public function requestVerificationAction($phoneNumber)
	{
		$number = Parser::getInstance()->parse($phoneNumber);

		if(!$number->isValid())
		{
			$this->errorCollection[] = new Error("", "wrong_number");
			return null;
		}
		$normalizedNumber = $number->format(Format::E164);
		// remove +
		$normalizedNumber = mb_substr($normalizedNumber, 1);

		$apiClient = new \CVoxImplantHttp();
		$result = $apiClient->verifyCallerID($normalizedNumber);
		if(!$result)
		{
			$this->errorCollection[] = new Error($apiClient->GetError()->msg, $apiClient->GetError()->code);
			return null;
		}

		return true;
	}

	public function verifyAction($phoneNumber, $code)
	{
		$number = Parser::getInstance()->parse($phoneNumber);
		if(!$number->isValid())
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_WRONG_NUMBER"), "wrong_number");
			return null;
		}
		$normalizedNumber = $number->format(Format::E164);
		// remove +
		$normalizedNumber = mb_substr($normalizedNumber, 1);

		$row = CallerIdTable::getRow(['filter' => [
			'=NUMBER' => $normalizedNumber
		]]);

		if(!$row)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_NOT_FOUND"), "not_found");
			return null;
		}

		$apiClient = new \CVoxImplantHttp();
		$result = $apiClient->activateCallerID($normalizedNumber, $code);

		if(!$result)
		{
			$this->errorCollection[] = new Error($apiClient->GetError()->msg, $apiClient->GetError()->code);
			return null;
		}

		$verifiedUntil = \Bitrix\Main\Type\DateTime::createFromTimestamp($result->verified_until_ts);
		CallerIdTable::update($row["ID"], [
			"VERIFIED" => $result->verified ? "Y" : "N",
			"VERIFIED_UNTIL" => $verifiedUntil
		]);

		return [
			"number" => $normalizedNumber,
			"verified" => $result->verified ? "Y" : "N",
			"verifiedUntil" => $verifiedUntil ? $verifiedUntil->toString() : "",
		];
	}

	public function deleteAction($phoneNumber)
	{
		$row = CallerIdTable::getRow(['filter' => [
			'=NUMBER' => $phoneNumber
		]]);

		if(!$row)
		{
			$this->errorCollection[] = new Error(Loc::getMessage("VOX_CALLER_ID_NOT_FOUND"), "not_found");
			return null;
		}


		$api = new \CVoxImplantHttp();
		$result = $api->delCallerID($phoneNumber);

		if(!$result)
		{
			$errorCode = $api->getError()->code;

			if($errorCode != 'CALLERID_ERROR')
			{
				$this->errorCollection[] = new Error($api->getError()->msg);
				return null;
			}
		}

		CallerIdTable::delete($row['ID']);
		ConfigTable::delete($row['CONFIG_ID']);
	}
}