<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/***************************************************************************
Convertation of the standard Softkey CSV file to the 
CSV file format of the Statistics module.
***************************************************************************/

/*
	Input parameters:
	INPUT_CSV_FILE - path to the source file
	OUTPUT_CSV_FILE - path to the resulting file
*/

$SEPARATOR = ","; // CSV separator

function CleanUpCsv(&$item)
{
	$item = TrimEx($item, "\"");
}

function PrepareQuotes(&$item)
{
	$item = "\"".str_replace("\"","\"\"", $item)."\"";
}

if ($fp_in = fopen($INPUT_CSV_FILE,"rb"))
{
	$upload_dir = $_SERVER["DOCUMENT_ROOT"]."/".COption::GetOptionString("main","upload_dir","/upload/"). "/statistic";
	if (substr($OUTPUT_CSV_FILE, 0, strlen($upload_dir))==$upload_dir && $fp_out = fopen($OUTPUT_CSV_FILE,"wb"))
	{
		$i = 0; // counter of the read valuable lines
		$j = 0; // counter of the written to the resulting  file lines 
		$lang_date_format = FORMAT_DATE; // date format for the current language
		$event1 = "softkey";
		$event2 = "buy";
		$EVENT_ID = CStatEventType::ConditionSet($event1, $event2, $arEventType)." (".$event1." / ".$event2.")";
		$SITE_ID = GetEventSiteID(); // short site identifier (ID)
		while (!feof($fp_in)) 
		{
			$arrCSV = fgetcsv($fp_in, 4096, $SEPARATOR);
			if (is_array($arrCSV) && count($arrCSV)>1)
			{
				array_walk($arrCSV, "CleanUpCsv");
				reset($arrCSV);
				$i++;
				// if it is the first line then
				if ($arrCSV[0]=="AUTHOR_ID")
				{
					// get an array with the field numbers 
					$arrS = array_flip($arrCSV);
				}
				elseif ($arrCSV[0]!="AUTHOR_ID" && is_array($arrS) && count($arrS)>0) // else form the CSV line in module format and write it to the resulting file 
				{
					$arrRes = array();

					// ID of an event type;
					$arrRes[] = $EVENT_ID;

					// event3
					$arrRes[] = $arrCSV[$arrS["ORDER_ID"]]." / ".$arrCSV[$arrS["PROGRAM_ID"]]." / ".$arrCSV[$arrS["OPTION_ID"]];

					// date
					$arrRes[] = $DB->FormatDate(trim($arrCSV[$arrS["PAID_DATE"]]), "DD.MM.YYYY", $lang_date_format);

					// additional parameter
					$ADDITIONAL_PARAMETER = $arrCSV[$arrS["REFERER1"]];
					if (strpos($ADDITIONAL_PARAMETER,$SITE_ID)===false)
					{
						$ADDITIONAL_PARAMETER = $arrCSV[$arrS["REFERER2"]];
					}
					$arrRes[] = $ADDITIONAL_PARAMETER;

					// money sum
					$arrRes[] = $arrCSV[$arrS["AMOUNT"]];

					// currency
					$arrRes[] = $arrCSV[$arrS["CURRENCY"]];

					$PAID_UP = $arrCSV[$arrS["PAID_UP"]];

					// if short site identifier exists in Additional parameter then
					if (strpos($ADDITIONAL_PARAMETER,$SITE_ID)!==false && $PAID_UP=="Y")
					{
						// write the line to the resulting file
						$j++;
						array_walk($arrRes, "PrepareQuotes");
						$str = implode(",",$arrRes);
						if ($j>1) $str = "\n".$str;
						fputs($fp_out, $str);
					}
				}
			}
		}
		@fclose($fp_out);
	}
	@fclose($fp_in);
}

