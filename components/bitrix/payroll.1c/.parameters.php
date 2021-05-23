<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("main"))
	return;
	//print_r($arCurrentValues);

$arComponentParameters = array(	
	"PARAMETERS" => array(		
		"ORG_LIST" => Array(
			"NAME"=>GetMessage("ORG_LIST"), 
			"PARENT" => "BASE",
			"TYPE"=>"STRING", 
			"DEFAULT" => "", 
			'JS_FILE' => '/bitrix/components/bitrix/map.yandex.view/settings/settings.js',
			'JS_EVENT' => 'javascript:alert("asdasd");',
			"REFRESH"=>"Y",
			"COLS"=>25,
			"MULTIPLE"=>"Y"
		),	
		
			"PR_TIMEOUT"=> Array(
			"NAME"=>GetMessage("PAYROLL_TIMEOUT"), 
			"PARENT" => "BASE",
			"TYPE"=>"STRING", 
			"DEFAULT" => "25", 
			"COLS"=>25
		),
		

		"PR_NAMESPACE" => Array(
			"NAME"=>GetMessage("PAYROLL_NAMESPACE"), 
			"PARENT" => "BASE",
			"DEFAULT" => "http://www.1c-bitrix.ru", 
			"COLS"=>25
		),
		"CACHE_TIME" => Array("DEFAULT"=>"3600"),

	)
);

if(count($arCurrentValues["ORG_LIST"])>0)
{
	$arOrgNum=0;
	foreach($arCurrentValues["ORG_LIST"] as $arOrgName)
	{		
		if (!$arOrgName)
			continue;

		$arComponentParameters["GROUPS"]["ORG_".$arOrgNum]=Array("NAME"=>GetMessage("ORG_SETTINGS",Array("#ORG_NAME#"=>$arOrgName)));
		$arComponentParameters["PARAMETERS"]["PR_URL_".$arOrgNum]= Array(
			"NAME"=>GetMessage("PAYROLL_URL"), 
			"PARENT" => "ORG_".$arOrgNum,
			"TYPE"=>"STRING", 
			"DEFAULT" => "", 
			"COLS"=>50
		);
	
		$arComponentParameters["PARAMETERS"]["PR_PORT_".$arOrgNum]= Array(
			"NAME"=>GetMessage("PAYROLL_PORT"), 
			"PARENT" => "ORG_".$arOrgNum,
			"TYPE"=>"STRING", 
			"DEFAULT" => "80", 
			"COLS"=>25
		);
		
		$arComponentParameters["PARAMETERS"]["PR_LOGIN_".$arOrgNum]= Array(
			"NAME"=>GetMessage("PAYROLL_LOGIN"), 
			"PARENT" => "ORG_".$arOrgNum,
			"TYPE"=>"STRING", 
			"DEFAULT" => "", 
			"COLS"=>25
		);		
		$arComponentParameters["PARAMETERS"]["PR_PASSWORD_".$arOrgNum]= Array(
			"NAME"=>GetMessage("PAYROLL_PASSWORD"), 
			"PARENT" => "ORG_".$arOrgNum,
			"TYPE"=>"STRING", 
			"DEFAULT" => "", 
			"COLS"=>25
		);
	
		$arOrgNum++;
	}
}
?>
