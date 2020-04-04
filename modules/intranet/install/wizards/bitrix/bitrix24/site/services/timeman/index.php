<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("timeman"))
	return;

$arAccessRights = array(
	WIZARD_EMPLOYEES_GROUP => 'N',
	WIZARD_PERSONNEL_DEPARTMENT_GROUP => 'R',
	WIZARD_DIRECTION_GROUP => 'T',
	'1' => 'W',
);

$arTaskIDs = array();
$dbRes = CTask::GetList(array(), array(
	'MODULE_ID' => 'timeman',
	'SYS' => 'Y',
	'LETTER' => implode('|', $arAccessRights)
));

while ($arRes = $dbRes->Fetch())
{
	$arTaskIDs[$arRes['LETTER']] = $arRes['ID'];
}

$arTasksForModule = array();
foreach ($arAccessRights as $group => $letter)
{
	$APPLICATION->SetGroupRight('timeman', $group, $letter);
	$arTasksForModule[$group] = array('ID' => $arTaskIDs[$letter]);
}

CGroup::SetTasksForModule('timeman', $arTasksForModule);

if (CModule::IncludeModule('iblock'))
{
	$fields_file = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/timeman/install/fields.php";
	if (file_exists($fields_file))
		include($fields_file);
	$dep = new CIBlockSection;
	if ($ib = COption::GetOptionInt('intranet', 'iblock_structure', false))
		$entity_id = 'IBLOCK_'.$ib.'_SECTION';
	$arFields = Array(
		"UF_TM_TIME" =>"16:00",
		"UF_TM_DAY"=>5
	);
	$entities = CUserTypeEntity::GetList(Array(),Array("ENTITY_ID"=>$entity_id,"FIELD_NAME"=>"UF_REPORT_PERIOD")); 
	if($arEntity = $entities ->Fetch())
	{
		$oStatus = CUserFieldEnum::GetList(array(), array("USER_FIELD_ID" =>$arEntity["ID"]));
		while($result  = $oStatus->Fetch())
		{
			if ($result["XML_ID"] == "WEEK")
			{
				$period = $result["ID"];
				break;
			}
				
		}		
	}
	
	$arFields["UF_REPORT_PERIOD"] = $period;

	$dbsec = $dep->GetList(Array("ID"=>"asc"),Array("IBLOCK_ID"=>$ib,"SECTION_ID"=>false));
	$root = $dbsec->Fetch();
	if ($root)
		$dep->Update($root["ID"],$arFields);
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.'/modules/timeman/install/fields.php');
?>