<?php
define("NOT_CHECK_FILE_PERMISSIONS", true);
/**
 * @var CALLUser $USER
 * @var CAllMain $APPLICATION
 */
use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Main\Localization\Loc;


require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!\Bitrix\Main\Loader::includeModule('faceid'))
{
	die();
}

const FACEID_AUTH_ERROR_NOT_AUTHORIZED = 1;
const FACEID_AUTH_ERROR_NOT_PERMITTED = 2;
const FACEID_AUTH_ERROR_WRONG_REQUEST = 3;

$response = array("status"=>"failed");

header("Content-Type: application/x-javascript");

if (!$USER->IsAuthorized())
{

	header("HTTP/1.0 401 Not Authorized");
	$response = array(
		"status"=>"failed",
		"error_code"=> FACEID_AUTH_ERROR_NOT_AUTHORIZED,
		"error_message" => "User is not authorized"
	);

	$userData = CHTTP::ParseAuthRequest();
	$login = $userData["basic"]["username"];

	if($login)
	{
		if(CModule::IncludeModule('bitrix24') && ($captchaInfo = CBitrix24::getStoredCaptcha()))
		{
			$response["captchaCode"] = $captchaInfo["captchaCode"];
			$response["captchaURL"] = $captchaInfo["captchaURL"];
		}
		elseif($APPLICATION->NeedCAPTHAForLogin($login))
		{
			$response["captchaCode"] = $APPLICATION->CaptchaGetCode();
		}

		if (CModule::IncludeModule("security") && \Bitrix\Security\Mfa\Otp::isOtpRequired())
		{
			$response["needOtp"] = true;
		}
	}
}
else
{
	if (\Bitrix\FaceId\TrackingWorkdayApplication::checkPermission())
	{
		$appId = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_ID");
		$appUUID = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_UUID");
		$deviceName = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_DEVICE_NAME");

		if ($appId == 'facein')
		{
			$response["status"] = "success";

			if($USER->GetParam("APPLICATION_ID") === null)
			{
				if (strlen($appUUID) > 0)
				{
					$result = ApplicationPasswordTable::getList(Array(
						'select' => Array('ID'),
						'filter' => Array(
							'USER_ID' => $USER->GetID(),
							'CODE' => $appUUID
						)
					));

					if ($row = $result->fetch())
					{
						ApplicationPasswordTable::delete($row['ID']);
					}
				}

				$password = ApplicationPasswordTable::generatePassword();

				$res = ApplicationPasswordTable::add(array(
					'USER_ID' => $USER->GetID(),
					'APPLICATION_ID' => 'faceid_workday',
					'PASSWORD' => $password,
					'CODE' => $appUUID,
					'DATE_CREATE' => new Bitrix\Main\Type\DateTime(),
					'COMMENT' => Loc::getMessage("FACEID_AUTH_GENERATED_BY_FACEIN") . (strlen($deviceName) > 0 ? " (" . $deviceName . ")" : ""),
					'SYSCOMMENT' => Loc::getMessage("FACEID_AUTH_FACEIN_APP")
				));

				if ($res->isSuccess())
				{
					$response["appPassword"] = $password;
				}
			}
		}
		else
		{
			$response["status"] = "failed";
			$response["error_code"] = FACEID_AUTH_ERROR_WRONG_REQUEST;
			$response["error_message"] = "Wrong request.";
		}
	}
	else
	{
		$response["status"] = "failed";
		$response["error_code"] = FACEID_AUTH_ERROR_NOT_PERMITTED;
		$response["error_message"] = "The authorized user has no enough permissions";
	}
}

echo json_encode($response);
