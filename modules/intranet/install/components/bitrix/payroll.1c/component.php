<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$APPLICATION->SetTitle(GetMessage("TITLE_PAYROLL"));

if (CModule::IncludeModule('webservice'))
{
	if (!$USER->IsAuthorized())
	{
		ShowError(GetMessage("USER_AUTH_ERROR"));
		return;
	}

	if (empty($arParams['ORG_LIST']) || !is_array($arParams['ORG_LIST']) || !$arParams['ORG_LIST'][0])
	{
		ShowError(GetMessage("EMPTY_ORG_LIST"));
		return;
	}
	$arResult = Array();
	$isUTF=(ToUpper(SITE_CHARSET) == "UTF-8");
	$NeedActivation = false;
	$ID = $USER->GetID();
	$arActive = CUserOptions::GetOption($this->__name, "ACTIVATION", "N", $ID);
	if ($arActive != "Y")
		$NeedActivation = true;

	if ($_REQUEST["ACTIVATION"] == "Y")
		$arResult["IS_ACTIVATION"] = "Y";

	$arParams["YEAR_OFFSET"]=(intval($arParams['YEAR_OFFSET']) <= 0)? 1: intval($arParams['YEAR_OFFSET']);
	if ($_REQUEST['GETDATA'] == "Y" && $_REQUEST["USERORG"]>=0)
	{
		$arOrg=$_REQUEST["USERORG"];
		if (!$arParams["ORG_LIST"][$arOrg])
		{
			ShowError(GetMessage("UNKNOW_ORG"));
			return;
		}

		$arPort=(intval($arParams["PR_PORT_".$arOrg]<=0))? 80: intval($arParams["PR_PORT_".$arOrg]);
		$arTimeout=(intval($arParams["PR_TIMEOUT"]<=0))? 10: intval($arParams["PR_TIMEOUT"]);
		$arLogin=($isUTF)?$arParams["PR_LOGIN_".$arOrg]:$APPLICATION->ConvertCharset($arParams["PR_LOGIN_".$arOrg],SITE_CHARSET,"UTF-8");
		$arPassword=($isUTF)?$arParams["PR_PASSWORD_".$arOrg]:$APPLICATION->ConvertCharset($arParams["PR_PASSWORD_".$arOrg],SITE_CHARSET,"UTF-8");
		$arWebServiceUrl=$arParams["PR_URL_".$arOrg];

		if($arWebServiceUrl == '')
		{
			ShowError(GetMessage("payroll_error_url"));
			return;
		}

		$arParams["PR_NAMESPACE"]=($arParams["PR_NAMESPACE"]=="")? "http://www.1c-bitrix.ru": $arParams["PR_NAMESPACE"];
		$arParams["CACHE_TIME"]=(intval($arParams['CACHE_TIME'])<0)? 3600: intval($arParams['CACHE_TIME']);
		$arSoapParams = Array(
					GetMessage("SOAP_PORTAL_EMP_ID")=>$USER->GetID(),
					GetMessage("SOAP_IP") => $_SERVER['REMOTE_ADDR'],
					GetMessage("SOAP_DATETIME") => date("c"),
					GetMessage("SOAP_LOGIN") => ($isUTF)?$USER->GetLogin():$APPLICATION->ConvertCharset($USER->GetLogin(),SITE_CHARSET,"UTF-8"),
					GetMessage("SOAP_ADDINFO")=>$_SERVER["HTTP_HOST"]
				);
		$arActionType=$_REQUEST["ACTIONTYPE"];

		switch ($arActionType)
		{
			case "PAYROLL":
				if (!$_REQUEST['USERPIN'])
				{
					$arResult["RESULT"]['ERROR']=GetMessage("EMPTY_PIN_ERROR");
					break;
				}
				$arSoapParams[GetMessage("SOAP_EMP_PIN")] = $_REQUEST['USERPIN'];
				$arSoapMethod=GetMessage("SOAP_PAYROLL_METHOD");
				$arMonth=0;
				$arYear=0;
				$arMonth = $_REQUEST["MONTH"];
				$arYear = $_REQUEST["YEAR"];

				if ($arMonth<=0 || $arMonth>12)
					$arMonth = date("n");
				if ($arYear<=0 || $arYear>date("Y"))
					$arYear = date("Y");

				$arTitle=GetMessage("PAYROLL",Array("#DATE#"=>GetMessage("MONTH_".$arMonth)))." ".$arYear;
				$arSoapParams[GetMessage("SOAP_MONTH")]=$arMonth;
				$arSoapParams[GetMessage("SOAP_YEAR")]=$arYear;
				break;
			case "HOLIDAY":
				if (!$_REQUEST['USERPIN'])
				{
					$arResult["RESULT"]['ERROR']=GetMessage("EMPTY_PIN_ERROR");
					break;
				}
				$arSoapParams[GetMessage("SOAP_EMP_PIN")] = $_REQUEST['USERPIN'];
				$arSoapMethod=GetMessage("SOAP_HOLIDAY_METHOD");
				$arTitle=GetMessage("TITLE_HOLIDAY");
				break;
			case "ACTIVATION":
				if (!$_REQUEST['ACTIVATION_CODE'])
				{
					$arResult["RESULT"]['ERROR']=GetMessage("EMPTY_ACTIVATION_CODE_ERROR");
					break;
				}
				$arResult["IS_ACTIVATION"] = true;
				$arSoapParams[GetMessage("SOAP_ACTIVATION_CODE")] = $_REQUEST['ACTIVATION_CODE'];
				$arSoapMethod=GetMessage("SOAP_ACTIVATION_METHOD");
				break;
			default:
				ShowError(GetMessage("USER_AUTH_ERROR"));
				return;
		}

		if (!$arResult["RESULT"]['ERROR'])
		{
			$arSoapRequest=new CSOAPRequest(
				$arSoapMethod,
				$arParams["PR_NAMESPACE"],
				$arSoapParams
				);
			$request_body=$arSoapRequest->payload();

			if (!$isUTF)
			{
				$request_body=$APPLICATION->ConvertCharset($request_body,SITE_CHARSET,"UTF-8");
				$arSoapMethod=$APPLICATION->ConvertCharset($arSoapMethod,SITE_CHARSET,"UTF-8");
			}

			$req=new CHTTP;
			$arUrl=$req->ParseURL($arWebServiceUrl);
			$arUrl["port"]= $arPort;
			$req->SetAuthBasic($arLogin,$arPassword);
			$req->user_agent = "BITRIX SOAP Client";
			$req->http_timeout = $arTimeout;
			$req->additional_headers['SOAPAction'] = $arParams["PR_NAMESPACE"].$arSoapMethod;
			$result=$req->Query("POST",$arUrl["host"],$arUrl["port"],$arUrl["path"],$request_body,$arUrl["proto"],"text/xml; charset=utf-8");

			if (!$req->errstr)
			{
				if ($req->status == 401)
					$arResult["RESULT"]['ERROR']=GetMessage("AUTH_ERROR");
				else
				{
					preg_match("/^<soap:Envelope.*>/i",$req->result,$preg);
					if (empty($preg) || $req->status <> "200")
						$arResult["RESULT"]['ERROR']=GetMessage("WRONG_RESPONSE");
				}
			}
			else
				$arResult["RESULT"]['ERROR']=$req->errstr;

			if (!$arResult["RESULT"]['ERROR'])
			{
				$response = new CSOAPResponse();
				$response->decodeStream( $arSoapRequest, "\r\n\r\n".$req->result);

				if ($response->Value["return"])
				{
					$resFormHtml=base64_decode($response->Value["return"]);
						//removing BOM
					if(mb_substr($resFormHtml, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf))
						$resFormHtml = mb_substr($resFormHtml, 3);

					if (!$isUTF)
						$resFormHtml=$APPLICATION->ConvertCharset($resFormHtml,"UTF-8",SITE_CHARSET);

					if ($arActionType == "ACTIVATION")
					{
						if (!mb_strpos($resFormHtml, GetMessage("ERROR_RESP_MESSAGE")))
						{
							CUserOptions::SetOption($this->__name, "ACTIVATION", "Y", false, $ID);
							$arResult["FORWARD_BUTTON"]="FORWARD_TO_PAYROLL_FROM";
							$arResult["FORWARD_BUTTON_URL"]=$APPLICATION->GetCurPage();
						}
						else
						{
							$arResult["FORWARD_BUTTON"]="REPLAY_ACTIVATION";
							$arResult["FORWARD_BUTTON_URL"]=$APPLICATION->GetCurPage()."?ACTIVATION=Y";
						}
						$arResult["RESULT"]['html_form'] = $resFormHtml;
					}
					else
						$arResult["RESULT"]['html_form']=str_replace("<TITLE></TITLE>","<TITLE>".$arTitle."</TITLE>",$resFormHtml);
				}
				else
					$arResult["RESULT"]['html_form']=GetMessage("WRONG_RESPONSE");
			}
		}
	}
	$arResult["CURRENT_MONTH"]=date("n");
	$arResult["CURRENT_YEAR"]=date("Y");

	$arResult["ACTIVATION_FROM_URL"]=$APPLICATION->GetCurPage()."?ACTIVATION=Y";

	$arResult["PAYROLL_URL"]=$APPLICATION->GetCurPage();
	$APPLICATION->AddChainItem(GetMessage("TITLE_PAYROLL"),$arResult["PAYROLL_URL"]);
	if($arResult['IS_ACTIVATION'])
		$APPLICATION->AddChainItem(GetMessage("PIN_ACTIVATION"),$arResult["ACTIVATION_FROM_URL"]);
	$arResult["NEED_ACTIVATION"] = $NeedActivation;
	$arResult["ORG_LIST"] = $arParams["ORG_LIST"];

}
else
{ 
	ShowError(GetMessage("WEBSERVICE_MODULE_NOT_INSTALLED"));
	return;
}
if ($arResult["RESULT"] && !$arResult["IS_ACTIVATION"])
	$APPLICATION->RestartBuffer();
$this->IncludeComponentTemplate();
?>
