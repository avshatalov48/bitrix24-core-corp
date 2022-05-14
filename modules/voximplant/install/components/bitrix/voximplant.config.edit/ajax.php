<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class VoximplantLinesAjaxController extends \Bitrix\Main\Engine\Controller
{
	public function __construct(\Bitrix\Main\Request $request = null)
	{
		parent::__construct($request);

		\Bitrix\Main\Loader::includeModule('voximplant');
	}

	public function checkConnectionAction($registrationId)
	{
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("AUTHORIZE_ERROR");
			return null;
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->GetSipRegistrations($registrationId);

		if(!$result)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error($viSip->GetError()->msg);
			return null;
		}

		$viSip->updateSipRegistrationStatus([
			'sip_registration_id' => $result->reg_id,
			'error_message' => $result->error_message,
			'status_code' => $result->status_code,
			'successful' => $result->status_result === 'success'
		]);

		return [
			'id' => $result->reg_id,
			'lastUpdated' => $result->last_updated,
			'errorMessage' => $result->error_message,
			'statusCode' => $result->status_code,
			'statusResult' => $result->status_result,
		];
	}

	public function addSipNumberAction($sipId, $number)
	{
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("AUTHORIZE_ERROR");
			return null;
		}

		$parsedNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($number);
		$normalizedNumber = $parsedNumber->format(\Bitrix\Main\PhoneNumber\Format::E164);

		$checkRow = \Bitrix\Voximplant\Model\ExternalLineTable::getList([
			"filter" => [
				"=SIP_ID" => $sipId,
				"=NORMALIZED_NUMBER" => $normalizedNumber
			]
		])->fetch();

		if($checkRow)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("Number already exists");
			return null;
		}

		$record = [
			"TYPE" => \Bitrix\Voximplant\Model\ExternalLineTable::TYPE_SIP,
			"SIP_ID" => $sipId,
			"NUMBER" => $number,
			"NORMALIZED_NUMBER" => $normalizedNumber,
			"IS_MANUAL" => "Y"
		];
		\Bitrix\Voximplant\Model\ExternalLineTable::add($record);

		return [
			"NUMBER" => $normalizedNumber,
			"FORMATTED_NUMBER" => $parsedNumber->format(),
			"IS_MANUAL" => "Y"
		];
	}

	public function removeSipNumberAction($sipId, $number)
	{
		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("AUTHORIZE_ERROR");
			return null;
		}

		$parsedNumber = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($number);
		$normalizedNumber = $parsedNumber->format(\Bitrix\Main\PhoneNumber\Format::E164);

		$checkRow = \Bitrix\Voximplant\Model\ExternalLineTable::getList([
			"filter" => [
				"=SIP_ID" => $sipId,
				"=NORMALIZED_NUMBER" => $normalizedNumber
			]
		])->fetch();

		if(!$checkRow)
		{
			$this->errorCollection[] = new \Bitrix\Main\Error("Number is not found");
			return null;
		}
		if($checkRow["IS_MANUAL"] != "Y")
		{
			$this->errorCollection[] =new \Bitrix\Main\Error("Number can not be deleted");
			return null;
		}

		\Bitrix\Voximplant\Model\ExternalLineTable::delete($checkRow["ID"]);

		return true;
	}
}
