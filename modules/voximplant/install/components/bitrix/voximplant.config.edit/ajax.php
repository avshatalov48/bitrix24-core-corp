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

		return [
			'id' => $result->reg_id,
			'lastUpdated' => $result->last_updated,
			'errorMessage' => $result->error_message,
			'statusCode' => $result->status_code,
			'statusResult' => $result->status_result,
		];
	}
}
