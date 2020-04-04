<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Main\Entity\Query;
use Bitrix\Voximplant as VI;

class CVoxImplantPhoneOrder
{
	const OPERATOR_STATUS_NONE = 'NONE';
	const OPERATOR_STATUS_WAIT = 'WAIT';
	const OPERATOR_STATUS_DECLINE = 'DECLINE';
	const OPERATOR_STATUS_IN_PROCESS = 'IN_PROCESS';
	const OPERATOR_STATUS_ACCEPT = 'ACCEPT';
	const OPERATOR_STATUS_ACTIVE_PARTIAL_BLOCKED = 'ACTIVE_PARTIAL_BLOCKED';
	const OPERATOR_STATUS_ACTIVE_BLOCKED = 'ACTIVE_BLOCKED';
	const OPERATOR_STATUS_ACTIVE_TERMINATION = 'ACTIVE_TERMINATION';

	const OPERATOR_REQUEST_TYPE_TOLLFREE = 'TOLLFREE';
	const OPERATOR_REQUEST_TYPE_LINE = 'LINE';
	const OPERATOR_REQUEST_TYPE_NUMBER = 'NUMBER';
	const OPERATOR_REQUEST_TYPE_CITY = 'CITY';
	const OPERATOR_REQUEST_TYPE_CHANGE = 'CHANGE';
	
	public static function GetStatus($requestFromController = false)
	{
		$arResult['DATE_CREATE'] = COption::GetOptionString("voximplant", "phone_order_date_create", '');
		$arResult['DATE_MODIFY'] = COption::GetOptionString("voximplant", "phone_order_date_modify", '');
		$arResult['OPERATOR_STATUS'] = COption::GetOptionString("voximplant", "phone_order_operator_status", '');
		$arResult['OPERATOR_CONTRACT'] = COption::GetOptionString("voximplant", "phone_order_operator_contract", '');

		if ($arResult['OPERATOR_STATUS'] == '' || $requestFromController)
		{
			$ViHttp = new CVoxImplantHttp();
			$result = $ViHttp->GetPhoneOrderStatus();

			if ($result)
			{
				$arResult['DATE_CREATE'] = $result->DATE_CREATE? ConvertTimeStamp($result->DATE_CREATE+CTimeZone::GetOffset()+date("Z"), 'SHORT'): '';
				$arResult['DATE_MODIFY'] = $result->DATE_MODIFY? ConvertTimeStamp($result->DATE_MODIFY+CTimeZone::GetOffset()+date("Z"), 'SHORT'): '';
				$arResult['OPERATOR_STATUS'] = $result->OPERATOR_STATUS;
				$arResult['OPERATOR_CONTRACT'] = $result->OPERATOR_CONTRACT;

				COption::SetOptionString("voximplant", "phone_order_date_create", $arResult['DATE_CREATE']);
				COption::SetOptionString("voximplant", "phone_order_date_modify", $arResult['DATE_MODIFY']);
				COption::SetOptionString("voximplant", "phone_order_operator_status", $arResult['OPERATOR_STATUS']);
				COption::SetOptionString("voximplant", "phone_order_operator_contract", $arResult['OPERATOR_CONTRACT']);
			}
		}

		return $arResult;
	}

	public static function Send($params)
	{
		$status = self::GetStatus();
		if (!in_array($status['OPERATOR_STATUS'], Array(self::OPERATOR_STATUS_NONE, self::OPERATOR_STATUS_DECLINE)))
		{
			return false;
		}

		$arSend = Array(
			'NAME' => $params['NAME'],
			'CONTACT' => $params['CONTACT'],
			'REG_CODE' => $params['REG_CODE'],
			'PHONE' => $params['PHONE'],
			'EMAIL' => $params['EMAIL'],
		);

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->AddPhoneOrder($arSend);
		if ($result)
		{
			$arResult['DATE_CREATE'] = $result->DATE_CREATE? ConvertTimeStamp($result->DATE_CREATE+CTimeZone::GetOffset()+date("Z"), 'SHORT'): '';
			$arResult['OPERATOR_STATUS'] = $result->OPERATOR_STATUS;

			COption::SetOptionString("voximplant", "phone_order_date_create", $arResult['DATE_CREATE']);
			COption::SetOptionString("voximplant", "phone_order_operator_status", $arResult['OPERATOR_STATUS']);

			return $arResult;
		}

		return false;
	}

	public static function RequestService($params)
	{
		if (!in_array($params['TYPE'], Array(
			self::OPERATOR_REQUEST_TYPE_TOLLFREE,
			self::OPERATOR_REQUEST_TYPE_LINE,
			self::OPERATOR_REQUEST_TYPE_NUMBER,
			self::OPERATOR_REQUEST_TYPE_CITY,
			self::OPERATOR_REQUEST_TYPE_CHANGE
		)))
		{
			return false;
		}

		$arSend = Array(
			'TYPE' => $params['TYPE'],
		);

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->AddServiceOrder($arSend);

		return $result? true: false;
	}

	public static function Update($params)
	{
		if (isset($params['DATE_CREATE']))
		{
			$params['DATE_CREATE'] = $params['DATE_CREATE']? ConvertTimeStamp($params['DATE_CREATE']+CTimeZone::GetOffset()+date("Z"), 'SHORT'): '';
			COption::SetOptionString("voximplant", "phone_order_date_create", $params['DATE_CREATE']);
		}
		if (isset($params['DATE_MODIFY']))
		{
			$params['DATE_MODIFY'] = $params['DATE_MODIFY']? ConvertTimeStamp($params['DATE_MODIFY']+CTimeZone::GetOffset()+date("Z"), 'SHORT'): '';
			COption::SetOptionString("voximplant", "phone_order_date_modify", $params['DATE_MODIFY']);
		}
		if (isset($params['OPERATOR_STATUS']))
		{
			COption::SetOptionString("voximplant", "phone_order_operator_status", $params['OPERATOR_STATUS']);
		}
		if (isset($params['OPERATOR_CONTRACT']))
		{
			COption::SetOptionString("voximplant", "phone_order_operator_contract", $params['OPERATOR_CONTRACT']);
		}

		return false;
	}
}
?>