/*

�������� CSV �����:

AUTHOR_ID - ��� �������� ������;
AUTHOR_NAME - �������� �������� ������;
ORDER_ID - ��� ������;
BASKET_ID - ��� ������� ������;
DATE_INSERT - ���� �������� ������;
PROGRAM_ID - ��� ���������;
OPTION_ID - ��� ����� ���������;
PROGRAM_NAME - �������� ���������;
LID - ������ ������;
BUYER_NAME - ������ ��� ����������;
BUYER_COMPANY_ID - ��� �������� ����������;
BUYER_COMPANY - �������� �������� ����������;
QUANTITY - ���������� �����;
AMOUNT - ��������� ������;
CURRENCY - ������ ������;
CURRENCY_RATE_USD - ���� ������ ������ �� ��������� � USD;
AMOUNT_USER_CURRENCY - ��������� � �������������� ������;
CURRENCY_RATE_USER_CURRENCY - ���� �������������� ������ � USD;
STATUS - ������� ������ ������;
DATE_STATUS - ���� ���������� ��������� �������;
DATE_DISPATCH - ���� ������������ ������� "��������";
CANCEL_REASON - ������� ������ ������;
CANCEL_REASON_ANOTHER - ������������ ������� ������;
PAID_UP - ������� �� ����� (Y/N)
PAID_DATE - ���� ������ ������;
DEALER_AGREEMENT_ID - ��� ���������� ��������;
DEALER_DISCOUNT - ��������� ������;
BUYER_AGREEMENT_ID - ��� �������� �������������� ������;
BUYER_DISCOUNT - �������������� ������;
COUPON - �������� ������ ��� ������;
AUTHOR_AMOUNT - ��������� ��������������;
AUTHOR_AMOUNT_USER_CURRENCY - ��������� �������������� � �������������� ������;
COMMISSION_AGREEMENT_ID - ��� ���������� ��������;
COMMISSION_AMOUNT - �������� ��������;
COMMISSION_CURRENCY - ������ ������� ���������� ��������������;
DELIVERY_LID - �����������������, ������������� �� �������� ������;
TRANSFER - ���������� �� �������� �� ���������� ���� ������ (Y/N);
AFFILIATE_ID - ��� �������� ���������;
AFFILIATE_NAME - �������� �������� ���������;
AFFILIATE_URL_FROM - � ����� �������� ��������� ������� ����������;
AFFILIATE_URL_TO - �� ����� �������� ������� ���������� �� ���������;
AFFILIATE_DATE - ���� �������� �� ���������;
AFFILIATE_AGREEMENT_ID - ��� ������������� ��������;
AFFILIATE_AMOUNT - �������� ���������;
IP_ADDRESS - IP ����� ����������;
HTTP_HOST - ���� ����������;
HTTP_REFERER - � ����� �������� ���������� ������� �����;
HTTP_ACCEPT_LANGUAGE - ����� �������� ����������;
HTTP_USER_AGENT - �������� �������� ����������;
REG_NAME - ��� ��� �����������;
REG_COMPANY - �������� �������� ��� �����������;
REG_EMAIL - email ��� �����������;
REG_ZIPCODE - �������� ������ ��� �����������;
REG_LOCATION - �������������� ��� �����������;
REG_CITY - ����� ��� �����������;
REG_ADDRESS - ����� ��� �����������;
REG_PHONE - ������� ��� �����������;
REG_PARAM1 - �������������� ���� 1;
REG_PARAM2 - �������������� ���� 2;
REG_PARAM3 - �������������� ���� 3;
REFERER1 - �������� referer1 �� ������ �� ����� (������������� ��������� ��������);
REFERER2 - �������� referer2 �� ������ �� �����;
REF_URL_FROM - ������ ������� ���������� �� ��������� ��������;
REF_URL_TO - ���� ������� ���������� �� ��������� ��������;
REF_DATE - ���� �������� �� ��������� ��������;
SESSION_REFERER - ������ ���������� ������ �� ������;
ORD_EMAIL - ���������� email � ������;
ORD_CONTACT_PERSON - ���������� ���� � ������;
ORD_COMPANY_NAME - �������� �������� � ������;
ORD_INN - ��� � ������;
ORD_LOCATION - �������������� � ������;
ORD_COUNTRY - ������ � ������;
ORD_ZIP_CODE - �������� ������ � ������;
ORD_STATE - ���� � ������;
ORD_CITY - ����� � ������;
ORD_ADDRESS - ����������� ����� � ������;
ORD_ADDRESS_FACT - ����������� ����� � ������;
ORD_PHONE - ������� � ������;
ORD_FAX - ���� � ������;
ORD_OKONH - ����� � ������;
ORD_OKPO - ���� � ������;
ORG_TYPE_NAME - ��������������� ����� �����������;
PAYMENT_SYS_NAME - �������� ������ ������.
*/
?>